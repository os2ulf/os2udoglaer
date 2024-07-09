<?php

namespace Drupal\transform_api_facets\Plugin\Transform\Type;

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
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   *   The facet manager.
   */
  protected DefaultFacetManager $facetManager;

  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facetManager
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
   * @param \Drupal\facets\Entity\Facet $facet
   * @param $transformation
   *
   * @return void
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   */
  protected function buildFacet(\Drupal\facets\Entity\Facet $facet, &$transformation) {
    $build = $this->facetManager->build($facet)[0];

    if ($build['#context']['list_style'] !== 'checkbox') {
      // TODO: Replace with warning message and error log.
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
