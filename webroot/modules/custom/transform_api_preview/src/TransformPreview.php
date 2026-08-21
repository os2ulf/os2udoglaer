<?php

namespace Drupal\transform_api_preview;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\transform_api\Repository\EntityTransformRepositoryInterface;
use Drupal\transform_api\Transform\EntityTransform;
use Drupal\transform_api\Transformer;

class TransformPreview {

  protected KeyValueStoreInterface $keyValueStore;
  protected Config $config;
  protected Transformer $transformer;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected UuidInterface $uuid;
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * @param KeyValueFactoryInterface $keyValueFactory
   * @param EntityTypeManagerInterface $entityTypeManager
   * @param Transformer $transformer
   * @param UuidInterface $uuid
   * @param ModuleHandlerInterface $moduleHandler
   */
  public function __construct(KeyValueFactoryInterface $keyValueFactory, EntityTypeManagerInterface $entityTypeManager, Transformer $transformer, UuidInterface $uuid, ModuleHandlerInterface $moduleHandler, ConfigFactoryInterface $configFactory)
  {
    $this->config = $configFactory->get('transform_api_preview.settings');
    $this->keyValueStore = $keyValueFactory->get('transform_api_preview');
    $this->transformer = $transformer;
    $this->entityTypeManager = $entityTypeManager;
    $this->uuid = $uuid;
    $this->moduleHandler = $moduleHandler;
  }

  public function generate(EntityInterface $entity) {
    $uuid = $this->uuid->generate();
    $entity_id = $entity->id();
    $entity_type_id = $entity->getEntityTypeId();
    $info = [
      'entity_id' => $entity_id,
      'entity_type_id' => $entity_type_id,
    ];

    $this->keyValueStore->set($uuid, $info);
    return $uuid;
  }

  public function getUrl($uuid, $transform_mode = EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE) {
    $front_end_url = $this->config->get('front_end_url');
    $url = $front_end_url . 'transform/preview/' . $uuid . '/full';
    $this->moduleHandler->alter('transform_preview_url', $url, $uuid);
    return $url;
  }

  public function getEntity($uuid) {
    $info = $this->keyValueStore->get($uuid);
    $entity = NULL;
    if (!empty($info)) {
      $storage = $this->entityTypeManager->getStorage($info['entity_type_id']);
      if ($storage instanceof RevisionableStorageInterface) {
        $revision_id = $storage->getLatestRevisionId($info['entity_id']);
        if ($revision_id !== NULL) {
          $entity = $storage->loadRevision($revision_id);
        }
      } else {
        $entity = $storage->load($info['entity_id']);
      }
    }
    return $entity;
  }

  public function getTransform($uuid, $transform_mode = EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE) {
    $entity = $this->getEntity($uuid);
    if (empty($entity)) {
      return NULL;
    }
    $transform = new EntityTransform($entity, $transform_mode);
    $transform->setCacheMaxAge(0);
    return $transform;
  }
}
