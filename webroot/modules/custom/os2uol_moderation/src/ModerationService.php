<?php

namespace Drupal\os2uol_moderation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\Entity\Node;
use Drupal\os2uol_domain\Os2uolDomain;
use Psr\Log\LoggerInterface;

/**
 * Service for handling moderation logic.
 */
class ModerationService {

  protected $results;

  /**
   * Constructs the ModerationService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\os2uol_moderation\EmailService $emailService
   *   The email service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected EmailService $emailService,
    protected LoggerInterface $logger
  ) {
    $this->results = [
      'skipped' => [],
      'unpublished' => [],
      'warnings' => [],
      'errors' => [],
    ];
  }

  /**
   * Runs the moderation process for nodes.
   */
  public function processModeration() {
    $config = $this->configFactory->get('os2uol_moderation.settings');
    $unpublishInterval = $config->get('unpublish_interval') * 24 * 60 * 60;

    /** @var \Drupal\domain\DomainNegotiator $domain_negotiator */
    $domain_negotiator = \Drupal::service('domain.negotiator');

    $domain = $domain_negotiator->getActiveDomain();

    if ($domain->id() == Os2uolDomain::DEFAULT_DOMAIN_ID) {
      $this->logger->warning('Can not run due to default domain being currently used');
      return;
    }

    // Start moderation process.
    $this->logger->notice('Moderation process started for domain @domain', [
      '@domain' => $domain->label(),
    ]);

    try {
      // Query published nodes.
      $nids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('status', 1)
        ->condition('field_domain_access', $domain->id())
        ->accessCheck(FALSE)
        ->execute();

      foreach ($nids as $nid) {
        $node = Node::load($nid);
        if (!$node) {
          $this->results['errors'][] = "Node $nid could not be loaded.";
          continue;
        }

        $this->processNode($node, $unpublishInterval);
      }
    } catch (\Throwable $throwable) {
      watchdog_exception('os2uol_moderation', $throwable);
    }

    $this->logSummary();
  }

  /**
   * Processes a single node for moderation.
   */
  protected function processNode(Node $node, int $unpublishInterval) {
    $owner = $node->getOwner();
    $nid = $node->id();

    // Skip nodes if "Automatisk afpublicering" is disabled.
    if ($owner->hasField('field_disable_auto_unpublish') && !$owner->get('field_disable_auto_unpublish')->value) {
      $this->results['skipped'][] = [
        'nid' => $nid,
        'uid' => $owner->id(),
      ];
      return;
    }

    // Prioritize "Hele Året" logic first.
    if ($node->hasField('field_all_year') && $node->get('field_all_year')->value == 1) {
      // Ignore `field_period`, only apply time-based unpublishing.
      if ($this->checkAllYear($node, $unpublishInterval)) {
        return;
      }
    } else {
      // If "Hele Året" is not enabled, check `field_period`.
      if ($this->checkFieldPeriod($node)) {
        return;
      }
    }
  }

  /**
   * Handles the field_period logic (only used if "Hele Året" is disabled).
   */
  protected function checkFieldPeriod(Node $node): bool {
    if ($node->hasField('field_period') && !$node->get('field_period')->isEmpty()) {
      $periodEndString = $node->get('field_period')->end_value;
      // Clear time part for accurate comparison.
      $periodEndString = explode('T', $periodEndString)[0];

      $periodEnd = strtotime($periodEndString);

      // Add one day to ensure the content is available throughout the end date.
      $oneDayInSeconds = 24 * 60 * 60;
      $periodEnd += $oneDayInSeconds;

      if ($periodEnd && time() > $periodEnd) {
        $this->unpublishNode($node, 'field_period');
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Handles "Hele Året" unpublishing logic.
   */
  protected function checkAllYear(Node $node, int $unpublishInterval): bool {
    $config = $this->configFactory->get('os2uol_moderation.settings');
    $firstWarning = $config->get('first_warning') * 24 * 60 * 60;
    $secondWarning = $config->get('second_warning') * 24 * 60 * 60;

    $lastUpdated = $node->getChangedTime();
    $timeSinceUpdate = time() - $lastUpdated;
    $nid = $node->id();

    if ($timeSinceUpdate >= $unpublishInterval) {
      $this->unpublishNode($node, 'time_since_update');
      return TRUE;
    }

    $this->sendWarningEmail($node, $timeSinceUpdate, $firstWarning, $secondWarning, $unpublishInterval);
    return FALSE;
  }

  /**
   * Sends warning emails based on time since last update.
   */
  protected function sendWarningEmail(Node $node, int $timeSinceUpdate, int $firstWarning, int $secondWarning, int $unpublishInterval): void {
    try {
      $owner = $node->getOwner();
      $email = $owner->getEmail();
      if (!$email) {
        return;
      }

      if ($timeSinceUpdate >= $firstWarning && $timeSinceUpdate < $secondWarning) {
        $this->emailService->sendNotification($owner, $node, 'first_warning');
        $this->results['warnings'][] = [
          'nid' => $node->id(),
          'type' => 'first_warning',
        ];
      } elseif ($timeSinceUpdate >= $secondWarning && $timeSinceUpdate < $unpublishInterval) {
        $this->emailService->sendNotification($owner, $node, 'second_warning');
        $this->results['warnings'][] = [
          'nid' => $node->id(),
          'type' => 'second_warning',
        ];
      }
    } catch (\Exception $e) {
      $this->results['errors_warning_email'][] = "Failed to send email for node {$node->id()}: {$e->getMessage()}";
    }
  }

  /**
   * Unpublishes a node and logs the result.
   */
  protected function unpublishNode(Node $node, string $reason) {
    try {
      if ($node->hasField('moderation_state')) {
        $node->set('moderation_state', 'unpublished');
      }
      $node->setPublished(FALSE);
      $node->setNewRevision(TRUE);
      $node->isDefaultRevision(TRUE);
      $node->save();

      $this->results['unpublished'][] = [
        'nid' => $node->id(),
        'reason' => $reason,
      ];
    } catch (\Exception $e) {
      $this->results['errors_unpublishing'][] = "Failed to unpublish node {$node->id()}: {$e->getMessage()}";
    }
  }

  /**
   * Logs a summary of the moderation process.
   */
  protected function logSummary() {
    if (!empty($this->results['unpublished'])) {
      $this->logger->info('Unpublished @count nodes.', [
        '@count' => count($this->results['unpublished']),
      ]);
    }

    if (!empty($this->results['skipped'])) {
      $this->logger->info('Skipped @count nodes due to "Automatisk afpublicering" being disabled.', [
        '@count' => count($this->results['skipped']),
      ]);
    }

    if (!empty($this->results['errors_warning_email'])) {
      $this->logger->error('Encountered errors with warning email in @count nodes.', [
        '@count' => count($this->results['errors_warning_email']),
      ]);
      $this->logger->error($this->results['errors_warning_email'][0]);
    }

    if (!empty($this->results['errors_unpublishing'])) {
      $this->logger->error('Encountered errors unpublishing @count nodes.', [
        '@count' => count($this->results['errors_unpublishing']),
      ]);
      $this->logger->error($this->results['errors_unpublishing'][0]);
    }
  }
}
