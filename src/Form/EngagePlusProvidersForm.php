<?php

namespace Drupal\engageplus\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\engageplus\EngagePlusApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for managing OAuth providers via EngagePlus API.
 */
class EngagePlusProvidersForm extends FormBase {

  /**
   * The EngagePlus API service.
   *
   * @var \Drupal\engageplus\EngagePlusApiService
   */
  protected $apiService;

  /**
   * Constructs a new EngagePlusProvidersForm.
   *
   * @param \Drupal\engageplus\EngagePlusApiService $api_service
   *   The EngagePlus API service.
   */
  public function __construct(EngagePlusApiService $api_service) {
    $this->apiService = $api_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('engageplus.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'engageplus_providers_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Check if API is configured
    if (!$this->config('engageplus.settings')->get('api_key')) {
      $form['error'] = [
        '#markup' => '<div class="messages messages--error">' .
          $this->t('Management API key not configured. Please <a href="@url">configure your API key</a> first.', [
            '@url' => '/admin/config/people/engageplus',
          ]) .
          '</div>',
      ];
      return $form;
    }

    $form['#tree'] = TRUE;

    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configure OAuth providers for your EngagePlus widget. Changes are saved directly to your EngagePlus account.') . '</p>',
    ];

    // Get current providers
    $providers = $this->apiService->getProviders();

    if ($providers === NULL) {
      $form['error'] = [
        '#markup' => '<div class="messages messages--error">' .
          $this->t('Failed to connect to EngagePlus API. Please check your API key configuration.') .
          '</div>',
      ];
      return $form;
    }

    $provider_types = [
      'google' => 'Google',
      'github' => 'GitHub',
      'microsoft' => 'Microsoft',
      'linkedin' => 'LinkedIn',
    ];

    foreach ($provider_types as $type => $label) {
      $provider_data = $providers[$type] ?? [];
      $enabled = !empty($provider_data['enabled']);

      $form['providers'][$type] = [
        '#type' => 'details',
        '#title' => $label,
        '#open' => $enabled,
      ];

      $form['providers'][$type]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable @provider', ['@provider' => $label]),
        '#default_value' => $enabled,
      ];

      $form['providers'][$type]['client_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Client ID'),
        '#default_value' => $provider_data['client_id'] ?? '',
        '#states' => [
          'visible' => [
            ':input[name="providers[' . $type . '][enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['providers'][$type]['client_secret'] = [
        '#type' => 'password',
        '#title' => $this->t('Client Secret'),
        '#default_value' => '', // Never show existing secrets
        '#description' => $this->t('Leave blank to keep existing secret.'),
        '#states' => [
          'visible' => [
            ':input[name="providers[' . $type . '][enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      if (!empty($provider_data['scopes'])) {
        $form['providers'][$type]['scopes'] = [
          '#type' => 'textfield',
          '#title' => $this->t('OAuth Scopes'),
          '#default_value' => implode(' ', $provider_data['scopes']),
          '#description' => $this->t('Space-separated list of OAuth scopes.'),
          '#states' => [
            'visible' => [
              ':input[name="providers[' . $type . '][enabled]"]' => ['checked' => TRUE],
            ],
          ],
        ];
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Providers'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $providers = $form_state->getValue('providers');

    foreach ($providers as $type => $config) {
      if (!empty($config['enabled'])) {
        $provider_config = [
          'enabled' => TRUE,
          'client_id' => $config['client_id'],
        ];

        // Only include secret if provided
        if (!empty($config['client_secret'])) {
          $provider_config['client_secret'] = $config['client_secret'];
        }

        // Include scopes if provided
        if (!empty($config['scopes'])) {
          $provider_config['scopes'] = explode(' ', trim($config['scopes']));
        }

        $result = $this->apiService->saveProvider($type, $provider_config);
        
        if ($result) {
          $this->messenger()->addStatus($this->t('@provider configuration saved successfully.', [
            '@provider' => ucfirst($type),
          ]));
        } else {
          $this->messenger()->addError($this->t('Failed to save @provider configuration.', [
            '@provider' => ucfirst($type),
          ]));
        }
      } else {
        // Disable provider
        $result = $this->apiService->deleteProvider($type);
        if ($result) {
          $this->messenger()->addStatus($this->t('@provider disabled successfully.', [
            '@provider' => ucfirst($type),
          ]));
        }
      }
    }
  }

}
