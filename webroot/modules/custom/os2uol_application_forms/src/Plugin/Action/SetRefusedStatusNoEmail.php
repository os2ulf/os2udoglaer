<?php

namespace Drupal\os2uol_application_forms\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a 'Set status to Refused (No Email)' action.
 *
 * @Action(
 *   id = "set_refused_status_no_email",
 *   label = @Translation("Set status to Refused (No Email)"),
 *   type = "node"
 * )
 */
class SetRefusedStatusNoEmail extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity instanceof NodeInterface) {

      // Check if the entity has the 'field_rfc_send_mail' field.
      if ($entity->hasField('field_rfc_send_mail')) {
        // Check if email sending is disabled.
        $send_mail = $entity->get('field_rfc_send_mail')->value;

        // Set status to refused without triggering email if send_mail is false.
        if (!$send_mail) {
          // Set status to refused.
          $entity->set('moderation_state', 'refused');

          // Save without triggering notifications.
          \Drupal::service('content_moderation_notifications.manager')->stopNotification($entity);
        }
      }

      // Save the entity.
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }
}
