<?php

namespace Drupal\os2uol_application_forms\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\os2uol_application_forms\Os2Notification;

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
      // Set status to accepted.
      $entity->set('moderation_state', 'refused');

      /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
      $session = \Drupal::service('session');

      $session->set(Os2Notification::NO_EMAIL_SESSION_VARIABLE, TRUE);
      $session->save();

      try {
        // Save the entity.
        $entity->save();
      } catch (\Throwable $e) {
        watchdog_exception('os2uol_application_forms', $e);
      } finally {
        $session->remove(Os2Notification::NO_EMAIL_SESSION_VARIABLE);
        $session->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }
}
