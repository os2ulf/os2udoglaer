<?php

declare(strict_types = 1);

namespace Drupal\os2uol_pretix\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2uol_pretix\Plugin\Menu\LocalTask\PretixLocalTask;
use Drupal\os2uol_pretix\Routing\PretixRouteProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pretix tab for entities.
 */
class PretixLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  protected string $basePluginId;
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Creates a new PretixLocalTasks.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(string $base_plugin_id, EntityTypeManagerInterface $entity_type_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entityType) {
      if ($entityType->hasLinkTemplate(PretixRouteProvider::LINK_TEMPLATE)) {
        $entityTypeId = $entityType->id();
        $this->derivatives["$entityTypeId.pretix"] = [
          'class' => PretixLocalTask::class,
          'route_name' => PretixRouteProvider::getPretixRouteName($entityType),
          'title' => $this->t('Pretix'),
          'base_route' => "entity.$entityTypeId.canonical",
          // Weight it after nodes' Edit, Delete, Versions.
          'weight' => 29,
        ] + $base_plugin_definition;

        $this->derivatives["$entityTypeId.pretix.dates"] = [
            'class' => PretixLocalTask::class,
            'route_name' => PretixRouteProvider::getPretixRouteName($entityType),
            'title' => $this->t('Dates'),
            'parent_id' => "os2uol_pretix.tasks:$entityTypeId.pretix"
          ] + $base_plugin_definition;

        $this->derivatives["$entityTypeId.pretix.emails"] = [
            'class' => PretixLocalTask::class,
            'route_name' => PretixRouteProvider::getPretixSettingsRouteName($entityType),
            'title' => $this->t('Settings'),
            'parent_id' => "os2uol_pretix.tasks:$entityTypeId.pretix"
          ] + $base_plugin_definition;
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
