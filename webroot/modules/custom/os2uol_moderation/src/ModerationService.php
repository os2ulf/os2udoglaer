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

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('field_all_year', 1)
      ->condition('status', 1)
      ->accessCheck(FALSE);
    $nids = $query->execute();

    foreach ($nids as $nid) {
      $node = Node::load($nid);
      $lastUpdated = $node->getChangedTime();
      $timeSinceUpdate = time() - $lastUpdated;

      $owner = $node->getOwner();
      if ($owner->hasField('disable_auto_unpublish') && $owner->get('disable_auto_unpublish')->value) {
        continue;
      }

      if ($timeSinceUpdate >= $firstWarning && $timeSinceUpdate < $secondWarning) {
        $this->emailService->sendNotification($owner, $node, 'first_warning');
      } elseif ($timeSinceUpdate >= $secondWarning && $timeSinceUpdate < $unpublishInterval) {
        $this->emailService->sendNotification($owner, $node, 'second_warning');
      } elseif ($timeSinceUpdate >= $unpublishInterval) {
        $this->emailService->sendNotification($owner, $node, 'unpublish');
        $node->setUnpublished();
        $node->save();
      }
    }
  }
}
