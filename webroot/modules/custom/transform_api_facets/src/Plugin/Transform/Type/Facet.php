<?php

namespace Drupal\transform_api_facets\Plugin\Transform\Type;

use Drupal\facets\Entity\Facet as FacetEntity;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\transform_api\Transform\TransformInterface;
use Drupal\transform_api\TransformationTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for view transform types.
 *
 * @TransformationType(
 *  id = "facet",
 *  title = "Facet transform"
 * )
 */
class Facet extends TransformationTypeBase {

  /**
   * The facet manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected DefaultFacetManager $facetManager;

  /**
   * Constructs a new Facet object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facetManager
   *   The facet manager.
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
  public function transform(TransformInterface $transform) {
    /** @var \Drupal\transform_api_facets\Transform\FacetTransform $facet_transform */
    $facet_transform = $transform;

    $facet = $this->facetManager->getEnabledFacets()[$facet_transform->getFacetId()] ?? NULL;

    if (!$facet) {
      return [];
    }

    $transformation = [
      'type' => 'facet',
      'facet_id' => $facet_transform->getFacetId(),
      'label' => $facet->label(),
      '#facet' => $facet,
    ];

    $this->buildFacet($facet, $transformation);

    return $transformation;
  }

  /**
   * Builds the facet transformation.
   *
   * @param \Drupal\facets\Entity\Facet $facet
   *   The facet.
   * @param array $transformation
   *   The transformation.
   *
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   */
  protected function buildFacet(FacetEntity $facet, array &$transformation) {
    $build = $this->facetManager->build($facet)[0];

    if ($build['#context']['list_style'] !== 'checkbox') {
      // @todo Replace with warning message and error log.
      throw new \Exception('Facet list style not supported');
    }

    $items = $build['#items'];

    foreach ($items as $item) {
      $value = $item['#title']['#raw_value'];

      $item_transform = [
        'type' => 'facet_item',
        'label' => $item['#title']['#value'],
        'count' => $item['#title']['#count'],
        'value' => $value,
      ];

      $transformation['items'][$value] = $item_transform;
    }
  }

}
