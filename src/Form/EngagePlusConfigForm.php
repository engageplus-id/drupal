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
        '<li>' . $this->t('Copy your Client ID from the dashboard') . '</li>' .
        '<li>' . $this->t('Paste it below and save') . '</li>' .
        '<li>' . $this->t('Add the EngagePlus widget block to your site') . '</li>' .
        '<li>' . $this->t('Copy your callback URL below and add it as a redirect URI in your EngagePlus dashboard') . '</li>' .
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

    $form['api_settings']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#description' => $this->t('Your EngagePlus Client ID from the dashboard.'),
      '#default_value' => $config->get('client_id'),
      '#required' => TRUE,
    ];

    $form['api_settings']['api_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Base URL'),
      '#description' => $this->t('The base URL for EngagePlus API. Leave default unless using a custom instance.'),
      '#default_value' => $config->get('api_base_url') ?: 'https://engageplus.id',
      '#required' => TRUE,
    ];

    $form['api_settings']['widget_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Widget Script URL'),
      '#description' => $this->t('The URL to the EngagePlus widget script. Leave default unless instructed otherwise.'),
      '#default_value' => $config->get('widget_url') ?: 'https://engageplus.id/widget.js',
      '#required' => TRUE,
    ];

    $form['widget_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Widget Appearance'),
      '#description' => $this->t('Customize the appearance of the EngagePlus widget. These settings can be overridden in individual block configurations. <a href="@url" target="_blank">View documentation</a>', [
        '@url' => 'https://engageplus.id/docs/widget-customization',
      ]),
      '#open' => FALSE,
    ];

    $form['widget_settings']['show_labels'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show provider labels'),
      '#description' => $this->t('Display text labels next to provider icons.'),
      '#default_value' => $config->get('show_labels') ?? TRUE,
    ];

    // Layout & Sizing
    $form['widget_settings']['layout'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout & Sizing'),
      '#open' => FALSE,
    ];

    $form['widget_settings']['layout']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#description' => $this->t('Width of the widget container (e.g., 400px, 100%, 30rem)'),
      '#default_value' => $config->get('styles.width') ?: '400px',
      '#size' => 20,
    ];

    $form['widget_settings']['layout']['max_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max Width'),
      '#description' => $this->t('Maximum width (useful for responsive layouts)'),
      '#default_value' => $config->get('styles.max_width') ?: '100%',
      '#size' => 20,
    ];

    $form['widget_settings']['layout']['padding'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Padding'),
      '#description' => $this->t('Inner padding of the widget (e.g., 24px, 1.5rem)'),
      '#default_value' => $config->get('styles.padding') ?: '24px',
      '#size' => 20,
    ];

    // Colors
    $form['widget_settings']['colors'] = [
      '#type' => 'details',
      '#title' => $this->t('Colors'),
      '#open' => FALSE,
    ];

    $form['widget_settings']['colors']['background_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Background Color'),
      '#description' => $this->t('Background color of the widget'),
      '#default_value' => $config->get('styles.background_color') ?: '#ffffff',
    ];

    $form['widget_settings']['colors']['primary_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Primary Color'),
      '#description' => $this->t('Primary brand color (links, hover effects)'),
      '#default_value' => $config->get('styles.primary_color') ?: '#4f46e5',
    ];

    $form['widget_settings']['colors']['text_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Text Color'),
      '#description' => $this->t('Main text color'),
      '#default_value' => $config->get('styles.text_color') ?: '#111827',
    ];

    $form['widget_settings']['colors']['secondary_text_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Secondary Text Color'),
      '#description' => $this->t('Secondary/muted text color'),
      '#default_value' => $config->get('styles.secondary_text_color') ?: '#6b7280',
    ];

    $form['widget_settings']['colors']['button_hover_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Button Hover Color'),
      '#description' => $this->t('Background color when hovering over buttons'),
      '#default_value' => $config->get('styles.button_hover_color') ?: '#f9fafb',
    ];

    // Borders & Shadows
    $form['widget_settings']['borders'] = [
      '#type' => 'details',
      '#title' => $this->t('Borders & Shadows'),
      '#open' => FALSE,
    ];

    $form['widget_settings']['borders']['border_radius'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border Radius'),
      '#description' => $this->t('Corner rounding of the widget container (e.g., 8px, 1rem)'),
      '#default_value' => $config->get('styles.border_radius') ?: '8px',
      '#size' => 20,
    ];

    $form['widget_settings']['borders']['border_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Border Color'),
      '#description' => $this->t('Color of the widget border'),
      '#default_value' => $config->get('styles.border_color') ?: '#e5e7eb',
    ];

    $form['widget_settings']['borders']['border_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border Width'),
      '#description' => $this->t('Thickness of the border (e.g., 2px, 0.125rem)'),
      '#default_value' => $config->get('styles.border_width') ?: '2px',
      '#size' => 20,
    ];

    $form['widget_settings']['borders']['box_shadow'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Box Shadow'),
      '#description' => $this->t('Drop shadow effect (e.g., 0 10px 15px -3px rgba(0, 0, 0, 0.1))'),
      '#default_value' => $config->get('styles.box_shadow') ?: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
      '#maxlength' => 255,
    ];

    $form['widget_settings']['borders']['button_border_radius'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Border Radius'),
      '#description' => $this->t('Corner rounding of provider buttons (e.g., 6px, 0.5rem)'),
      '#default_value' => $config->get('styles.button_border_radius') ?: '6px',
      '#size' => 20,
    ];

    // Typography
    $form['widget_settings']['typography'] = [
      '#type' => 'details',
      '#title' => $this->t('Typography'),
      '#open' => FALSE,
    ];

    $form['widget_settings']['typography']['font_family'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Font Family'),
      '#description' => $this->t('Font family for all widget text'),
      '#default_value' => $config->get('styles.font_family') ?: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
      '#maxlength' => 255,
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
    $client_id = $form_state->getValue('client_id');
    if (empty($client_id)) {
      $form_state->setErrorByName('client_id', $this->t('Client ID is required.'));
    }

    $api_base_url = $form_state->getValue('api_base_url');
    if (!filter_var($api_base_url, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('api_base_url', $this->t('API Base URL must be a valid URL.'));
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
    
    $config->set('client_id', $form_state->getValue('client_id'))
      ->set('api_base_url', $form_state->getValue('api_base_url'))
      ->set('widget_url', $form_state->getValue('widget_url'))
      ->set('show_labels', $form_state->getValue('show_labels'))
      ->set('auto_create_users', $form_state->getValue('auto_create_users'))
      ->set('default_role', $form_state->getValue('default_role'))
      ->set('username_pattern', $form_state->getValue('username_pattern'))
      ->set('email_verification', $form_state->getValue('email_verification'))
      ->set('debug_mode', $form_state->getValue('debug_mode'))
      ->set('redirect_after_login', $form_state->getValue('redirect_after_login'));
    
    // Save widget style settings
    $layout = $form_state->getValue(['widget_settings', 'layout']);
    $colors = $form_state->getValue(['widget_settings', 'colors']);
    $borders = $form_state->getValue(['widget_settings', 'borders']);
    $typography = $form_state->getValue(['widget_settings', 'typography']);
    
    if ($layout) {
      $config->set('styles.width', $layout['width'])
        ->set('styles.max_width', $layout['max_width'])
        ->set('styles.padding', $layout['padding']);
    }
    
    if ($colors) {
      $config->set('styles.background_color', $colors['background_color'])
        ->set('styles.primary_color', $colors['primary_color'])
        ->set('styles.text_color', $colors['text_color'])
        ->set('styles.secondary_text_color', $colors['secondary_text_color'])
        ->set('styles.button_hover_color', $colors['button_hover_color']);
    }
    
    if ($borders) {
      $config->set('styles.border_radius', $borders['border_radius'])
        ->set('styles.border_color', $borders['border_color'])
        ->set('styles.border_width', $borders['border_width'])
        ->set('styles.box_shadow', $borders['box_shadow'])
        ->set('styles.button_border_radius', $borders['button_border_radius']);
    }
    
    if ($typography) {
      $config->set('styles.font_family', $typography['font_family']);
    }
    
    $config->save();

    parent::submitForm($form, $form_state);
  }

}

