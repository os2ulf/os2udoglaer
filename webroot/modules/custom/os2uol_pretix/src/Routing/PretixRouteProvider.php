<?php

declare(strict_types = 1);

namespace Drupal\os2uol_pretix\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route provider for host entities of Scheduled Transitions.
 */
class PretixRouteProvider implements EntityRouteProviderInterface {

  /**
   * Name of the link template for scheduled transitions form.
   *
   * Link template for scheduled transitions. This should not exist if the
   * entity does not have a canonical template.
   */
  public const LINK_TEMPLATE = 'pretix';

  public const FORM = 'pretix_entity_form';

  public const CANONICAL_PATH_SUFFIX = '/pretix';

  public const LINK_TEMPLATE_ADD = 'pretix_add';

  public const FORM_ADD = 'pretix_add_form';

  public const CANONICAL_PATH_SUFFIX_ADD = '/pretix/add';

  public const LINK_TEMPLATE_SETTINGS = 'pretix_settings';

  public const FORM_SETTINGS = 'pretix_settings';

  public const CANONICAL_PATH_SUFFIX_SETTINGS = '/pretix/settings';

  public const ROUTE_ENTITY_TYPE = '_pretix_entity_type';

  public const ALL_PERMISSION = 'edit all pretix events';

  public const OWN_PERMISSION = 'edit own pretix events';

  public const ENTITY_OPERATION_VIEW = 'view pretix events';

  public const ENTITY_OPERATION_EDIT = 'edit pretix events';

  public const ENTITY_OPERATION_ADD = 'add own pretix events';

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();
    $entityTypeId = $entity_type->id();

    if ($entity_type->hasLinkTemplate(static::LINK_TEMPLATE)) {
      $path = $entity_type->getLinkTemplate('canonical') . static::CANONICAL_PATH_SUFFIX;
      $route = (new Route($path))
        ->addDefaults([
          '_title' => 'Pretix',
          '_entity_form' => $entityTypeId . '.' . static::FORM,
        ])
        // @todo Better permissions
        ->setRequirement('_entity_access', $entityTypeId . '.' . static::ENTITY_OPERATION_VIEW)
        ->setOption('_admin_route', TRUE);
      $collection->add(static::getPretixRouteName($entity_type), $route);
    }

    if ($entity_type->hasLinkTemplate(static::LINK_TEMPLATE_ADD)) {
      $path = $entity_type->getLinkTemplate('canonical') . static::CANONICAL_PATH_SUFFIX_ADD;
      $route = (new Route($path))
        ->addDefaults([
          '_title' => 'Add Pretix date',
          '_entity_form' => $entityTypeId . '.' . static::FORM_ADD,
        ])
        // @todo Better permissions
        ->setRequirement('_entity_access', $entityTypeId . '.' . static::ENTITY_OPERATION_ADD)
        ->setOption('_admin_route', TRUE);
      $collection->add(static::getPretixAddRouteName($entity_type), $route);
    }

    if ($entity_type->hasLinkTemplate(static::LINK_TEMPLATE_SETTINGS)) {
      $path = $entity_type->getLinkTemplate('canonical') . static::CANONICAL_PATH_SUFFIX_SETTINGS;
      $route = (new Route($path))
        ->addDefaults([
          '_title' => 'Edit Pretix emails',
          '_entity_form' => $entityTypeId . '.' . static::FORM_SETTINGS,
        ])
        // @todo Better permissions
        ->setRequirement('_entity_access', $entityTypeId . '.' . static::ENTITY_OPERATION_EDIT)
        ->setOption('_admin_route', TRUE);
      $collection->add(static::getPretixSettingsRouteName($entity_type), $route);
    }

    return $collection;
  }

  /**
   * Get the route name for Pretix form for an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   An entity type.
   *
   * @return string
   *   The route name.
   */
  public static function getPretixRouteName(EntityTypeInterface $entityType): string {
    return sprintf('entity.%s.pretix', $entityType->id());
  }

  /**
   * Get the route name for Pretix settings form for an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   An entity type.
   *
   * @return string
   *   The route name.
   */
  public static function getPretixSettingsRouteName(EntityTypeInterface $entityType): string {
    return sprintf('entity.%s.pretix_settings', $entityType->id());
  }

  /**
   * Get the route name for scheduled transition form for an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   An entity type.
   *
   * @return string
   *   The route name.
   */
  public static function getPretixAddRouteName(EntityTypeInterface $entityType): string {
    return sprintf('entity.%s.pretix_add', $entityType->id());
  }

}
