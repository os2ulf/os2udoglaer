<?php

namespace Drupal\os2uol_pretix\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2uol_pretix\Plugin\Menu\LocalAction\PretixLocalAction;
use Drupal\os2uol_pretix\Routing\PretixRouteProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Pretix actions for entities.
 */
class PretixLocalActions extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  protected string $basePluginId;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected AccountInterface $currentUser;

  /**
   * Constructs a new PretixLocalActions object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(string $base_plugin_id, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->basePluginId = $base_plugin_id;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('current_user')
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

        // Add the "Add date" action only if the user has the required permission.
        $this->derivatives["$entityTypeId.add_pretix"] = [
          'class' => PretixLocalAction::class,
          'route_name' => 'os2uol_pretix.add_date',
          'appears_on' => ['entity.' . $entityTypeId . '.pretix'],
          'base_route' => "entity.$entityTypeId.pretix",
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
      }
    }

    return $this->derivatives;
  }

}
