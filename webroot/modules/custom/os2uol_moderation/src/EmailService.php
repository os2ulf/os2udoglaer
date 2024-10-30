<?php

namespace Drupal\os2uol_moderation;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Service for handling email notifications.
 */
class EmailService {

  protected $mailManager;

  const FIRST_WARNING_TEMPLATE = 'os2uol_moderation_first_warning';
  const SECOND_WARNING_TEMPLATE = 'os2uol_moderation_second_warning';
  const UNPUBLISH_TEMPLATE = 'os2uol_moderation_unpublish';

  /**
   * Constructs a new EmailService.
   */
  public function __construct(MailManagerInterface $mailManager) {
    $this->mailManager = $mailManager;
  }

  /**
   * Sends an email notification.
   */
  public function sendNotification(User $user, Node $node, string $type) {
    $params = [
      'username' => $user->getDisplayName(),
      'node_title' => $node->getTitle(),
      'message' => $this->getMessage($node, $type),
    ];
    $template_key = match ($type) {
      'first_warning' => self::FIRST_WARNING_TEMPLATE,
      'second_warning' => self::SECOND_WARNING_TEMPLATE,
      'unpublish' => self::UNPUBLISH_TEMPLATE,
    };

    $this->mailManager->mail('os2uol_moderation', $template_key, $user->getEmail(), LanguageInterface::LANGCODE_DEFAULT, $params);
  }

  /**
   * Returns the message based on notification type.
   */
  protected function getMessage(Node $node, string $type): string {
    return "This is a test message for type: $type.";
  }
}
