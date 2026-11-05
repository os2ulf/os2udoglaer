<?php

namespace Drupal\view_profiles_perms;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Provides the permissions for view_profile_perms module.
 */
class ViewProfilesPermsPermissions {

  use StringTranslationTrait;

  /**
   * Get view profiles permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    // Generate permissions for each user role except authenticated and
    // anonymous.
    $permissions = [];
    /* @var $roles \Drupal\user\RoleInterface[] */
    $roles = Role::loadMultiple();
    unset($roles[RoleInterface::ANONYMOUS_ID], $roles[RoleInterface::AUTHENTICATED_ID]);
    if (count($roles) < 1) {
      return $permissions;
    }

    foreach ($roles as $role) {
      $role_name = $role->label();
      $role_id = $role->id();
      $permissions["access $role_id users profiles"] = [
        'title' => $this->t("Access %role_name users profiles", ['%role_name' => $role_name]),
      ];
    }

    return $permissions;
  }

}
