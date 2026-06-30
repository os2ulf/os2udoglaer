<?php

declare(strict_types=1);

namespace Drupal\os2uol_moderation;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

class Purger {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected Connection $database,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  public function purgeOldTrashNodes(): void {
    $config = $this->configFactory->get('os2uol_moderation.settings');
    $days = (int) ($config->get('days') ?: 30);
    $threshold = strtotime(sprintf('-%d days', $days));

    $this->logger->info('Trash purge started. Purge days: @days.', [
      '@days' => $days,
    ]);

    $connection = $this->database;

    $query = $connection->select('content_moderation_state_field_revision', 'cmsfr')
      ->distinct()
      ->fields('cmsfr', ['content_entity_id'])
      ->condition('cmsfr.content_entity_type_id', 'node')
      ->condition('cmsfr.moderation_state', 'trash')
      ->condition('nr.revision_timestamp', $threshold, '<=');

    // Only purge nodes whose current/default revision is the old trash revision.
    $query->join('node_field_data', 'nfd', 'cmsfr.content_entity_id = nfd.nid AND cmsfr.content_entity_revision_id = nfd.vid AND cmsfr.langcode = nfd.langcode');
    $query->join('node_revision', 'nr', 'nfd.vid = nr.vid');

    try {
      $result = $query->execute();
      $nids = $result->fetchCol(0);
    } catch (\Exception $e) {
      $this->logger->error('Query execution failed: @message', ['@message' => $e->getMessage()]);
      return;
    }

    if (empty($nids)) {
      $this->logger->notice('Trash purge completed. No nodes found in trash older than @days days.', [
        '@days' => $days,
      ]);
      return;
    } else {
      $this->logger->notice('Trash purge found @count nodes to delete: @nids', [
        '@count' => count($nids),
        '@nids' => implode(', ', $nids),
      ]);
    }

    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    foreach ($nodes as $node) {
      $this->logger->info('Attempting to delete node @nid.', ['@nid' => $node->id()]);
      $node->delete();
      $this->logger->info('Deleted node @nid from trash.', ['@nid' => $node->id()]);
    }

    $this->logger->info('Trash purge completed. Deleted @count nodes.', [
      '@count' => count($nodes),
    ]);
  }
}
