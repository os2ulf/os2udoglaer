<?php

namespace Drupal\transform_api_facets\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Display extender plugin to control Transform settings.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "transform_api_facets",
 *   title = @Translation("Transform API Facets"),
 *   help = @Translation("Manage transform settings for facets."),
 *   no_ui = FALSE,
 * )
 */
class TransformApiFacets extends DisplayExtenderPluginBase {

  /**
   * The facet manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected DefaultFacetManager $facetManager;

  /**
   * Constructs a new TransformApiViews object.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facetManager
   *   The entity transform repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DefaultFacetManager $facetManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->facetManager = $facetManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('facets.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    return [
      'transform_facets' => [
        'default' => [],
      ],
    ] + parent::defineOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'transform_facets') {
      $form['#title'] = $form['#title'] . ' ' . $this->t('Which facets should be enabled?');

      $facets = $this->facetManager->getEnabledFacets();

      $options = [];

      foreach ($facets as $facet) {
        $options[$facet->id()] = $facet->label();
      }

      $form['transform_facets'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Enabled facets'),
        '#description' => $this->t('Choose transform style for row result'),
        '#default_value' => $this->options['transform_facets'],
        '#options' => $options,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'transform_facets') {
      $this->options['transform_facets'] = $form_state->getValue('transform_facets');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    $categories['transform_api_facets'] = [
      'title' => $this->t('Transform Facets'),
      'column' => 'first',
    ];
    $options['transform_facets'] = [
      'category' => 'transform_api_facets',
      'title' => $this->t('Facets settings'),
      'value' => $this->t('Change'),
    ];
  }

}
