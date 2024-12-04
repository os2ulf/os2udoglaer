<?php

namespace Drupal\os2uol_pretix;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class PretixBannerManager implements TrustedCallbackInterface {

  use StringTranslationTrait;

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
   * @param string $entity_type_id
   * @param string $entity_id
   *
   * @return array
   */
  public function transformBanner(string $entity_type_id, string $entity_id): array {
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    return [
      '#collapse' => TRUE,
      'value' => $this->getBanner($entity)
    ];
  }

  public function getBanner(EntityInterface $entity): string {
    $banner = '';
    $cached = $this->cache->get($this->getCacheKey($entity), TRUE);
    if ($cached !== FALSE) {
      $banner = $cached->data;
    }
    if ($cached === FALSE || !$cached->valid) {
      $this->addEntityToQueue($entity);
    }
    return $banner;
  }

  /**
   * @param EntityInterface $entity
   *
   * @return void
   */
  public function updateBanner(EntityInterface $entity): void {
    $banner = '';
    $available = TRUE;
    if ($entity instanceof EditorialContentEntityBase) {
      $available = FALSE;
      $availability = $this->eventManager->getQuotasAndAvailability($entity);
      foreach ($availability['results'] as $quota) {
        $available = $available | $quota['available'];
      }
    }
    if (!$available) {
      $banner = $this->t('Sold out');
    } else {
      if ($entity instanceof EditorialContentEntityBase) {
        if ($entity->hasField('field_is_free') && $entity->get('field_is_free')->first()->getString()) {
          $banner = $this->t('Free');
        }
      }
    }
    $this->saveBanner($entity, $banner);
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
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $banner
   *
   * @return void
   */
  public function saveBanner(EntityInterface $entity, string $banner): void {
    \Drupal::logger('pretix')->debug('Saving banner "@banner" for @title', ['@banner' => $banner, '@title' => $entity->getTitle()]);
    $this->cache->delete($this->getCacheKey($entity));
    $this->cache->set($this->getCacheKey($entity), $banner, $this->getMaxAge());
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return void
   */
  public function deleteBanner(EntityInterface $entity): void {
    $this->cache->delete($this->getCacheKey($entity));
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return string
   */
  protected function getCacheKey(EntityInterface $entity): string {
    return 'pretix:banner:' . $entity->getEntityTypeId() . ':' . $entity->id();
  }

  protected function getMaxAge(): int {
    return CacheBackendInterface::CACHE_PERMANENT;
    //return $this->getRequestTime() + 60*60*24;
  }

  /**
   * Wrapper method for REQUEST_TIME constant.
   *
   * @return int
   */
  protected function getRequestTime() {
    return defined('REQUEST_TIME') ? REQUEST_TIME : (int) $_SERVER['REQUEST_TIME'];
  }

  public static function trustedCallbacks() {
    return [
      'transformBanner'
    ];
  }
}
