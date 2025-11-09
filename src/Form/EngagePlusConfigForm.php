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
        '<li>' . $this->t('Add <strong>@url</strong> as a redirect URI in your EngagePlus dashboard', [
          '@url' => $GLOBALS['base_url'] . '/engageplus/auth/callback',
        ]) . '</li>' .
        '</ol>' .
        '</div>',
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

    $form['api_settings']['widget_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Widget Script URL'),
      '#description' => $this->t('The URL to the EngagePlus widget script. Leave default unless instructed otherwise.'),
      '#default_value' => $config->get('widget_url') ?: 'https://engageplus.id/widget.js',
      '#required' => TRUE,
    ];

    $form['widget_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Widget Appearance'),
      '#description' => $this->t('Customize the appearance of the EngagePlus widget. These settings can be overridden in individual block configurations.'),
    ];

    $form['widget_settings']['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#description' => $this->t('Custom text for the login button.'),
      '#default_value' => $config->get('button_text'),
    ];

    $form['widget_settings']['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#description' => $this->t('Widget color theme.'),
      '#options' => [
        '' => $this->t('Default'),
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ],
      '#default_value' => $config->get('theme') ?: '',
    ];

    $form['widget_settings']['show_labels'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show provider labels'),
      '#description' => $this->t('Display text labels next to provider icons.'),
      '#default_value' => $config->get('show_labels') ?? TRUE,
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
    $this->config('engageplus.settings')
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('widget_url', $form_state->getValue('widget_url'))
      ->set('button_text', $form_state->getValue('button_text'))
      ->set('theme', $form_state->getValue('theme'))
      ->set('show_labels', $form_state->getValue('show_labels'))
      ->set('auto_create_users', $form_state->getValue('auto_create_users'))
      ->set('default_role', $form_state->getValue('default_role'))
      ->set('username_pattern', $form_state->getValue('username_pattern'))
      ->set('email_verification', $form_state->getValue('email_verification'))
      ->set('debug_mode', $form_state->getValue('debug_mode'))
      ->set('redirect_after_login', $form_state->getValue('redirect_after_login'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

