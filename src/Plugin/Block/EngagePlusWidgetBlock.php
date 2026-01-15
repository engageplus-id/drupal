<?php

namespace Drupal\engageplus\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an 'EngagePlus Widget' block.
 *
 * @Block(
 *   id = "engageplus_widget_block",
 *   admin_label = @Translation("EngagePlus Widget"),
 *   category = @Translation("Authentication")
 * )
 */
class EngagePlusWidgetBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new EngagePlusWidgetBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    AccountInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'container_id' => 'engageplus-widget',
      'button_text' => '',
      'theme' => '',
      'show_labels' => TRUE,
      'hide_for_authenticated' => TRUE,
      'show_logout_button' => TRUE,
      'custom_css_class' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->configuration;

    $form['appearance'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Appearance'),
    ];

    $form['appearance']['container_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Container ID'),
      '#description' => $this->t('Unique HTML ID for this widget instance. Must be unique if using multiple widgets.'),
      '#default_value' => $config['container_id'],
      '#required' => TRUE,
    ];

    $form['appearance']['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#description' => $this->t('Custom text for the login button. Leave empty to use global setting.'),
      '#default_value' => $config['button_text'],
    ];

    $form['appearance']['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#description' => $this->t('Widget color theme. Leave default to use global setting.'),
      '#options' => [
        '' => $this->t('Use global setting'),
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ],
      '#default_value' => $config['theme'],
    ];

    $form['appearance']['show_labels'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show provider labels'),
      '#description' => $this->t('Display text labels next to provider icons.'),
      '#default_value' => $config['show_labels'],
    ];

    $form['appearance']['custom_css_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom CSS Class'),
      '#description' => $this->t('Add custom CSS classes to the widget container.'),
      '#default_value' => $config['custom_css_class'],
    ];

    $form['behavior'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Behavior'),
    ];

    $form['behavior']['hide_for_authenticated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide for authenticated users'),
      '#description' => $this->t('Hide the widget when user is already logged in.'),
      '#default_value' => $config['hide_for_authenticated'],
    ];

    $form['behavior']['show_logout_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show logout button for authenticated users'),
      '#description' => $this->t('Show a logout button when user is logged in (only if widget is visible).'),
      '#default_value' => $config['show_logout_button'],
      '#states' => [
        'visible' => [
          ':input[name="settings[behavior][hide_for_authenticated]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['container_id'] = $values['appearance']['container_id'];
    $this->configuration['button_text'] = $values['appearance']['button_text'];
    $this->configuration['theme'] = $values['appearance']['theme'];
    $this->configuration['show_labels'] = $values['appearance']['show_labels'];
    $this->configuration['custom_css_class'] = $values['appearance']['custom_css_class'];
    $this->configuration['hide_for_authenticated'] = $values['behavior']['hide_for_authenticated'];
    $this->configuration['show_logout_button'] = $values['behavior']['show_logout_button'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configuration;
    $global_config = $this->configFactory->get('engageplus.settings');

    // Check if we should hide for authenticated users.
    if ($config['hide_for_authenticated'] && $this->currentUser->isAuthenticated()) {
      if ($config['show_logout_button']) {
        return [
          '#theme' => 'engageplus_logout',
          '#username' => $this->currentUser->getDisplayName(),
        ];
      }
      return [];
    }

    // Don't render if client ID is not configured.
    $client_id = $global_config->get('client_id');
    if (empty($client_id)) {
      if ($this->currentUser->hasPermission('administer engageplus')) {
        return [
          '#markup' => '<div class="messages messages--warning">' .
            $this->t('EngagePlus is not configured. Please <a href="@url">configure your Client ID</a>.', [
              '@url' => '/admin/config/people/engageplus',
            ]) .
            '</div>',
        ];
      }
      return [];
    }

    // Build widget configuration.
    // Ensure api_base_url always has a value, even for upgraded installations.
    $api_base_url = $global_config->get('api_base_url');
    if (empty($api_base_url)) {
      $api_base_url = 'https://engageplus.id';
    }
    
    // Get the full callback URL.
    $callback_url = $GLOBALS['base_url'] . '/engageplus/auth/callback';
    
    $widget_config = [
      'clientId' => $client_id,
      'containerId' => $config['container_id'],
      'issuer' => $api_base_url,
      'redirectUri' => $callback_url,
    ];

    // Apply button text (block config overrides global).
    $button_text = !empty($config['button_text']) ? $config['button_text'] : $global_config->get('button_text');
    if (!empty($button_text)) {
      $widget_config['buttonText'] = $button_text;
    }

    // Apply theme (block config overrides global).
    $theme = !empty($config['theme']) ? $config['theme'] : $global_config->get('theme');
    if (!empty($theme)) {
      $widget_config['theme'] = $theme;
    }

    // Apply show_labels setting.
    if (isset($config['show_labels'])) {
      $widget_config['showLabels'] = (bool) $config['show_labels'];
    }

    // Apply auth mode setting.
    $auth_mode = $global_config->get('auth_mode');
    if (!empty($auth_mode)) {
      $widget_config['authMode'] = $auth_mode;
    }

    // Apply custom styles from global configuration.
    $styles = [];
    
    // Get all style settings from config.
    $style_keys = [
      'width', 'max_width', 'padding',
      'background_color', 'primary_color', 'text_color', 
      'secondary_text_color', 'button_hover_color',
      'border_radius', 'border_color', 'border_width', 
      'box_shadow', 'button_border_radius',
      'font_family',
    ];
    
    foreach ($style_keys as $key) {
      $value = $global_config->get('styles.' . $key);
      if (!empty($value)) {
        // Convert snake_case to camelCase for JavaScript
        $js_key = lcfirst(str_replace('_', '', ucwords($key, '_')));
        $styles[$js_key] = $value;
      }
    }
    
    if (!empty($styles)) {
      $widget_config['styles'] = $styles;
    }

    // Add custom CSS class.
    $css_classes = ['engageplus-widget-container'];
    if (!empty($config['custom_css_class'])) {
      $css_classes[] = $config['custom_css_class'];
    }

    $build = [
      '#theme' => 'engageplus_widget',
      '#container_id' => $config['container_id'],
      '#client_id' => $client_id,
      '#config' => $widget_config,
      '#attached' => [
        'library' => [
          'engageplus/widget',
        ],
        'drupalSettings' => [
          'engageplus' => [
            'widgets' => [
              $config['container_id'] => $widget_config,
            ],
            'callbackUrl' => '/engageplus/auth/callback',
            'userInfoUrl' => '/engageplus/api/user',
            'debugMode' => $global_config->get('debug_mode') ?? FALSE,
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['config:engageplus.settings'],
      ],
    ];

    return $build;
  }

}

