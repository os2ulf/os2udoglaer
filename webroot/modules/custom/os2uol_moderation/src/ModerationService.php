<?php

namespace Drupal\os2uol_moderation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\Entity\Node;

/**
 * Service for handling moderation logic.
 */
class ModerationService {

  protected $entityTypeManager;
  protected $configFactory;
  protected $emailService;

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
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory, EmailService $emailService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->emailService = $emailService;
    $this->results = [
      'skipped' => [],
      'unpublished' => [],
      'no_action' => [],
      'errors' => [],
    ];
  }

  /**
   * Runs the moderation process for nodes.
   */
  public function processModeration() {
    $config = $this->configFactory->get('os2uol_moderation.settings');
    $unpublishInterval = $config->get('unpublish_interval') * 24 * 60 * 60;

    // Start moderation process.
    \Drupal::logger('os2uol_moderation')->notice('Moderation process started.');

    // Query published nodes.
    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('status', 1)
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

    $this->logSummary();
  }

  /**
   * Processes a single node for moderation.
   */
  protected function processNode(Node $node, int $unpublishInterval) {
    $owner = $node->getOwner();

    // Skip nodes if "Automatisk afpublicering" is disabled.
    if ($owner->hasField('field_disable_auto_unpublish') && !$owner->get('field_disable_auto_unpublish')->value) {
      $this->results['skipped'][] = [
        'nid' => $node->id(),
        'uid' => $owner->id(),
      ];
      return;
    }

    // Check for field_period logic.
    if ($this->checkFieldPeriod($node)) {
      return;
    }

    // Check for "Hele Året" logic.
    if ($this->checkAllYear($node, $unpublishInterval)) {
      return;
    }

    // If no actions were needed, add to "no action" list.
    $this->results['no_action'][] = $node->id();
  }

  /**
   * Handles the field_period logic.
   */
  protected function checkFieldPeriod(Node $node): bool {
    if ($node->hasField('field_period') && !$node->get('field_period')->isEmpty()) {
      $periodEnd = strtotime($node->get('field_period')->end_value);

      if ($periodEnd && time() > $periodEnd) {
        $this->unpublishNode($node, 'field_period');
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Handles the "Hele Året" logic.
   */
  protected function checkAllYear(Node $node, int $unpublishInterval): bool {
    $config = $this->configFactory->get('os2uol_moderation.settings');
    $firstWarning = $config->get('first_warning') * 24 * 60 * 60;
    $secondWarning = $config->get('second_warning') * 24 * 60 * 60;

    if ($node->hasField('field_all_year') && $node->get('field_all_year')->value == 1) {
        $lastUpdated = $node->getChangedTime();
        $timeSinceUpdate = time() - $lastUpdated;

        // If the unpublish interval is exceeded, unpublish the node.
        if ($timeSinceUpdate >= $unpublishInterval) {
            $this->unpublishNode($node, 'time_since_update');
            return TRUE;
        }

        // Handle warning emails.
        $this->sendWarningEmail($node, $timeSinceUpdate, $firstWarning, $secondWarning, $unpublishInterval);
    }
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
              $this->results['errors'][] = "Node {$node->id()} skipped: No email for user {$owner->id()}.";
              return;
          }

          // Determine which warning to send.
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
          $this->results['errors'][] = "Failed to send email for node {$node->id()}: {$e->getMessage()}";
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

      // Record the unpublished node.
      $this->results['unpublished'][] = [
        'nid' => $node->id(),
        'reason' => $reason,
      ];
    } catch (\Exception $e) {
      $this->results['errors'][] = "Failed to unpublish node {$node->id()}: {$e->getMessage()}";
    }
  }

  /**
   * Logs a summary of the moderation process.
   */
  protected function logSummary() {
    // Log skipped nodes.
    if (!empty($this->results['skipped'])) {
      \Drupal::logger('os2uol_moderation')->notice('Skipped @count nodes due to "Automatisk afpublicering" being disabled.', [
        '@count' => count($this->results['skipped']),
      ]);
    }

    // Log unpublished nodes.
    if (!empty($this->results['unpublished'])) {
      \Drupal::logger('os2uol_moderation')->notice('Unpublished @count nodes. Details: @details', [
        '@count' => count($this->results['unpublished']),
        '@details' => json_encode($this->results['unpublished']),
      ]);
    }

    // Log nodes requiring no action.
    if (!empty($this->results['no_action'])) {
      \Drupal::logger('os2uol_moderation')->notice('No actions needed for @count nodes.', [
        '@count' => count($this->results['no_action']),
      ]);
    }

    // Log errors if any occurred.
    if (!empty($this->results['errors'])) {
      \Drupal::logger('os2uol_moderation')->error('Encountered errors with @count nodes. Details: @details', [
        '@count' => count($this->results['errors']),
        '@details' => json_encode($this->results['errors']),
      ]);
    }
  }
}
