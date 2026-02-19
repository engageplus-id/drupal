<?php

namespace Drupal\engageplus\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\engageplus\EngagePlusApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for managing widget styling via EngagePlus API.
 */
class EngagePlusWidgetStylingForm extends FormBase {

  /**
   * The EngagePlus API service.
   *
   * @var \Drupal\engageplus\EngagePlusApiService
   */
  protected $apiService;

  /**
   * Constructs a new EngagePlusWidgetStylingForm.
   *
   * @param \Drupal\engageplus\EngagePlus ApiService $api_service
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
    return 'engageplus_widget_styling_form';
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

    $form['info'] = [
      '#markup' => '<p>' . $this->t('Customize your widget appearance. Changes are saved directly to EngagePlus and will apply to all integrations using your organization.') . '</p>',
    ];

    // Get current widget config
    $widget_config = $this->apiService->getWidgetConfig();

    if ($widget_config === NULL) {
      $form['error'] = [
        '#markup' => '<div class="messages messages--error">' .
          $this->t('Failed to load widget configuration from EngagePlus API.') .
          '</div>',
      ];
      return $form;
    }

    $styles = $widget_config['styles'] ?? [];

    // Colors
    $form['colors'] = [
      '#type' => 'details',
      '#title' => $this->t('Colors'),
      '#open' => TRUE,
    ];

    $form['colors']['primary_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Primary Color'),
      '#default_value' => $styles['primaryColor'] ?? '#4f46e5',
    ];

    $form['colors']['background_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Background Color'),
      '#default_value' => $styles['backgroundColor'] ?? '#ffffff',
    ];

    $form['colors']['text_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Text Color'),
      '#default_value' => $styles['textColor'] ?? '#111827',
    ];

    // Layout
    $form['layout'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout'),
      '#open' => FALSE,
    ];

    $form['layout']['border_radius'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border Radius'),
      '#default_value' => $styles['borderRadius'] ?? '8px',
      '#size' => 10,
    ];

    $form['layout']['padding'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Padding'),
      '#default_value' => $styles['padding'] ?? '24px',
      '#size' => 10,
    ];

    // Providers
    $form['providers'] = [
      '#type' => 'details',
      '#title' => $this->t('Provider Display'),
      '#open' => FALSE,
    ];

    $form['providers']['show_labels'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show provider labels'),
      '#default_value' => $widget_config['showLabels'] ?? TRUE,
    ];

    $form['providers']['layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Layout'),
      '#options' => [
        'single_column' => $this->t('Single Column'),
        'two_column' => $this->t('Two Column'),
        'grid' => $this->t('Grid'),
      ],
      '#default_value' => $widget_config['layout'] ?? 'single_column',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Widget Styling'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = [
      'styles' => [
        'primaryColor' => $form_state->getValue(['colors', 'primary_color']),
        'backgroundColor' => $form_state->getValue(['colors', 'background_color']),
        'textColor' => $form_state->getValue(['colors', 'text_color']),
        'borderRadius' => $form_state->getValue(['layout', 'border_radius']),
        'padding' => $form_state->getValue(['layout', 'padding']),
      ],
      'showLabels' => (bool) $form_state->getValue(['providers', 'show_labels']),
      'layout' => $form_state->getValue(['providers', 'layout']),
    ];

    $result = $this->apiService->updateWidgetConfig($config);

    if ($result) {
      $this->messenger()->addStatus($this->t('Widget styling saved successfully.'));
    } else {
      $this->messenger()->addError($this->t('Failed to save widget styling.'));
    }
  }

}
