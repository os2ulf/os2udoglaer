<?php

namespace Drupal\os2uol_moderation\Entity;

use Drupal\content_moderation_notifications\Entity\ContentModerationNotification as BaseEntity;

/**
 * Extends the content_moderation_notification entity to add domain support.
 */
class ContentModerationNotification extends BaseEntity {

  /**
   * The domain associated with this notification.
   *
   * @var string|null
   */
  protected $domain;

  /**
   * Gets the domain.
   *
   * @return string|null
   *   The domain ID, or NULL if not set.
   */
  public function getDomain() {
    return $this->get('domain');
  }

  /**
   * Sets the domain.
   *
   * @param string|null $domain
   *   The domain ID.
   */
  public function setDomain($domain) {
    $this->set('domain', $domain);
  }
}
