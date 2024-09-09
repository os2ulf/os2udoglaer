<?php

namespace Drupal\os2uol_search\Plugin\facets\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\processor\UidToUserNameCallbackProcessor;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Provides a processor that transforms the results to show the user's name.
 *
 * @FacetsProcessor(
 *   id = "user_role_filter",
 *   label = @Translation("Filter by user role"),
 *   description = @Translation("Filter users based on their role."),
 *   stages = {
 *     "build" = 2
 *   }
 * )
 */
class UserRoleFilter extends UidToUserNameCallbackProcessor {

  public function build(FacetInterface $facet, array $results) {
    $allowed_roles = $this->getConfiguration()['roles'];

    /** @var \Drupal\facets\Result\ResultInterface $result */
    foreach ($results as $key => $result) {
      /** @var \Drupal\user\Entity\User $user */
      if (($user = User::load($result->getRawValue())) !== NULL) {
        $user_roles = $user->getRoles();
        $intersect = array_intersect($allowed_roles, $user_roles);

        if (empty($intersect)) {
          unset($results[$key]);
        }
      }
    }

    return $results;
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $config = $this->getConfiguration();

    $build['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#options' => $this->getRoles(),
      '#default_value' => $config['roles'],
      '#description' => $this->t('Select the roles to filter by.'),
    ];

    return $build;
  }

  public function getRoles() {
    $roles = Role::loadMultiple();

    $options = [];

    foreach ($roles as $role) {
      $options[$role->id()] = $role->label();
    }

    return $options;
  }

}
