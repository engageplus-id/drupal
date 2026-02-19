<?php

namespace Drupal\engageplus\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure EngagePlus settings for this site.
 */
class EngagePlusConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'engageplus_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['engageplus.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('engageplus.settings');

    $form['getting_started'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Getting Started'),
      '#collapsible' => FALSE,
    ];

    $form['getting_started']['info'] = [
      '#markup' => '<div class="messages messages--status">' .
        '<p>' . $this->t('To use EngagePlus:') . '</p>' .
        '<ol>' .
        '<li>' . $this->t('Create an account at <a href="@url" target="_blank">engageplus.id</a>', ['@url' => 'https://engageplus.id']) . '</li>' .
        '<li>' . $this->t('Configure your OAuth providers (Google, GitHub, Microsoft, LinkedIn)') . '</li>' .
        '<li>' . $this->t('Copy your Organization ID from the dashboard') . '</li>' .
        '<li>' . $this->t('Paste it below and save') . '</li>' .
        '<li>' . $this->t('Add the EngagePlus widget block to your site') . '</li>' .
        '<li>' . $this->t('Copy your callback URL below and add it as an allowed redirect URI in your EngagePlus dashboard') . '</li>' .
        '</ol>' .
        '</div>',
    ];

    // Callback URL field with copy functionality.
    $callback_url = $GLOBALS['base_url'] . '/engageplus/auth/callback';
    $form['getting_started']['callback_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Callback URL'),
      '#description' => $this->t('Add this URL as a redirect URI in your EngagePlus dashboard. Click the field to select all and copy.'),
      '#default_value' => $callback_url,
      '#attributes' => [
        'readonly' => 'readonly',
        'onclick' => 'this.select();',
        'style' => 'font-family: monospace; background-color: #f5f5f5;',
      ],
      '#prefix' => '<div class="callback-url-wrapper">',
      '#suffix' => '<button type="button" class="button button--small" onclick="navigator.clipboard.writeText(\'' . $callback_url . '\'); this.textContent=\'Copied!\'; setTimeout(() => this.textContent=\'Copy to Clipboard\', 2000);" style="margin-left: 10px;">Copy to Clipboard</button></div>',
    ];

    $form['api_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Settings'),
    ];

    $form['api_settings']['org_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organization ID'),
      '#description' => $this->t('Your EngagePlus Organization ID from the dashboard (e.g., k4ia-Oq4p0Xz).'),
      '#default_value' => $config->get('org_id') ?: $config->get('client_id'), // Backwards compatibility
      '#required' => TRUE,
    ];

    $form['api_settings']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Management API Key'),
      '#description' => $this->t('Your EngagePlus Management API key. Create one in your <a href="@url" target="_blank">EngagePlus dashboard</a> to enable advanced management features (provider configuration, widget styling, analytics, etc.). See <a href="@docs" target="_blank">API documentation</a>.', [
        '@url' => 'https://engageplus.id/dashboard',
        '@docs' => 'https://engageplus.id/docs/api',
      ]),
      '#default_value' => $config->get('api_key'),
      '#attributes' => [
        'placeholder' => 'ep_api_xxxxxxxxxxxxxxxxxxxxxxxx',
      ],
    ];

    // Test API connection if key is set
    if (!empty($config->get('api_key'))) {
      /** @var \Drupal\engageplus\EngagePlusApiService $api */
      $api = \Drupal::service('engageplus.api');
      if ($api->testConnection()) {
        $org = $api->getOrganization();
        $form['api_settings']['api_status'] = [
          '#markup' => '<div class="messages messages--status">' .
            $this->t('✓ API Connected: @name', ['@name' => $org['name'] ?? 'Unknown']) .
            '</div>',
        ];
      } else {
        $form['api_settings']['api_status'] = [
          '#markup' => '<div class="messages messages--error">' .
            $this->t('✗ API Connection Failed - Check your API key') .
            '</div>',
        ];
      }
    }

    $form['api_settings']['widget_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Widget Script URL'),
      '#description' => $this->t('The URL to the EngagePlus widget script. Leave default unless instructed otherwise.'),
      '#default_value' => $config->get('widget_url') ?: 'https://auth.engageplus.id/public/pkce.js',
      '#required' => TRUE,
    ];

    $form['widget_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Widget Configuration'),
      '#description' => $this->t('Configure widget behavior. <strong>Note:</strong> Widget styling is now managed in your EngagePlus dashboard at <a href="@url" target="_blank">engageplus.id</a>.', [
        '@url' => 'https://engageplus.id',
      ]),
      '#open' => FALSE,
    ];

    $form['user_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User Management'),
    ];

    $form['user_settings']['auto_create_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically create users'),
      '#description' => $this->t('Create Drupal user accounts automatically for new EngagePlus users.'),
      '#default_value' => $config->get('auto_create_users') ?? TRUE,
    ];

    $form['user_settings']['default_role'] = [
      '#type' => 'select',
      '#title' => $this->t('Default role'),
      '#description' => $this->t('Role to assign to newly created users.'),
      '#options' => user_role_names(TRUE),
      '#default_value' => $config->get('default_role') ?: 'authenticated',
      '#states' => [
        'visible' => [
          ':input[name="auto_create_users"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['user_settings']['username_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username pattern'),
      '#description' => $this->t('Pattern for generating usernames. Use [email] for email address, [name] for display name. Default: [email]'),
      '#default_value' => $config->get('username_pattern') ?: '[email]',
      '#states' => [
        'visible' => [
          ':input[name="auto_create_users"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['user_settings']['email_verification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip email verification'),
      '#description' => $this->t('Automatically verify email addresses from OAuth providers (recommended).'),
      '#default_value' => $config->get('email_verification') ?? TRUE,
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Settings'),
      '#open' => FALSE,
    ];

    $form['advanced']['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug mode'),
      '#description' => $this->t('Log authentication events to watchdog. Useful for troubleshooting.'),
      '#default_value' => $config->get('debug_mode') ?? FALSE,
    ];

    $form['advanced']['redirect_after_login'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect after login'),
      '#description' => $this->t('Path to redirect users to after successful login. Leave empty to stay on current page. Use &lt;front&gt; for homepage.'),
      '#default_value' => $config->get('redirect_after_login'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $org_id = $form_state->getValue('org_id');
    if (empty($org_id)) {
      $form_state->setErrorByName('org_id', $this->t('Organization ID is required.'));
    }

    $widget_url = $form_state->getValue('widget_url');
    if (!filter_var($widget_url, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('widget_url', $this->t('Widget URL must be a valid URL.'));
    }

    $redirect = $form_state->getValue('redirect_after_login');
    if (!empty($redirect) && $redirect !== '<front>' && !str_starts_with($redirect, '/')) {
      $form_state->setErrorByName('redirect_after_login', $this->t('Redirect path must start with / or be &lt;front&gt;.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('engageplus.settings');
    
    $config->set('org_id', $form_state->getValue('org_id'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('widget_url', $form_state->getValue('widget_url'))
      ->set('auto_create_users', $form_state->getValue('auto_create_users'))
      ->set('default_role', $form_state->getValue('default_role'))
      ->set('username_pattern', $form_state->getValue('username_pattern'))
      ->set('email_verification', $form_state->getValue('email_verification'))
      ->set('debug_mode', $form_state->getValue('debug_mode'))
      ->set('redirect_after_login', $form_state->getValue('redirect_after_login'));
    
    $config->save();

    parent::submitForm($form, $form_state);
  }

}

