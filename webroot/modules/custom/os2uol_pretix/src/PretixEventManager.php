<?php

namespace Drupal\os2uol_pretix;

use DateTimeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;

class PretixEventManager extends PretixAbstractManager {

  public function __construct(PretixConnector $connector, LoggerChannelFactoryInterface $logger_factory, MessengerInterface $messenger) {
    parent::__construct($connector, $logger_factory, $messenger);
  }

  public function addSubEvent(EditorialContentEntityBase $entity, array $subevent) {
    if (!$this->isPretixEventEntity($entity)) {
      $this->messenger->addError(t('Event has not yet been set up for Pretix'));
      return NULL;
    }
    $eventSlug = $this->getEventSlug($entity);
    $client = $this->getClient($entity);

    $templateSubEvent = $this->getEventSubEventTemplate($entity);
    if ($templateSubEvent !== null && $this->isApiError($templateSubEvent)) {
      $this->apiError($templateSubEvent, 'Cannot get template event sub-event');
      return NULL;
    }

    $product = $this->getEventProduct($entity);
    if ($templateSubEvent !== null && $this->isApiError($templateSubEvent)) {
      $this->apiError($templateSubEvent, 'Cannot get template event sub-event');
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

    $this->notifyEventChanged($entity);

    return $subEvent;
  }

  public function editSubEvent(EditorialContentEntityBase $entity, array $subevent) {
    if (!$this->isPretixEventEntity($entity)) {
      $this->messenger->addError(t('Event has not yet been set up for Pretix'));
      return NULL;
    }
    $eventSlug = $this->getEventSlug($entity);
    $client = $this->getClient($entity);

    $data = $this->getSubEvent($entity, $subevent['id']);
    $this->updateSubEventData($entity, $subevent, $data);
    $subEvent = $client->updateSubEvent($eventSlug, $data);
    $this->updateSubEventQuota($entity, $subEvent, $subevent['quota'] ?? 0);

    $this->notifyEventChanged($entity);

    return $subEvent;
  }

  public function deleteEvent(EditorialContentEntityBase $entity) {
    $eventSlug = $this->getEventSlug($entity);
    $client = $this->getClient($entity);
    return $client->deleteEvent($eventSlug);
  }

  public function deleteSubEvent(EditorialContentEntityBase $entity, $subevent_id) {
    $eventSlug = $this->getEventSlug($entity);
    $client = $this->getClient($entity);
    $this->notifyEventChanged($entity);
    return $client->deleteSubEvent($eventSlug, $subevent_id);
  }

  protected function updateSubEventQuota(EditorialContentEntityBase $entity, array $subEvent, $size) {
    $eventSlug = $this->getEventSlug($entity);
    $eventTemplate = $this->getEventTemplate($entity);
    $client = $this->getClient($entity);

    $templateSubEvent = $this->getEventSubEventTemplate($entity);
    if ($this->isApiError($templateSubEvent)) {
      $this->apiError($templateSubEvent, 'Cannot get template event sub-event');
      return NULL;
    }
    $product = $this->getEventProduct($entity);
    if ($this->isApiError($product)) {
      $this->apiError($product, 'Cannot get template event items');
      return NULL;
    }

    // Get sub-event quotas.
    $result = $client->getQuotas($eventSlug, $subEvent);
    if ($this->isApiError($result)) {
      $this->apiError($result, 'Cannot get sub-event quotas');
      return NULL;
    }

    if (0 === $result['count']) {
      // Create a new quota for the sub-event.
      $result = $client->getQuotas(
        $eventTemplate,
        $templateSubEvent
      );
      if ($this->isApiError($result) || 0 === $result['count']) {
        $this->apiError($result, 'Cannot get template sub-event quotas');
        return NULL;
      }

      $templateQuota = $result['results'][0];
      unset($templateQuota['id'], $templateQuota['subevent']);
      $data = $templateQuota;
      $data['subevent'] = $subEvent['id'];
      $data['items'] = [$product['id']];
      $result = $client->createQuota($eventSlug, $data);
      if ($this->isApiError($result)) {
        $this->apiError($result, 'Cannot create quota for sub-event');
        return NULL;
      }
      $result = $client->getQuotas($eventSlug, $subEvent);
    }

    // Update the quota.
    if ($this->isApiError($result) || 1 !== $result['count']) {
      $this->apiError($result, 'Cannot get sub-event quota');
      return NULL;
    }

    $quota = $result['results'][0];

    $data = ['size' => $size];
    $result = $client->updateQuota($eventSlug, $quota['id'], $data);
    if ($this->isApiError($result)) {
      $this->apiError($result, 'Cannot update sub-event quota');
      return NULL;
    }
    return $result;
  }

    /**
   * Convert a date string to UTC format.
   *
   * @param string $date_string
   *   The date string to convert.
   *
   * @return string
   *   The date in UTC formatted as ATOM.
   */
  protected function convertToUTC($date_string) {
    $date = new \DateTime($date_string, new \DateTimeZone(date_default_timezone_get()));
    return $date->format(DateTimeInterface::ATOM);
  }

  /**
   * Updates sub-event data before sending it to Pretix.
   */
  protected function updateSubEventData(EditorialContentEntityBase $entity, $subevent, &$data) {
    // Ensure that the date is formatted in UTC before sending it to Pretix
    $date_from = new \DateTime($subevent['date_from'], new \DateTimeZone(date_default_timezone_get()));

    $data['name'] = $this->formatEventName($entity);
    $data['date_from'] = $date_from->format(DateTimeInterface::ATOM);  // Send the UTC-formatted date
    $data['presale_start'] = !empty($subevent['presale_start']) ? $this->convertToUTC($subevent['presale_start']) : NULL;
    $data['location'] = NULL;
    $data['active'] = TRUE;
    $data['is_public'] = TRUE;
    $data['date_to'] = !empty($subevent['date_to']) ? $this->convertToUTC($subevent['date_to']) : NULL;
    $data['date_admission'] = NULL;
    $data['presale_end'] = !empty($subevent['presale_end']) ? $this->convertToUTC($subevent['presale_end']) : NULL;

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
    $data['item_meta_properties'] = ['DrupalURL' => $this->getDrupalUrl($entity)];
  }

  public function getEvent(EditorialContentEntityBase $entity) {
    if (is_null($this->getEventSlug($entity))) {
      return NULL;
    }

    $eventSlug = $this->getEventSlug($entity);
    $client = $this->getClient($entity);
    return $client->getEvent($eventSlug);
  }

  public function getEvents(EditorialContentEntityBase $entity, $all = FALSE) {
    $client = $this->getClient($entity);
    $page = 1;
    $result = $client->getEvents($page);
    if (!$all || $this->isApiError($result)) {
      return $result;
    } else {
      $results = $result;
      while (!is_null($result['next'])) {
        $result = $client->getEvents(++$page);
        $results['results'] = array_merge($results['results'], $result['results']);
      }
      $results['next'] = NULL;
      return $results;
    }
  }

  public function getEventUrl(EditorialContentEntityBase $entity): string {
    $client = $this->getClient($entity);
    return $client->getPretixUrl() . 'control/event/' . $client->getOrganizer() . '/' . $this->getEventSlug($entity) . '/';
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return string
   */
  public function getDrupalUrl(EntityInterface $entity): string {
    try {
      return $entity->toUrl()->toString();
    }
    catch (EntityMalformedException $e) {
      return 'Malformed';
    }
  }

  public function getEventShopUrl(EditorialContentEntityBase $entity): string {
    $client = $this->getClient($entity);
    if ($this->isPretixEventEntity($entity)) {
      return $client->getPretixUrl() . $client->getOrganizer() . '/' . $this->getEventSlug($entity) . '/';
    } elseif ($this->hasPretixShopURL($entity)) {
      return $entity->get('field_pretix_shop_url')->first()->getString();
    } else {
      return '';
    }
  }

  public function getQuotas(EditorialContentEntityBase $entity, $subevent) {
    $client = $this->getClient($entity);
    return $client->getQuotas($this->getEventSlug($entity), $subevent);
  }

  public function populateSubeventsWithQuotas(EditorialContentEntityBase $entity, array $subevents): array {
    $subevent_ids = [];
    foreach ($subevents as $key => $subevent) {
      $subevent_ids[] = $subevent['id'];
    }
    $client = $this->getClient($entity);
    $quota_result = $client->getQuotas($this->getEventSlug($entity), $subevent_ids);
    $quotas = [];
    foreach ($quota_result['results'] as $key => $quota) {
      $quotas[$quota['subevent']] = $quota;
    }
    foreach ($subevents as $key => $subevent) {
      if (isset($quotas[$subevent['id']])) {
        $subevents[$key]['quota'] = $quotas[$subevent['id']];
      }
    }
    return $subevents;
  }

  public function getQuotasAndAvailability(EditorialContentEntityBase $entity) {
    $client = $this->getClient($entity);
    $eventSlug = $this->getEventSlug($entity);
    if (!empty($eventSlug)) {
      return $client->getQuotasAndAvailability($eventSlug);
    } else {
      return ['results' => []];
    }
  }

  public function getSubEvent(EditorialContentEntityBase $entity, $subevent_id) {
    if (is_null($this->getEventSlug($entity))) {
      return NULL;
    }

    $eventSlug = $this->getEventSlug($entity);
    $client = $this->getClient($entity);
    return $client->getSubEvent($eventSlug, $subevent_id);
  }

  public function getSubEvents(EditorialContentEntityBase $entity, $page = 1) {
    if (is_null($this->getEventSlug($entity))) {
      return NULL;
    }

    $eventSlug = $this->getEventSlug($entity);
    $client = $this->getClient($entity);
    return $client->getSubEvents($eventSlug, $page);
  }

  public function setEventLive(EditorialContentEntityBase $entity) {
    $live = $entity->isPublished();
    $client = $this->getClient($entity);
    $result = $client->getEvent($this->getEventSlug($entity));
    if ($this->isApiError($result)) {
      return $this->apiError($result, 'Cannot get event');
    }

    $result = $client->updateEvent($this->getEventSlug($entity), [
      'live' => $live,
      'meta_data' => ['DrupalURL' => $this->getDrupalUrl($entity)]
    ]);
    if ($this->isApiError($result)) {
      foreach ($result['json'] as $type => $errors) {
        if ($type == 'live') {
          foreach ($errors as $error) {
            if ($error == 'You need to configure at least one quota to sell anything.') {
              $this->messenger->addWarning(t('Shop is not live until dates are added.'));
            }
            else {
              $this->apiError($result, $live ? 'Cannot set pretix event live' : 'Cannot set pretix event not live');
            }
          }
        } elseif ($type == 'meta_data') {
          foreach ($errors as $error) {
            if ($error == "Meta data property 'DrupalURL' does not exist.") {
              $this->messenger->addError(t("Meta data property 'DrupalURL' does not exist."));
            }
            else {
              $this->apiError($result, $live ? 'Cannot set pretix event live' : 'Cannot set pretix event not live');
            }
          }
        } else {
          $this->apiError($result, $live ? 'Cannot set pretix event live' : 'Cannot set pretix event not live');
        }
      }
      return [];
    }

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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Check if the field_pretix_template_event is populated
    if ($entity->get('field_pretix_template_event')->isEmpty()) {
      $this->messenger->addError($this->t('The event template is missing. Please ensure the event template field is filled.'));
      return;
    }

    // Continue with the rest of the submit handler
    parent::submitForm($form, $form_state);
  }

  protected function getEventSubEventTemplate(EditorialContentEntityBase $entity) {
    static $templates = [];
    $key = $this->getEntityKey($entity);

    // Check if the event template is set
    if (!isset($templates[$key])) {
      $event_template = $this->getEventTemplate($entity);  // Use getEventSlug instead of getEventTemplate

      // If the event template is null or empty, return an error or handle the case
      if (empty($event_template)) {
        throw new \Exception('No event template found for the sub-event.');
      }

      $client = $this->getClient($entity);

      // Proceed only if the event template exists
      $result = $client->getSubEvents($event_template);

      if (isset($result['error']) || 0 === $result['count']) {
        $templates[$key] = $result;
      } else {
        $templates[$key] = $result['results'][0];
      }
    }

    return $templates[$key];
  }

  protected function getEventProduct(EditorialContentEntityBase $entity) {
    static $products = [];
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

  public function createEvent(EditorialContentEntityBase $entity, $template, array $event): array {
    $client = $this->getClient($entity);
    $data = [
      'name' => $this->formatEventName($entity),
      'slug' => $entity->id(),
      'is_public' => $entity->isPublished(),
      'date_from' => $event['date_from'],
      "currency" => "DKK"
    ];
    $data['has_subevents'] = TRUE;
    $data['metadata'] = ['DrupalURL' => $entity->toUrl()->setAbsolute()->toString()];
    $result = $client->createEvent($template, $data);
    if ($this->isApiError($result)) {
      foreach ($result['json'] as $type => $errors) {
        if ($type == 'slug') {
          foreach ($errors as $error) {
            if ($error == 'This slug has already been used for a different event.') {
              $this->apiError($result, 'The event already exists.');
            }
            else {
              $this->apiError($result, 'Could not create event');
            }
          }
        } elseif ($type == 'meta_data') {
          foreach ($errors as $error) {
            if ($error == "Meta data property 'DrupalURL' does not exist.") {
              $this->messenger->addError(t("Meta data property 'DrupalURL' does not exist."));
            }
            else {
              $this->apiError($result, 'Could not create event');
            }
          }
        } else {
          $this->apiError($result, 'Could not create event');
        }
      }
      return [];
    }
    return $result;
  }

  protected function formatEventName($entity): array {
    return ['da' => $entity->label()];
  }

}
