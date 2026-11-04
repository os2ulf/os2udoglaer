<?php

namespace Drupal\os2uol_search\Plugin\facets\processor;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the public profile role labels in the search type facet.
 *
 * @FacetsProcessor(
 *   id = "search_type_role_labels",
 *   label = @Translation("Search type role labels"),
 *   description = @Translation("Display the configured labels for public profile roles in the search type facet."),
 *   stages = {
 *     "build" = 6
 *   }
 * )
 */
class SearchTypeRoleLabels extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The roles whose labels are intended for public search filters.
   */
  private const PUBLIC_ROLE_IDS = [
    'corporation',
    'course_provider',
    'place_of_visit',
  ];

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    if (!$this->supportsFacet($facet)) {
      return $results;
    }

    foreach ($results as $result) {
      $role_id = $result->getRawValue();
      if (!in_array($role_id, self::PUBLIC_ROLE_IDS, TRUE)) {
        continue;
      }

      // Viewing role entities requires administrative permissions. Only expose
      // these public labels, using config so language overrides are respected.
      $role_config = $this->configFactory->get('user.role.' . $role_id);
      $facet->addCacheableDependency($role_config);
      $label = $role_config->get('label');

      if (is_string($label) && $label !== '') {
        $result->setDisplayValue($label);
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet) {
    return $facet->id() === 'search_type';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['languages:language_interface'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
