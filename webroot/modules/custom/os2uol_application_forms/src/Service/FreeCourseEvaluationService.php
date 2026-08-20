<?php

declare(strict_types=1);

namespace Drupal\os2uol_application_forms\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Service for sending evaluation emails for free course requests.
 */
class FreeCourseEvaluationService {

  protected LoggerChannelInterface $logger;

  /**
   * Constructs the FreeCourseEvaluationService.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected DomainNegotiatorInterface $domainNegotiator,
    protected MailManagerInterface $mailManager,
    protected Token $token,
    protected Connection $database,
    LoggerChannelFactoryInterface $loggerChannelFactory,
  ) {
    $this->logger = $loggerChannelFactory->get('os2uol_application_forms');
  }

  /**
   * Process evaluation emails for the active domain.
   */
  public function processEvaluationEmails(): void {
    $domain = $this->domainNegotiator->getActiveDomain();

    if (!$domain) {
      $this->logger->error('Unable to determine active domain.');
      return;
    }

    $config = $this->configFactory->get('os2uol_settings.settings');

    if (empty($config->get('evaluation_email_enabled'))) {
      $this->logger->notice('Evaluation email disabled for domain @domain. Skipping.', [
        '@domain' => $domain->id(),
      ]);
      return;
    }

    $days = (int) ($config->get('evaluation_email_days') ?? 7);
    $subject = $config->get('evaluation_email_subject') ?? '';
    $body = $config->get('evaluation_email_body');

    if (empty($subject) || empty($body['value'])) {
      $this->logger->notice('Evaluation email subject or body is empty for domain @domain. Skipping.', [
        '@domain' => $domain->id(),
      ]);
      return;
    }

    $this->processForDomain($domain, $days, $subject, $body['value']);
  }

  /**
   * Process evaluation emails for a specific domain.
   */
  protected function processForDomain($domain, int $days, string $subject, string $bodyTemplate): void {
    // Calculate the target date: nodes where field_rfc_date + X days <= today.
    $targetDate = new \DateTime('now');
    $targetDate->modify("-{$days} days");
    $targetDateStr = $targetDate->format('Y-m-d');

    $nodeStorage = $this->entityTypeManager->getStorage('node');

    // Query for free_course_request nodes that:
    // 1. Belong to this domain
    // 2. Have moderation state "accepted" (Godkendt)
    // 3. Have field_rfc_date <= target date
    // 4. Have NOT been sent the follow-up mail yet (field_rfc_follow_up_mail_sent is empty)
    $query = $this->database->select('node_field_data', 'nfd');
    $query->fields('nfd', ['nid']);
    $query->join('node__field_domain_access', 'fda', 'nfd.nid = fda.entity_id');
    $query->join('content_moderation_state_field_data', 'cmsfd', 'nfd.nid = cmsfd.content_entity_id AND cmsfd.content_entity_type_id = :type', [':type' => 'node']);
    $query->join('node__field_rfc_date', 'rd', 'nfd.nid = rd.entity_id');
    $query->leftJoin('node__field_rfc_follow_up_mail_sent', 'fum', 'nfd.nid = fum.entity_id');
    $query->leftJoin('node__field_data_anonymized', 'fda2', 'nfd.nid = fda2.entity_id');

    $query->condition('nfd.type', 'free_course_request');
    $query->condition('fda.field_domain_access_target_id', $domain->id());
    $query->condition('cmsfd.moderation_state', 'accepted');
    $query->condition('rd.field_rfc_date_value', $targetDateStr, '<=');
    $query->isNull('fum.field_rfc_follow_up_mail_sent_value');

    // Exclude anonymized nodes.
    $orGroup = $query->orConditionGroup()
      ->isNull('fda2.field_data_anonymized_value')
      ->condition('fda2.field_data_anonymized_value', '1', '!=');
    $query->condition($orGroup);

    $nids = $query->execute()->fetchCol();

    if (empty($nids)) {
      $this->logger->info('No relevant free course requests found to send evaluation email for domain @domain.', [
        '@domain' => $domain->id(),
      ]);
      return;
    }

    $this->logger->info('Found @count free course requests to send evaluation email for domain @domain.', [
      '@count' => count($nids),
      '@domain' => $domain->id(),
    ]);

    $nodes = $nodeStorage->loadMultiple($nids);

    /** @var \Drupal\node\NodeInterface $node */
    foreach ($nodes as $node) {
      $email = $node->get('field_rfc_mail')->value;

      if (empty($email)) {
        $this->logger->warning('No email address found for free course request node @nid. Skipping.', [
          '@nid' => $node->id(),
        ]);
        continue;
      }

      // Replace tokens in subject and body.
      $tokenData = ['node' => $node, 'user' => $node->getOwner()];
      $processedSubject = $this->token->replace($subject, $tokenData);
      $processedBody = $this->token->replace($bodyTemplate, $tokenData);

      $params = [
        'subject' => $processedSubject,
        'content' => $processedBody,
      ];

      $result = $this->mailManager->mail(
        'os2uol_application_forms',
        'free_course_evaluation',
        $email,
        LanguageInterface::LANGCODE_DEFAULT,
        $params
      );

      if ($result['result']) {
        // Mark the follow-up mail as sent.
        $node->set('field_rfc_follow_up_mail_sent', \Drupal::time()->getRequestTime());
        // Skip content moderation notification
        $node->set('field_rfc_send_mail', '0');
        $node->save();

        $this->logger->info('Evaluation email sent for node @nid to @email.', [
          '@nid' => $node->id(),
          '@email' => $email,
        ]);
      }
      else {
        $this->logger->error('Failed to send evaluation email for node @nid to @email.', [
          '@nid' => $node->id(),
          '@email' => $email,
        ]);
      }
    }
  }

}

