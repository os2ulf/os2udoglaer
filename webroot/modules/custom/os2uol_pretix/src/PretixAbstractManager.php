<?php

namespace Drupal\os2uol_pretix;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
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
    $clients = &drupal_static(__FUNCTION__);
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
    $pretix_url = $user->get('field_pretix_url')->first()->getString();
    $api_token = $user->get('field_pretix_api_token')->first()->getString();
    $organizer_short_form = $user->get('field_pretix_organizer_form')->first()->getString();
    return $this->connector->getClient($pretix_url, $api_token, $organizer_short_form);
  }

  protected function getEventSlug(EditorialContentEntityBase $entity) {
    $slugs = &drupal_static(__FUNCTION__);
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

  /**
   * @param array $result
   *
   * @return bool
   */
  protected function isApiError(array $result) {
    if (isset($result['error'])) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  protected function apiError($result, $message, $addToMessenger = TRUE) {
    $this->logger->error(t($message), $result);
    if ($addToMessenger) {
      $this->messenger->addError(t($message));
    }
    return NULL;
  }

}