<?php

namespace Drupal\engageplus;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Service for interacting with EngagePlus Management API.
 */
class EngagePlusApiService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

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
   * The API base URL.
   *
   * @var string
   */
  protected $apiBaseUrl = 'https://api.engageplus.id';

  /**
   * Constructs an EngagePlusApiService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }

  /**
   * Get the API key from configuration.
   *
   * @return string|null
   *   The API key or NULL if not set.
   */
  protected function getApiKey() {
    $config = $this->configFactory->get('engageplus.settings');
    return $config->get('api_key');
  }

  /**
   * Make an API request.
   *
   * @param string $method
   *   HTTP method (GET, POST, PUT, DELETE).
   * @param string $endpoint
   *   API endpoint (e.g., '/organizations/me').
   * @param array $data
   *   Request data (optional).
   *
   * @return array|null
   *   Response data or NULL on failure.
   */
  protected function request($method, $endpoint, array $data = []) {
    $api_key = $this->getApiKey();
    
    if (empty($api_key)) {
      $this->logger->error('EngagePlus API key not configured');
      return NULL;
    }

    $url = $this->apiBaseUrl . $endpoint;
    
    $options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ],
    ];

    if (!empty($data)) {
      $options['json'] = $data;
    }

    try {
      $response = $this->httpClient->request($method, $url, $options);
      $body = (string) $response->getBody();
      return json_decode($body, TRUE);
    }
    catch (RequestException $e) {
      $this->logger->error('EngagePlus API request failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Test API connection.
   *
   * @return bool
   *   TRUE if connection successful, FALSE otherwise.
   */
  public function testConnection() {
    $result = $this->getOrganization();
    return !empty($result);
  }

  /**
   * Get organization details.
   *
   * @return array|null
   *   Organization data or NULL on failure.
   */
  public function getOrganization() {
    return $this->request('GET', '/organizations/me');
  }

  /**
   * Get all OAuth providers.
   *
   * @return array|null
   *   Array of providers or NULL on failure.
   */
  public function getProviders() {
    return $this->request('GET', '/providers');
  }

  /**
   * Get a specific provider.
   *
   * @param string $provider_id
   *   Provider ID.
   *
   * @return array|null
   *   Provider data or NULL on failure.
   */
  public function getProvider($provider_id) {
    return $this->request('GET', '/providers/' . $provider_id);
  }

  /**
   * Create or update a provider.
   *
   * @param string $provider_type
   *   Provider type (google, github, microsoft, linkedin).
   * @param array $config
   *   Provider configuration.
   *
   * @return array|null
   *   Updated provider data or NULL on failure.
   */
  public function saveProvider($provider_type, array $config) {
    return $this->request('PUT', '/providers/' . $provider_type, $config);
  }

  /**
   * Delete a provider.
   *
   * @param string $provider_id
   *   Provider ID.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function deleteProvider($provider_id) {
    $result = $this->request('DELETE', '/providers/' . $provider_id);
    return $result !== NULL;
  }

  /**
   * Get widget configuration.
   *
   * @return array|null
   *   Widget configuration or NULL on failure.
   */
  public function getWidgetConfig() {
    return $this->request('GET', '/widget/config');
  }

  /**
   * Update widget configuration.
   *
   * @param array $config
   *   Widget configuration.
   *
   * @return array|null
   *   Updated configuration or NULL on failure.
   */
  public function updateWidgetConfig(array $config) {
    return $this->request('PUT', '/widget/config', $config);
  }

  /**
   * Get analytics/metrics.
   *
   * @param string $period
   *   Time period (7d, 30d, 90d).
   *
   * @return array|null
   *   Analytics data or NULL on failure.
   */
  public function getAnalytics($period = '30d') {
    return $this->request('GET', '/analytics?period=' . $period);
  }

  /**
   * Get email provider configuration.
   *
   * @return array|null
   *   Email provider config or NULL on failure.
   */
  public function getEmailProvider() {
    return $this->request('GET', '/email/provider');
  }

  /**
   * Update email provider configuration.
   *
   * @param array $config
   *   Email provider configuration.
   *
   * @return array|null
   *   Updated configuration or NULL on failure.
   */
  public function updateEmailProvider(array $config) {
    return $this->request('PUT', '/email/provider', $config);
  }

  /**
   * Get webhooks.
   *
   * @return array|null
   *   Array of webhooks or NULL on failure.
   */
  public function getWebhooks() {
    return $this->request('GET', '/webhooks');
  }

  /**
   * Create a webhook.
   *
   * @param array $webhook
   *   Webhook configuration.
   *
   * @return array|null
   *   Created webhook or NULL on failure.
   */
  public function createWebhook(array $webhook) {
    return $this->request('POST', '/webhooks', $webhook);
  }

  /**
   * Update a webhook.
   *
   * @param string $webhook_id
   *   Webhook ID.
   * @param array $webhook
   *   Webhook configuration.
   *
   * @return array|null
   *   Updated webhook or NULL on failure.
   */
  public function updateWebhook($webhook_id, array $webhook) {
    return $this->request('PUT', '/webhooks/' . $webhook_id, $webhook);
  }

  /**
   * Delete a webhook.
   *
   * @param string $webhook_id
   *   Webhook ID.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function deleteWebhook($webhook_id) {
    $result = $this->request('DELETE', '/webhooks/' . $webhook_id);
    return $result !== NULL;
  }

  /**
   * Get redirect URIs.
   *
   * @return array|null
   *   Array of redirect URIs or NULL on failure.
   */
  public function getRedirectUris() {
    return $this->request('GET', '/redirect-uris');
  }

  /**
   * Add a redirect URI.
   *
   * @param string $uri
   *   Redirect URI.
   *
   * @return array|null
   *   Updated URIs or NULL on failure.
   */
  public function addRedirectUri($uri) {
    return $this->request('POST', '/redirect-uris', ['uri' => $uri]);
  }

  /**
   * Delete a redirect URI.
   *
   * @param string $uri
   *   Redirect URI.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function deleteRedirectUri($uri) {
    $result = $this->request('DELETE', '/redirect-uris', ['uri' => $uri]);
    return $result !== NULL;
  }

}
