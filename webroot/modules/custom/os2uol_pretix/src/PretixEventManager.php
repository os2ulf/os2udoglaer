<?php

namespace Drupal\os2uol_pretix;

use DateTimeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;

class PretixEventManager extends PretixAbstractManager {

  public function __construct(PretixConnector $connector, LoggerChannelFactoryInterface $logger_factory, MessengerInterface $messenger) {
    parent::__construct($connector, $logger_factory, $messenger);
  }

  public function addSubEvent(EditorialContentEntityBase $entity, array $subevent) {
    if (is_null($this->getEventSlug($entity)) || is_null($this->getEventTemplate($entity))) {
      \Drupal::messenger()->addError(t('Event has not yet been set up for Pretix'));
      return NULL;
    }
    $eventSlug = $this->getEventSlug($entity);
    $client = $this->getClient($entity);

    $templateSubEvent = $this->getEventSubEventTemplate($entity);
    if (empty($templateSubEvent)) {
      \Drupal::messenger()->addError(t('Cannot get template event sub-event'));
      return NULL;
    }

    $product = $this->getEventProduct($entity);
    if (empty($product)) {
      \Drupal::messenger()->addError(t('Cannot get template event items'));
      return NULL;
    }

    $data = $templateSubEvent;
    // Remove the template id.
    unset($data['id']);

    $data['item_price_overrides'] = [
      [
        'item' => $product['id'],
      ],
    ];

    $this->updateSubEventData($entity, $subevent, $data);

    $subEvent = $client->createSubEvent($eventSlug, $data);
    $this->updateSubEventQuota($entity, $subEvent, $subevent['quota']);

    return $subEvent;
  }

  public function editSubEvent(EditorialContentEntityBase $entity, array $subevent) {
    if (is_null($this->getEventSlug($entity)) || is_null($this->getEventTemplate($entity))) {
      \Drupal::messenger()->addError(t('Event has not yet been set up for Pretix'));
      return NULL;
    }
    $eventSlug = $this->getEventSlug($entity);
    $client = $this->getClient($entity);

    $data = $this->getSubEvent($entity, $subevent['id']);
    $this->updateSubEventData($entity, $subevent, $data);
    $subEvent = $client->updateSubEvent($eventSlug, $data);
    $this->updateSubEventQuota($entity, $subEvent, $subevent['quota']);

    return $subEvent;
  }

  public function deleteSubEvent(EditorialContentEntityBase $entity, $subevent_id) {
    $eventSlug = $this->getEventSlug($entity);
    $client = $this->getClient($entity);
    return $client->deleteSubEvent($eventSlug, $subevent_id);
  }

  protected function updateSubEventQuota(EditorialContentEntityBase $entity, array $subEvent, $size) {
    $eventSlug = $this->getEventSlug($entity);
    $eventTemplate = $this->getEventTemplate($entity);
    $client = $this->getClient($entity);

    $templateSubEvent = $this->getEventSubEventTemplate($entity);
    if (empty($templateSubEvent)) {
      \Drupal::messenger()->addError(t('Cannot get template event sub-event'));
      return NULL;
    }
    $product = $this->getEventProduct($entity);
    if (empty($product)) {
      \Drupal::messenger()->addError(t('Cannot get template event items'));
      return NULL;
    }

    // Get sub-event quotas.
    $result = $client->getQuotas($eventSlug, $subEvent);
    if (isset($result['error'])) {
      \Drupal::messenger()->addError(t('Cannot get sub-event quotas'));
      return NULL;
    }

    if (0 === $result['count']) {
      // Create a new quota for the sub-event.
      $result = $client->getQuotas(
        $eventTemplate,
        $templateSubEvent
      );
      if (isset($result['error']) || 0 === $result['count']) {
        \Drupal::messenger()->addError(t('Cannot get template sub-event quotas'));
        return NULL;
      }

      $templateQuota = $result['results'][0];
      unset($templateQuota['id'], $templateQuota['subevent']);
      $data = $templateQuota;
      $data['subevent'] = $subEvent['id'];
      $data['items'] = [$product['id']];
      $result = $client->createQuota($eventSlug, $data);
      if (isset($result['error'])) {
        \Drupal::messenger()->addError(t('Cannot create quota for sub-event'));
        return NULL;
      }
      $result = $client->getQuotas($eventSlug, $subEvent);
    }

    // Update the quota.
    if (isset($result['error']) || 1 !== $result['count']) {
      \Drupal::messenger()->addError(t('Cannot get sub-event quota'));
      return NULL;
    }

    $quota = $result['results'][0];

    $data = ['size' => $size];
    $result = $client->updateQuota($eventSlug, $quota['id'], $data);
    if (isset($result['error'])) {
      \Drupal::messenger()->addError(t('Cannot update sub-event quota'));
      return NULL;
    }
    return $result;
  }

  protected function updateSubEventData(EditorialContentEntityBase $entity, $subevent, &$data) {
    $data['name'] = $this->formatEventName($entity);
    $data['date_from'] = $subevent['date_from'];
    $data['presale_start'] = $subevent['presale_start'] ?? NULL;
    $data['location'] = NULL;
    $data['active'] = TRUE;
    $data['is_public'] = TRUE;
    $data['date_to'] = $subevent['date_to'] ?? NULL;
    $data['date_admission'] = NULL;
    $data['presale_end'] = $subevent['presale_end'] ?? NULL;
    $data['seating_plan'] = NULL;
    $data['seat_category_mapping'] = (object) [];
    $price = TRUE === $subevent['free'] ? 0 : (float) $subevent['price'];

    $data['item_price_overrides'][0]['price'] = $price;

    // Attempts to fix an issue where integration would fail if an empty array or object was passed.
    if (empty($data['frontpage_text']) || !is_string($data['frontpage_text'])) {
      $data['frontpage_text'] = NULL;
    }

    // Important: meta_data value must be an object!
    $data['meta_data'] = (object) [];
    $data['meta_data'] = ['DrupalURL' => $entity->toUrl()->toString()];
  }

  public function getEvents(EditorialContentEntityBase $entity) {
    $client = $this->getClient($entity);
    return $client->getEvents();
  }

  public function getEventUrl(EditorialContentEntityBase $entity): string {
    $client = $this->getClient($entity);
    return $client->getPretixUrl() . '/control/event/' . $client->getOrganizer() . '/' . $this->getEventSlug($entity) . '/';
  }

  public function getEventShopUrl(EditorialContentEntityBase $entity): string {
    $client = $this->getClient($entity);
    return $client->getPretixUrl() . '/' . $client->getOrganizer() . '/' . $this->getEventSlug($entity) . '/';
  }

  public function getQuotas(EditorialContentEntityBase $entity, $subevent) {
    $client = $this->getClient($entity);
    return $client->getQuotas($this->getEventSlug($entity), $subevent);
  }

  public function getSubEvent(EditorialContentEntityBase $entity, $subevent_id) {
    if (is_null($this->getEventSlug($entity))) {
      return NULL;
    }

    $eventSlug = $this->getEventSlug($entity);
    $client = $this->getClient($entity);
    return $client->getSubEvent($eventSlug, $subevent_id);
  }

  public function getSubEvents(EditorialContentEntityBase $entity) {
    if (is_null($this->getEventSlug($entity))) {
      return NULL;
    }

    $eventSlug = $this->getEventSlug($entity);
    $client = $this->getClient($entity);
    return $client->getSubEvents($eventSlug);
  }

  public function setEventLive(EditorialContentEntityBase $entity) {
    $live = $entity->isPublished();
    $client = $this->getClient($entity);
    $result = $client->getEvent($this->getEventSlug($entity));
    if ($this->isApiError($result)) {
      return $this->apiError($result, 'Cannot get event');
    }
    $event = $result;

    $result = $client->updateEvent($this->getEventSlug($entity), ['live' => $live]);
    if ($this->isApiError($result)) {
      return $this->apiError($result, $live ? 'Cannot set pretix event live' : 'Cannot set pretix event not live');
    }
    $event = $result;

    $message = $live
      ? t('Successfully set <a href="@pretix_event_url">the pretix event</a> live.', [
        '@pretix_event_url' => $this->getEventUrl($entity),
      ])
      : t('Successfully set <a href="@pretix_event_url">the pretix event</a> not live.', [
        '@pretix_event_url' => $this->getEventUrl($entity),
      ]);
    $this->messenger->addStatus($message);
    return TRUE;
  }

  protected function getEventTemplate(EditorialContentEntityBase $entity) {
    $templates = &drupal_static(__FUNCTION__);
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

  protected function getEventSubEventTemplate(EditorialContentEntityBase $entity) {
    $templates = &drupal_static(__FUNCTION__);
    $key = $this->getEntityKey($entity);
    if (!isset($templates[$key])) {
      $client = $this->getClient($entity);
      // Get first sub-event from template event.
      $result = $client->getSubEvents($this->getEventTemplate($entity));
      if (isset($result['error']) || 0 === $result['count']) {
        $templates[$key] = NULL;
      } else {
        $templates[$key] = $result['results'][0];
      }
    }
    return $templates[$key];
  }

  protected function getEventProduct(EditorialContentEntityBase $entity) {
    $products = &drupal_static(__FUNCTION__);
    $key = $this->getEntityKey($entity);
    if (!isset($products[$key])) {
      $client = $this->getClient($entity);
      // Get product from template event.
      $result = $client->getProducts($this->getEventSlug($entity));
      if (isset($result['error']) || 0 === $result['count']) {
        $products[$key] = NULL;
      } else {
        // Always use the first product.
        $products[$key] = $result['results'][0];
      }
    }
    return $products[$key];
  }

  public function formatDateFormValue($form_value) {
    if ($form_value instanceof DrupalDateTime) {
      return $form_value->format(DateTimeInterface::ATOM);
    } else {
      return NULL;
    }
  }

  public function createEvent(EditorialContentEntityBase $entity, array $event) {
    $client = $this->getClient($entity);
    $data = [
      'name' => $this->formatEventName($entity),
      'slug' => $entity->id(),
      'is_public' => $entity->isPublished(),
      'date_from' => $event['date_from']
    ];
    $data['has_subevents'] = TRUE;
    $data['meta_data'] = ['DrupalURL' => $entity->toUrl()->toString()];
    return $client->createEvent($this->getEventTemplate($entity), $data);
  }

  protected function formatEventName($entity): array {
    return ['da' => $entity->label()];
  }

}
