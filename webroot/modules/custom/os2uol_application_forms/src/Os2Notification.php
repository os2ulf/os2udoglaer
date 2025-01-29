<?php

namespace Drupal\os2uol_application_forms;

use Drupal\content_moderation_notifications\Notification;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\SynchronizableInterface;
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

    //======================================================================
    // Code from parent class function below, except for domain check.
    // Keep this code in sync with the parent class.
    //======================================================================

    // Never process entities that syncing (for example, during a migration).
    if ($entity instanceof SynchronizableInterface && $entity->isSyncing()) {
      return;
    }

    $notifications = $this->notificationInformation->getNotifications($entity);

    // Our only custom line of code here
    // Filter notifications based on domain.
    $this->filterNotificationsBasedOnDomain($notifications, $entity);

    if (!empty($notifications)) {
      $this->sendNotification($entity, $notifications);
    }
  }

  /**
   * Filters notifications based on domain.
   *
   * @param \Drupal\content_moderation_notifications\ContentModerationNotificationInterface[] $notifications
   *   The notifications to filter.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to filter notifications for.
   */
  protected function filterNotificationsBasedOnDomain(array &$notifications,EntityInterface $entity): void {
    if (empty($notifications)) {
      return;
    }

    // Only process nodes.
    if (!$entity instanceof Node) {
      return;
    }

    // Get the domains of the node.
    $node_domains = array_column($entity->get('field_domain_access')->getValue(), 'target_id');

    foreach ($notifications as $key => $notification) {
      // Skip notifications without domain.
      if (empty($notification->domain)) {
        continue;
      }

      if (!in_array($notification->domain, $node_domains)) {
        unset($notifications[$key]);
      }
    }
  }
}
