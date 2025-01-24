<?php

namespace Drupal\os2uol_application_forms;

use Drupal\content_moderation_notifications\Notification;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;

class Os2Notification extends Notification {

  const NO_EMAIL_SESSION_VARIABLE = 'os2_no_email';

  /**
   * {@inheritdoc}
   */
  public function processEntity(EntityInterface $entity) {
    // Ignore the email notification if the 'noEmail' session variable is set.
    if ($entity instanceof Node) {
      /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
      $session = \Drupal::service('session');

      if ($session->get(self::NO_EMAIL_SESSION_VARIABLE)) {
        $session->remove(self::NO_EMAIL_SESSION_VARIABLE);
        $session->save();
        return;
      }
    }

    parent::processEntity($entity);
  }
}
