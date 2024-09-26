<?php

declare(strict_types = 1);

namespace Drupal\os2uol_pretix\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2uol_pretix\Plugin\Menu\LocalAction\PretixLocalAction;
use Drupal\os2uol_pretix\Routing\PretixRouteProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pretix actions for entities.
 */
class PretixLocalActions extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  protected string $basePluginId;
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Creates a new ScheduledTransitionsLocalActions.
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

        // Add the "Add date" action
        $this->derivatives["$entityTypeId.add_pretix"] = [
          'route_name' => PretixRouteProvider::getPretixAddRouteName($entityType),
          'appears_on' => [PretixRouteProvider::getPretixRouteName($entityType)],
          'base_route' => "entity.$entityTypeId.canonical",
          'class' => PretixLocalAction::class,
          'title' => $this->t('Add date'),
          'options' => [
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => '80%',
              ]),
            ],
          ],
        ] + $base_plugin_definition;

        // Add the "Remove event connection" action
        $this->derivatives["$entityTypeId.remove_pretix"] = [
          'route_name' => 'os2uol_pretix.remove_event_connection',
          'appears_on' => [PretixRouteProvider::getPretixRouteName($entityType)],
          'base_route' => "entity.$entityTypeId.canonical",
          'class' => PretixLocalAction::class,
          'title' => $this->t('Remove event connection'),
          'options' => [
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => '50%',
              ]),
            ],
          ],
        ] + $base_plugin_definition;
      }
    }

    return $this->derivatives;
  }

}
