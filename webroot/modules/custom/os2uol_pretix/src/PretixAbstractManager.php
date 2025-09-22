<?php

namespace Drupal\os2uol_pretix;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\node\NodeInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\UserInterface;

abstract class PretixAbstractManager {

  /**
   * @var \Drupal\os2uol_pretix\PretixConnector
   */
  protected PretixConnector $connector;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  public function __construct(PretixConnector $connector, LoggerChannelFactoryInterface $logger_factory, MessengerInterface $messenger) {
    $this->connector = $connector;
    $this->logger = $logger_factory->get('pretix');
    $this->messenger = $messenger;
  }

  /**
   * @param \Drupal\Core\Entity\EditorialContentEntityBase $entity
   *
   * @return string
   */
  protected function getEntityKey(EditorialContentEntityBase $entity): string {
    return $entity->getEntityTypeId() . ':' . $entity->id();
  }

  protected function getClient(EditorialContentEntityBase $entity): PretixClient {
    static $clients = [];
    $key = $this->getEntityKey($entity);
    if (!isset($clients[$key])) {
      /** @var EntityOwnerTrait $entity_owner */
      $entity_owner = $entity;
      /** @var \Drupal\user\UserInterface $user */
      $user = $entity_owner->getOwner();
      $clients[$key] = $this->getClientByUser($user);
    }
    return $clients[$key];
  }

  protected function getClientByUser(UserInterface $user): PretixClient {
    $pretix_url = $user->get('field_pretix_url')->getString();
    $api_token = $user->get('field_pretix_api_token')->getString();
    $organizer_short_form = $user->get('field_pretix_organizer_form')->getString();
    return $this->connector->getClient($pretix_url, $api_token, $organizer_short_form);
  }

  /**
   * @param string $eventSlug
   *
   * @return \Drupal\node\NodeInterface|null
   */
  public function getEntityByEventSlug(string $eventSlug): ?NodeInterface {
    $entity = NULL;
    try {
      $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
    }
    catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
      return NULL;
    }

    $ids = $nodeStorage->getQuery()
      ->condition('field_pretix_event_short_form', $eventSlug)
      ->accessCheck(TRUE)
      ->execute();

    if (count($ids) > 1) {
      $this->logger->warning('Pretix event @event is attached to multiple content', ['@event' => $eventSlug]);
    }
    if (!empty($ids)) {
      /** @var \Drupal\node\NodeInterface $entity */
      $entity = $nodeStorage->load($ids[array_key_first($ids)]);
    }

    return $entity;
  }

  protected function getEventSlug(EditorialContentEntityBase $entity) {
    static $slugs = [];
    $key = $this->getEntityKey($entity);
    if (!isset($slugs[$key])) {
      if ($entity->get('field_pretix_event_short_form')->isEmpty()) {
        $slugs[$key] = NULL;
      } else {
        $slugs[$key] = $entity->get('field_pretix_event_short_form')->first()->getString();
      }
    }
    return $slugs[$key];
  }

  protected function getEventTemplate(EditorialContentEntityBase $entity) {
    static $templates = [];
    $key = $this->getEntityKey($entity);
    if (!isset($templates[$key])) {
      if ($entity->get('field_pretix_template_event')->isEmpty()) {
        $templates[$key] = NULL;
      } else {
        $templates[$key] = $entity->get('field_pretix_template_event')->first()->getString();
      }
    }
    return $templates[$key];
  }

  public function isEntityWithBanner(EntityInterface $entity): bool {
    if ($entity instanceof EditorialContentEntityBase) {
      if ($entity->hasField('field_is_free')) {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function isPretixEventEntity(EntityInterface $entity): bool {
    if (!$entity instanceof EditorialContentEntityBase) {
      return FALSE;
    }
    /** @var EditorialContentEntityBase $editorialEntity */
    $editorialEntity = $entity;
    if ($editorialEntity->hasField('field_pretix_template_event') && $editorialEntity->hasField('field_pretix_event_short_form')) {
      if (!$editorialEntity->get('field_pretix_event_short_form')->isEmpty()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function hasPretixShopURL(EntityInterface $entity): bool {
    if (!$entity instanceof EditorialContentEntityBase) {
      return FALSE;
    }
    /** @var EditorialContentEntityBase $editorialEntity */
    $editorialEntity = $entity;
    if ($editorialEntity->hasField('field_pretix_shop_url')) {
      if (!$editorialEntity->get('field_pretix_shop_url')->isEmpty()) {
        return TRUE;
      }
    }
    return $this->isPretixEventEntity($entity);
  }

  public function isPretixEnabledUser(UserInterface $user): bool {
    if ($user->get('field_pretix_url')->isEmpty() || $user->get('field_pretix_api_token')->isEmpty() || $user->get('field_pretix_organizer_form')->isEmpty()) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @param array $result
   *
   * @return bool
   */
  public function isApiError($result) {
    return isset($result['error']) || empty($result);
  }

  protected function apiError(array $result, string $message, $addToMessenger = TRUE) {
    $this->logger->error(t($message) . ': ' . json_encode($result));
    if ($addToMessenger) {
      $this->messenger->addError(t($message));
    }
    return NULL;
  }

  /**
   * Notify modules that the Pretix event has changed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return void
   */
  protected function notifyEventChanged(EntityInterface $entity): void {
    \Drupal::moduleHandler()->invokeAll('pretix_event_changed', [$entity]);
  }

}
