<?php

namespace Drupal\os2uol_domain\Plugin\facets\processor;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\UnchangingCacheableDependencyTrait;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a processor that excludes items based on domain access.
 *
 * @FacetsProcessor(
 *   id = "domain_access_filter",
 *   label = @Translation("Filter by domain access"),
 *   description = @Translation("Exclude items depending on the accessibility on the current domain."),
 *   stages = {
 *     "build" = 50
 *   }
 * )
 */
class DomainAccessFilter extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  public function build(FacetInterface $facet, array $results) {
    dpm($results);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet) {
    $data_definition = $facet->getDataDefinition();
    if ($data_definition->getDataType() === 'entity_reference') {
      return TRUE;
    }
    if (!($data_definition instanceof ComplexDataDefinitionInterface)) {
      return FALSE;
    }

    $data_definition = $facet->getDataDefinition();
    $property_definitions = $data_definition->getPropertyDefinitions();
    foreach ($property_definitions as $definition) {
      if ($definition instanceof DataReferenceDefinitionInterface
        && $definition->getDataType() === 'entity_reference'
        && $definition->getConstraint('EntityType') === 'taxonomy_term'
      ) {
        $target_type = $data_definition->getSettings()['target_type'];
        $handler_settings = $data_definition->getSettings()['handler_settings'];
        $target_bundles = $handler_settings['target_bundles'];
        if (count($target_bundles) == 1) {
          $target_bundle = reset($target_bundles);
          $fields = $this->entityFieldManager->getFieldDefinitions($target_type, $target_bundle);
          if (isset($fields['domain_access'])) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeTags(parent::getCacheContexts(), ['url.site']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['taxonomy_term_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
