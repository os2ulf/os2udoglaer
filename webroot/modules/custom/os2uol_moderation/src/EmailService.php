<?php

namespace Drupal\os2uol_moderation;

use Drupal\Core\Config\Config;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Utility\Token;
use Drupal\domain\Entity\Domain;
use Drupal\node\Entity\Node;
use Drupal\os2uol_domain\DomainConfigHelper;
use Drupal\os2uol_domain\Os2uolDomain;
use Drupal\user\Entity\User;

/**
 * Service for handling email notifications.
 */
class EmailService {

  protected ?Config $config = NULL;

  /**
   * Constructs a new EmailService.
   */
  public function __construct(
    protected MailManagerInterface $mailManager,
    protected DomainConfigHelper $domainConfigHelper,
    protected Token $token,
  ) {}

  /**
   * Sends an email notification.
   *
   * @param \Drupal\user\Entity\User $user
   * @param \Drupal\node\Entity\Node $node
   * @param string $type
   *
   * @throws \Exception
   */
  public function sendNotification(User $user, Node $node, string $type) {
    $domain = $this->getDomainFromNode($node);

    if (empty($domain)) {
      throw new \Exception("Node {$node->id()} does not have a proper domain assigned to it.");
    }

    $this->config = $this->domainConfigHelper->getDomainConfig('os2uol_moderation.email_settings', $domain);

    $params = [
      'user' => $user,
      'node' => $node,
      'subject' => $this->getEmailSubject($node, $type),
      'content' => $this->getEmailContent($node, $type),
    ];

    $this->mailManager->mail('os2uol_moderation', $type, $user->getEmail(), LanguageInterface::LANGCODE_DEFAULT, $params);
  }

  /**
   * Get email subject.
   *
   * @param \Drupal\node\Entity\Node $node
   * @param string $type
   *
   * @return string
   */
  protected function getEmailSubject(Node $node, string $type): string {
    $subject = $this->config->get($type . '_subject');

    if (empty($subject)) {
      throw new \Exception("Subject for $type email is empty.");
    }

    $subject = $this->token->replace($subject, [
      'node' => $node,
    ]);

    return $subject;
  }

  /**
   * Get email content.
   *
   * @param \Drupal\node\Entity\Node $node
   * @param string $type
   *
   * @return string
   */
  protected function getEmailContent(Node $node, string $type): string {
    $message = $this->config->get($type . '_email');

    if (empty($message)) {
      throw new \Exception("Email template for $type is empty.");
    }

    $message = $this->token->replace($message, [
      'node' => $node,
    ]);

    return $message;
  }

  /**
   * Get domain from node. Excludes default domain.
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return \Drupal\domain\Entity\Domain|null
   */
  protected function getDomainFromNode(Node $node): ?Domain {
    $domains = array_column($node->get('field_domain_access')->getValue(), 'target_id');

    if (empty($domains)) {
      return NULL;
    }

    // Unset default domain
    foreach ($domains as $key => $domain) {
      if ($domain == Os2uolDomain::DEFAULT_DOMAIN_ID) {
        unset($domains[$key]);
      }
    }

    if (empty($domains)) {
      return NULL;
    }

    $domain_id = reset($domains);

    return Domain::load($domain_id);
  }
}
