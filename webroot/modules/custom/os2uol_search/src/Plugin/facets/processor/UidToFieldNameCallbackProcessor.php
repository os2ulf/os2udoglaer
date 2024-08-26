<?php

namespace Drupal\os2uol_search\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\processor\UidToUserNameCallbackProcessor;
use Drupal\user\Entity\User;

/**
 * Provides a processor that transforms the results to show the user's name.
 *
 * @FacetsProcessor(
 *   id = "uid_to_field_name_callback",
 *   label = @Translation("Transform UID to field_name"),
 *   description = @Translation("Display the custom user name if the source field is a user ID."),
 *   stages = {
 *     "build" = 5
 *   }
 * )
 */
class UidToFieldNameCallbackProcessor extends UidToUserNameCallbackProcessor {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $usernames = [];

    /** @var \Drupal\facets\Result\ResultInterface $result */
    foreach ($results as $result) {
      /** @var \Drupal\user\Entity\User $user */
      if (($user = User::load($result->getRawValue())) !== NULL) {
        // Get the field name of the user entity.
        $field_name = $user->get('field_name')->getString();

        $result->setDisplayValue($field_name);

        $facet->addCacheableDependency($user);
        $usernames[] = $result;
      }
    }

    return $usernames;
  }

}
