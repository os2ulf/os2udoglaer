<?php

declare(strict_types = 1);

namespace Drupal\os2uol_pretix;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\os2uol_pretix\Form\PretixSettingsForm;
use Drupal\os2uol_pretix\Form\PretixSubEventAddForm;
use Drupal\os2uol_pretix\Form\PretixOverviewForm;
use Drupal\os2uol_pretix\Routing\PretixRouteProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity related hooks for Scheduled Transitions module.
 */
class PretixEntityHooks implements ContainerInjectionInterface {

  /**
   * Array of IDs of Entity types using content moderation workflows.
   *
   * @var string[]
   */
  protected array $moderatedEntityTypes;

  /**
   * Constructs a new ScheduledTransitionsEntityHooks.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderationInformation
   *   General service for moderation-related questions about Entity API.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModerationInformationInterface $moderationInformation,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('content_moderation.moderation_information'),
    );
  }

  /**
   * Implements hook_entity_type_build().
   *
   * @see \os2uol_pretix_entity_type_build()
   */
  public function entityTypeBuild(array &$entityTypes): void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entityTypes */
    foreach ($entityTypes as $entityType) {
      if ($entityType->id() !== 'node') {
        continue;
      }
      if (!$entityType->hasLinkTemplate('canonical') || !$entityType instanceof ContentEntityType) {
        continue;
      }

      // Add our entity route provider.
      $routeProviders = $entityType->getRouteProviderClasses() ?: [];
      $routeProviders['pretix'] = PretixRouteProvider::class;
      $entityType->setHandlerClass('route_provider', $routeProviders);

      $canonicalPath = $entityType->getLinkTemplate('canonical');
      $entityType
        ->setFormClass(PretixRouteProvider::FORM, PretixOverviewForm::class)
        ->setLinkTemplate(PretixRouteProvider::LINK_TEMPLATE, $canonicalPath . PretixRouteProvider::CANONICAL_PATH_SUFFIX);

      $entityType
        ->setFormClass(PretixRouteProvider::FORM_ADD, PretixSubEventAddForm::class)
        ->setLinkTemplate(PretixRouteProvider::LINK_TEMPLATE_ADD, $canonicalPath . PretixRouteProvider::CANONICAL_PATH_SUFFIX_ADD);

      $entityType
        ->setFormClass(PretixRouteProvider::FORM_SETTINGS, PretixSettingsForm::class)
        ->setLinkTemplate(PretixRouteProvider::LINK_TEMPLATE_SETTINGS, $canonicalPath . PretixRouteProvider::CANONICAL_PATH_SUFFIX_SETTINGS);
    }
  }

  /**
   * Implements hook_entity_delete().
   *
   * @see \os2uol_pretix_entity_delete()
   */
  public function entityDelete(EntityInterface $entity): void {
    // TODO: Nice to have feature to delete events in Pretix as well
    /*$transitionStorage = $this->entityTypeManager->getStorage('scheduled_transition');
    $transitionsForEntity = $this->loadByHostEntity($entity);
    $transitionStorage->delete($transitionsForEntity);*/
  }

  /**
   * Implements hook_entity_insert().
   *
   * @see \os2uol_pretix_entity_insert()
   */
  public function entityInsert(EntityInterface $entity): void {
    $this->entityUpdate($entity);
  }

  /**
   * Implements hook_entity_update().
   *
   * @see \os2uol_pretix_entity_update()
   */
  public function entityUpdate(EntityInterface $entity): void {
    if ($entity->getEntityTypeId() == 'user') {
      /** @var \Drupal\user\UserInterface $user */
      $user = $entity;
      /** @var \Drupal\os2uol_pretix\PretixOrderManager $orderManager */
      $orderManager = \Drupal::service('os2uol_pretix.order_manager');
      $orderManager->ensureWebhook($user);
    } else {
      /** @var \Drupal\os2uol_pretix\PretixEventManager $eventManager */
      $eventManager = \Drupal::service('os2uol_pretix.event_manager');
      if ($eventManager->isPretixEventEntity($entity)) {
        /** @var \Drupal\Core\Entity\EditorialContentEntityBase $editorialEntity */
        $editorialEntity = $entity;
        $result = $eventManager->setEventLive($editorialEntity);
      }
    }
  }

  /**
   * Implements hook_entity_access().
   *
   * @see \os2uol_pretix_entity_access()
   */
  public function entityAccess(EntityInterface $entity, string $operation, AccountInterface $account): AccessResultInterface {
    // Determines if a user has access to Pretix events for
    // an entity.
    $access = AccessResult::neutral();


    if ($operation === PretixRouteProvider::ENTITY_OPERATION_VIEW) {
      /*if (!$entity instanceof EditorialContentEntityBase) {
        return AccessResult::forbidden('Bundle does not support Pretix');
      } else {
        if (!$entity->hasField('field_pretix_template_event') || !$entity->hasField('field_pretix_event_short_form')) {
          return AccessResult::forbidden('Bundle does not support Pretix');
        }
      }*/

      $access->cachePerPermissions();
      if ($account->hasPermission(PretixRouteProvider::ALL_PERMISSION)) {
        $access = AccessResult::allowed();
      }
      elseif ($account->hasPermission(PretixRouteProvider::OWN_PERMISSION)) {
        $access->addCacheTags($entity->getCacheTagsToInvalidate());
        $access = AccessResult::allowed();
      }
      else {
        $access = $access->andIf(AccessResult::forbidden());
      }
    }

    return $access;
  }

}
