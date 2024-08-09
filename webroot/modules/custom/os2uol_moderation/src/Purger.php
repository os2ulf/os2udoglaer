<?php

namespace Drupal\os2uol_moderation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\Entity\Node;

class Purger {

  protected $entityTypeManager;
  protected $dateFormatter;
  protected $logger;
  protected $database;
  protected $configFactory;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, DateFormatterInterface $dateFormatter, LoggerInterface $logger, Connection $database, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->dateFormatter = $dateFormatter;
    $this->logger = $logger;
    $this->database = $database;
    $this->configFactory = $configFactory;
  }

  public function purgeOldTrashNodes() {
    $config = $this->configFactory->get('os2uol_moderation.settings');
    $days = $config->get('days') ?? 30;
    $interval = strtotime("-$days days");

    $connection = $this->database;

    // Define the query with a join to the node_field_data table.
    $query = $connection->select('content_moderation_state_field_data', 'cmsfd')
      ->fields('cmsfd', ['content_entity_id'])
      ->condition('cmsfd.moderation_state', 'trash')
      ->condition('nfd.changed', $interval, '<');

    // Join with the node_field_data table.
    $query->join('node_field_data', 'nfd', 'cmsfd.content_entity_id = nfd.nid');
    $this->logger->info('Query conditions set with content_moderation_state_field_data and node_field_data.');

    try {
      $result = $query->execute();
      $nids = $result->fetchCol(0);
      $this->logger->info('Query executed. Found nodes: @nids', ['@nids' => implode(', ', $nids)]);
    } catch (\Exception $e) {
      $this->logger->error('Query execution failed: @message', ['@message' => $e->getMessage()]);
      return;
    }

    if (empty($nids)) {
      $this->logger->info('No nodes found in trash older than @days days.', ['@days' => $days]);
      return;
    } else {
      $this->logger->info('Found nodes to delete: @nids', ['@nids' => implode(', ', $nids)]);
    }

    // Load and delete nodes.
    $nodes = Node::loadMultiple($nids);
    foreach ($nodes as $node) {
      $this->logger->info('Attempting to delete node @nid.', ['@nid' => $node->id()]);
      $node->delete();
      $this->logger->info('Deleted node @nid from trash.', ['@nid' => $node->id()]);
    }
  }
}
