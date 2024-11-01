<?php

namespace Drupal\os2uol_moderation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;

/**
 * Service for handling moderation logic.
 */
class ModerationService {

  protected $entityTypeManager;
  protected $configFactory;
  protected $logger;
  protected $emailService;

  /**
   * Constructs the ModerationService.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory, LoggerInterface $logger, EmailService $emailService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
    $this->emailService = $emailService;
  }

  /**
   * Runs the moderation process for nodes.
   */
  public function processModeration() {
    $config = $this->configFactory->get('os2uol_moderation.settings');
    $firstWarning = $config->get('first_warning') * 24 * 60 * 60;
    $secondWarning = $config->get('second_warning') * 24 * 60 * 60;
    $unpublishInterval = $config->get('unpublish_interval') * 24 * 60 * 60;

    // Query nodes that are published and marked as "Hele Året".
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('status', 1)
      ->accessCheck(FALSE);
    $nids = $query->execute();

    foreach ($nids as $nid) {
      $node = Node::load($nid);
      $owner = $node->getOwner();

      // Check if the owner has opted out of auto-unpublishing.
      if ($owner->hasField('field_disable_auto_unpublish') && $owner->get('field_disable_auto_unpublish')->value) {
        // Skip this node if the user has disabled auto-unpublishing.
        continue;
      }

      // Check if there is a field_period with an end date for automatic unpublishing.
      if ($node->hasField('field_period') && !$node->get('field_period')->isEmpty()) {
        $period_end = strtotime($node->get('field_period')->end_value);
        if ($period_end && time() > $period_end) {
          $this->unpublishNode($node);
          continue;
        }
      }

      // "Hele Året" logic: Unpublish 430 days after the last update, if marked as such.
      if ($node->hasField('field_all_year') && $node->get('field_all_year')->value == 1) {
        $lastUpdated = $node->getChangedTime();
        $timeSinceUpdate = time() - $lastUpdated;

        // Unpublish directly if 430 days have passed.
        if ($timeSinceUpdate >= $unpublishInterval) {
          $this->unpublishNode($node);
          continue;
        }

        // Send warning emails only if unpublishing conditions are not yet met.
        try {
          if ($timeSinceUpdate >= $firstWarning && $timeSinceUpdate < $secondWarning) {
            $this->emailService->sendNotification($owner, $node, 'first_warning');
          } elseif ($timeSinceUpdate >= $secondWarning && $timeSinceUpdate < $unpublishInterval) {
            $this->emailService->sendNotification($owner, $node, 'second_warning');
          }
        } catch (\Exception $e) {
          // Handle email failure silently to avoid impacting the unpublishing logic.
        }
      }
    }
  }

  /**
   * Helper function to unpublish a node by setting its moderation state.
   */
  protected function unpublishNode(Node $node) {
    try {
      if ($node->hasField('moderation_state')) {
        // Set the moderation state to 'unpublished'.
        $node->set('moderation_state', 'unpublished');
        $node->save();
      } else {
        // Fallback if the moderation state field is not available.
        $node->setUnpublished()->save();
      }
    } catch (\Exception $e) {
      \Drupal::logger('os2uol_moderation')->error('Failed to unpublish node @nid: @message', ['@nid' => $node->id(), '@message' => $e->getMessage()]);
    }
  }
}
