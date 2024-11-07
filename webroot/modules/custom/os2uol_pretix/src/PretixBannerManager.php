<?php

namespace Drupal\os2uol_pretix;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactoryInterface;
use Drupal\Core\Queue\QueueInterface;

class PretixBannerManager {

  /**
   * Cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Pretix event manager for retrieving data from Pretix.
   *
   * @var PretixEventManager
   */
  protected PretixEventManager $eventManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $connection;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs an AliasManager.
   *
   * @param PretixEventManager $event_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   */
  public function __construct(PretixEventManager $event_manager, CacheBackendInterface $cache, Connection $connection, EntityTypeManagerInterface $entity_type_manager) {
    $this->eventManager = $event_manager;
    $this->cache = $cache;
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @param EntityInterface $entity
   *
   * @return void
   */
  public function updateBanner(EntityInterface $entity) {
    $banner = '';
    $availability = $this->eventManager->getQuotasAndAvailability($entity);
    $available = TRUE;
    foreach ($availability['results'] as $quota) {
      $available = $available & $quota['available'];
    }
    if (!$available) {
      $banner = 'sold out';
    } else {
      // TODO
      $banner = 'free';
    }
  }

  /**
   * @param EntityInterface[] $entities
   *
   * @return void
   */
  public function updateBannerMultiple(array $entities) {
    /** @var EntityInterface $entity */
    foreach ($entities as $entity) {
      $this->updateBanner($entity);
    }
  }

  public function processQueue() {
    $result = $this->connection->select('pretix_queue', 'rq')
      ->fields('rq', ['entity_type_id', 'entity_id'])
      ->orderBy('timestamp')
      ->range(0, 50)
      ->execute();

    $entity_ids = [];
    while($values = $result->fetchAssoc()) {
      if (!isset($entity_ids[$values['entity_type_id']])) {
        $entity_ids[$values['entity_type_id']] = [];
      }
      $entity_ids[$values['entity_type_id']][] = $values['entity_id'];
    }

    $entities = [];
    foreach ($entity_ids as $entity_type_id => $ids) {
      $entities = array_merge($entities, $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple($ids));
    }

    $this->updateBannerMultiple($entities);

    foreach ($entities as $entity) {
      $this->deleteEntityFromQueue($entity);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return bool
   */
  public function isEntityInQueue(EntityInterface $entity): bool {
    $result = $this->connection->select('pretix_queue', 'rq')
      ->condition('entity_type_id', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->fields('rq', ['timestamp'])
      ->execute();
    return (count($result->fetchAll()) > 0);
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return void
   */
  public function addEntityToQueue(EntityInterface $entity): void {
    if (!$this->isEntityInQueue($entity)) {
      try {
        $this->connection->insert('pretix_queue')
          ->fields([
            'entity_type_id' => $entity->getEntityTypeId(),
            'entity_id' => $entity->id(),
            'timestamp' => time(),
          ])
          ->execute();
      } catch (\Exception $e) {
      }
    }
  }

  public function deleteEntityFromQueue(EntityInterface $entity): void {
    $this->connection->delete('pretix_queue')
      ->condition('entity_type_id', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->execute();
  }

  /**
   * @param array $banners
   *
   * @return void
   */
  protected function writeCache(array $banners) {
    foreach ($banners as $nid => $banner) {
      $cacheKey = 'pretix:banner:' . $nid;
      $this->cache->set($cacheKey, $banner, $this->getRequestTime() + $this->getMaxAge());
    }
  }

  protected function getMaxAge(): int {
    return 60*60*24;
  }

  /**
   * Wrapper method for REQUEST_TIME constant.
   *
   * @return int
   */
  protected function getRequestTime() {
    return defined('REQUEST_TIME') ? REQUEST_TIME : (int) $_SERVER['REQUEST_TIME'];
  }

}
