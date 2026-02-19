<?php

namespace Drupal\engageplus\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;

/**
 * Controller for EngagePlus authentication.
 */
class EngagePlusController extends ControllerBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new EngagePlusController.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerInterface $logger,
    AccountProxyInterface $current_user
  ) {
    $this->configFactory = $config_factory;
    $this->logger = $logger;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory')->get('engageplus'),
      $container->get('current_user')
    );
  }

  /**
   * Handles the authentication callback from EngagePlus widget.
   *
   * This is the page where the widget JavaScript will send the user data.
   */
  public function authCallback(Request $request) {
    $config = $this->configFactory->get('engageplus.settings');
    $org_id = $config->get('org_id') ?: $config->get('client_id'); // Backwards compatibility
    $callback_url = $GLOBALS['base_url'] . '/engageplus/auth/callback';
    
    // This page serves as the redirect target.
    // The actual authentication is handled by JavaScript in the widget.
    return [
      '#markup' => '<div id="engageplus-auth-callback">' .
        $this->t('Authenticating...') .
        '</div>',
      '#attached' => [
        'library' => ['engageplus/callback'],
        'drupalSettings' => [
          'engageplus' => [
            'callback' => [
              'orgId' => $org_id,
              'redirectUri' => $callback_url,
              'widgetUrl' => $config->get('widget_url') ?: 'https://auth.engageplus.id/public/pkce.js',
              'debugMode' => $config->get('debug_mode') ?? FALSE,
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Receives user information from the EngagePlus widget and creates/logs in user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object containing user data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with success or error status.
   */
  public function getUserInfo(Request $request) {
    $config = $this->configFactory->get('engageplus.settings');
    
    // Get JSON data from request.
    $data = json_decode($request->getContent(), TRUE);
    
    if (empty($data)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'No data received',
      ], 400);
    }

    // Validate user data is present.
    if (empty($data['user']) || empty($data['user']['email'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Missing user data or email',
      ], 400);
    }

    try {
      $user_data = $data['user'];
      
      // Debug logging if enabled.
      if ($config->get('debug_mode')) {
        $this->logger->info('EngagePlus user data received: @data', [
          '@data' => print_r($user_data, TRUE),
        ]);
      }

      // Find or create user.
      $account = $this->findOrCreateUser($user_data, $config);

      if (!$account) {
        throw new \Exception('Failed to create or find user account');
      }

      // Log the user in.
      user_login_finalize($account);

      // Determine redirect URL.
      $redirect_url = $this->getRedirectUrl($config);

      return new JsonResponse([
        'success' => TRUE,
        'uid' => $account->id(),
        'username' => $account->getAccountName(),
        'email' => $account->getEmail(),
        'redirect' => $redirect_url,
      ]);

    }
    catch (\Exception $e) {
      $this->logger->error('EngagePlus authentication error: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Decodes a JWT token and returns the payload.
   *
   * @param string $token
   *   The JWT token.
   *
   * @return array|null
   *   The decoded token payload or NULL on failure.
   */
  protected function decodeJwtToken($token) {
    try {
      // Split the JWT token.
      $parts = explode('.', $token);
      if (count($parts) !== 3) {
        return NULL;
      }

      // Decode the payload (second part).
      $payload = base64_decode(strtr($parts[1], '-_', '+/'));
      if (!$payload) {
        return NULL;
      }

      $data = json_decode($payload, TRUE);
      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to decode JWT token: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Finds an existing user or creates a new one.
   *
   * @param array $user_data
   *   User data from EngagePlus.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Module configuration.
   *
   * @return \Drupal\user\Entity\User|null
   *   The user account or NULL on failure.
   */
  protected function findOrCreateUser(array $user_data, $config) {
    $email = $user_data['email'];

    // Try to find existing user by email.
    $existing_users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['mail' => $email]);

    if (!empty($existing_users)) {
      // User exists, return it.
      $account = reset($existing_users);
      
      if ($config->get('debug_mode')) {
        $this->logger->info('Existing user found: @username (@email)', [
          '@username' => $account->getAccountName(),
          '@email' => $email,
        ]);
      }
      
      return $account;
    }

    // Check if auto-creation is enabled.
    if (!$config->get('auto_create_users')) {
      throw new \Exception('User does not exist and auto-creation is disabled');
    }

    // Create new user.
    return $this->createNewUser($user_data, $config);
  }

  /**
   * Creates a new Drupal user from EngagePlus data.
   *
   * @param array $user_data
   *   User data from EngagePlus.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Module configuration.
   *
   * @return \Drupal\user\Entity\User
   *   The newly created user account.
   */
  protected function createNewUser(array $user_data, $config) {
    $email = $user_data['email'];
    // The widget provides 'name' field from OAuth provider
    $display_name = $user_data['name'] ?? $user_data['given_name'] ?? $user_data['email'];
    
    // Generate username based on pattern.
    $username_pattern = $config->get('username_pattern') ?: '[email]';
    $username = str_replace('[email]', $email, $username_pattern);
    $username = str_replace('[name]', $display_name, $username);
    
    // Ensure username is unique.
    $username = $this->generateUniqueUsername($username);

    // Create user account.
    $account = User::create([
      'name' => $username,
      'mail' => $email,
      'status' => 1,
      'init' => $email,
    ]);

    // Skip email verification if configured.
    if ($config->get('email_verification')) {
      // Mark email as verified by setting login timestamp.
      $account->set('login', \Drupal::time()->getRequestTime());
    }

    // Add default role.
    $default_role = $config->get('default_role');
    if ($default_role && $default_role !== 'authenticated') {
      $account->addRole($default_role);
    }

    // Save the account.
    $account->save();

    if ($config->get('debug_mode')) {
      $this->logger->info('New user created: @username (@email)', [
        '@username' => $account->getAccountName(),
        '@email' => $email,
      ]);
    }

    return $account;
  }

  /**
   * Generates a unique username.
   *
   * @param string $base_name
   *   The base username.
   *
   * @return string
   *   A unique username.
   */
  protected function generateUniqueUsername($base_name) {
    $username = $base_name;
    $suffix = 1;

    // Keep trying until we find a unique username.
    while ($this->usernameExists($username)) {
      $username = $base_name . '_' . $suffix;
      $suffix++;
    }

    return $username;
  }

  /**
   * Checks if a username already exists.
   *
   * @param string $username
   *   The username to check.
   *
   * @return bool
   *   TRUE if username exists, FALSE otherwise.
   */
  protected function usernameExists($username) {
    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['name' => $username]);

    return !empty($users);
  }

  /**
   * Gets the redirect URL after successful authentication.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Module configuration.
   *
   * @return string
   *   The redirect URL.
   */
  protected function getRedirectUrl($config) {
    $redirect = $config->get('redirect_after_login');

    if (empty($redirect)) {
      // Return current page (handled by JavaScript).
      return 'current';
    }

    if ($redirect === '<front>') {
      return Url::fromRoute('<front>')->toString();
    }

    return $redirect;
  }

}

