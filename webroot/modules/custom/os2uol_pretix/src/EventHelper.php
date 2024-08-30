<?php

namespace Drupal\ulf_pretix\Pretix;

use EntityMetadataWrapper;

/**
 * Pretix helper.
 */
class EventHelper extends AbstractHelper {
  const PRETIX_CONTENT_TYPES = [
    'course',
    'course_educators',
    'internship',
    'education',
  ];

  /**
   * The configuration.
   *
   * @var array
   */
  private $configuration;

  /**
   * The constructor.
   *
   * @param array $configuration
   *   The configuration.
   */
  public function __construct(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * Create an instance of the helper.
   */
  public static function create() {
    return new static([
      'pretix_event_slug_template' => '!nid',
    ]);
  }

  /**
   * Validate the the specified event is a valid template event.
   *
   * @param string $url
   *   The url.
   * @param string $apiToken
   *   The api token.
   * @param string $organizerSlug
   *   The organizer slug.
   * @param string $eventSlug
   *   The event slug.
   *
   * @return null|array
   *   If null all is good. Otherwise, returns list of [key, error-message]
   */
  public function validateTemplateEvent($url, $apiToken, $organizerSlug, $eventSlug) {
    $client = new Client($url, $apiToken, $organizerSlug);

    // Check that we can access the pretix API.
    $result = $client->getApiEndpoints();
    if (isset($result->error) || 200 !== (int) $result->code) {
      return [
        'service_url' => t('Cannot connect to pretix api. Check your pretix url and API token settings.'),
        'api_token' => t('Cannot connect to pretix api. Check your pretix url and API token settings.'),
      ];
    }

    // Check that we can get events.
    $result = $client->getEvents();
    if (isset($result->error) || 200 !== (int) $result->code) {
      return [
        'organizer_slug' => t('Invalid or inaccessible organizer slug.'),
      ];
    }

    // Check that we can get the default event.
    $result = $client->getEvent($eventSlug);
    if (empty($eventSlug) || isset($result->error) || 200 !== (int) $result->code) {
      return [
        'event_slug' => t('Invalid or inaccessible event slug.'),
      ];
    }

    $event = $result->data;
    if (!$event->has_subevents) {
      return [
        'event_slug' => t('This event does not have sub-events.'),
      ];
    }

    $result = $client->getSubEvents($event);
    if (isset($result->error) || 200 !== (int) $result->code) {
      return [
        'event_slug' => t('Cannot get sub-events.'),
      ];
    }

    $subEvents = $result->data;
    if (1 !== $subEvents->count) {
      return [
        'event_slug' => t('Event must have exactly 1 date.'),
      ];
    }

    $subEvent = $subEvents->results[0];
    $result = $client->getQuotas($event, ['subevent' => $subEvent->id]);

    if (isset($result->error) || 200 !== (int) $result->code) {
      return [
        'event_slug' => t('Cannot get sub-event quotas.'),
      ];
    }

    $quotas = $result->data;
    if (1 !== $quotas->count) {
      return [
        'event_slug' => t('Date must have exactly 1 quota.'),
      ];
    }

    $quota = $quotas->results[0];
    if (1 !== count($quota->items)) {
      return [
        'event_slug' => t('Event date (sub-event) quota must apply to exactly 1 product.'),
      ];
    }

    return NULL;
  }

  /**
   * Check if user has pretix events.
   *
   * @param object $user
   *   The user.
   *
   * @return bool
   *   True if user has pretix events.
   */
  public function userHasPretixEvents($user) {
    $result = db_query('SELECT count(*) FROM {node} n JOIN {ulf_pretix_events} e ON n.nid = e.nid WHERE n.uid = :uid', ['uid' => $user->uid]);

    return 0 !== (int) $result->fetchField();
  }

  /**
   * Ensure that the pretix callback webhook exists.
   *
   * @param string $url
   *   The url.
   * @param string $apiToken
   *   The api token.
   * @param string $organizerSlug
   *   The organizer slug.
   *
   * @return object
   *   The result from calling the pretix api.
   */
  public function ensureWebhook($url, $apiToken, $organizerSlug) {
    $client = new Client($url, $apiToken, $organizerSlug);

    $targetUrl = url('ulf_pretix/pretix/webhook/' . $organizerSlug, ['absolute' => TRUE]);
    $existingWebhook = NULL;

    $webhooks = $client->getWebhooks($organizerSlug);
    if (isset($webhooks->data->results)) {
      foreach ($webhooks->data->results as $webhook) {
        if ($targetUrl === $webhook->target_url) {
          $existingWebhook = $webhook;
          break;
        }
      }
    }

    $actionTypes = [
      OrderHelper::PRETIX_EVENT_ORDER_PLACED,
      OrderHelper::PRETIX_EVENT_ORDER_PLACED_REQUIRE_APPROVAL,
      OrderHelper::PRETIX_EVENT_ORDER_PAID,
      OrderHelper::PRETIX_EVENT_ORDER_CANCELED,
      OrderHelper::PRETIX_EVENT_ORDER_EXPIRED,
      OrderHelper::PRETIX_EVENT_ORDER_MODIFIED,
      OrderHelper::PRETIX_EVENT_ORDER_CONTACT_CHANGED,
      OrderHelper::PRETIX_EVENT_ORDER_CHANGED,
      OrderHelper::PRETIX_EVENT_ORDER_REFUND_CREATED_EXTERNALLY,
      OrderHelper::PRETIX_EVENT_ORDER_APPROVED,
      OrderHelper::PRETIX_EVENT_ORDER_DENIED,
      OrderHelper::PRETIX_EVENT_CHECKIN,
      OrderHelper::PRETIX_EVENT_CHECKIN_REVERTED,
    ];

    $webhookSettings = [
      'target_url' => $targetUrl,
      'enabled' => TRUE,
      'all_events' => TRUE,
      'limit_events' => [],
      'action_types' => $actionTypes,
    ];

    $result = NULL === $existingWebhook
      ? $client->createWebhook($webhookSettings)
      : $client->updateWebhook($existingWebhook, $webhookSettings);

    return $result;
  }

  /**
   * Check if a node is a pretix node.
   *
   * @param object $node
   *   The node.
   *
   * @return bool
   *   True iff the node is a pretix event node.
   */
  public function isPretixEventNode($node) {
    $type = $node->type ?? $node;

    if (!in_array($type, self::PRETIX_CONTENT_TYPES, TRUE)) {
      return FALSE;
    }

    // Node is a pretix node, but its creating user must also have a pretix
    // connection.
    return $this->isPretixUser($node->uid);
  }

  /**
   * Check if a user is a pretix user, i.e. has a connection to pretix.
   *
   * @param object|int $user
   *   The user or user id.
   *
   * @return bool
   *   True if the user has a defined pretix connection.
   */
  public function isPretixUser($user) {
    $user = entity_metadata_wrapper('user', $user);

    return $user->field_pretix_enable->value()
      && $user->field_pretix_url->value()
      && $user->field_pretix_api_token->value()
      && $user->field_pretix_organizer_slug->value()
      && $user->field_pretix_default_event_slug->value();
  }

  /**
   * Set pretix event info on a single node or a list of nodes.
   *
   * @param object|array $node
   *   A single node or a list of nodes.
   */
  public function setPretixEventInfo($node) {
    $info = $this->loadPretixEventInfo($node);
    if (!empty($info)) {
      if (is_array($node)) {
        foreach ($info as $nid => $data) {
          $node[$nid]->pretix = $data;
        }
      }
      else {
        $node->pretix = $info;
      }
    }
  }

  /**
   * Update (or create) pretix event based on node.
   *
   * @param object $node
   *   The node.
   *
   * @return array
   *   The status or an error.
   *
   * @throws \InvalidMergeQueryException
   */
  public function syncronizePretixEvent($node) {
    $info = $this->loadPretixEventInfo($node);
    $wrapper = entity_metadata_wrapper('node', $node);
    $user = entity_metadata_wrapper('user', $node->uid);
    $client = $this->getPretixClient($node);
    $isNewEvent = NULL === $info;

    if (NULL === $client) {
      return $this->error('Cannot get client');
    }

    // Only update new events and events that are syncrhonized with pretix.
    if ($isNewEvent || TRUE === $wrapper->field_pretix_synchronize->value()) {
      $startDate = NULL;
      $items = $wrapper->field_pretix_date->value();
      foreach ($items as $item) {
        $item = entity_metadata_wrapper('field_collection_item', $item);
        if ($item->field_pretix_start_date->value()) {
          if (NULL === $startDate || $item->field_pretix_start_date->value() < $startDate) {
            $startDate = $item->field_pretix_start_date->value();
          }
        }
      }

      if (NULL === $startDate) {
        return $this->error('Cannot get start date for event. pretix event not created.');
      }

      $name = $this->getEventName($node);
      $location = $this->getEventLocation($node);

      $data = [
        'name' => ['en' => $name],
        'currency' => 'DKK',
        'date_from' => $this->formatDate($startDate),
        'is_public' => $node->status,
        'location' => ['en' => $location],
      ];

      $eventData = [];
      if ($isNewEvent) {
        $data['slug'] = $this->getEventSlug($node);
        // has_subevents is not cloned from source event.
        $data['has_subevents'] = TRUE;
        $templateEventSlug = $user->field_pretix_default_event_slug->value();
        $result = $client->cloneEvent($templateEventSlug, $data);
        if (isset($result->error)) {
          return $this->apiError($result, 'Cannot clone event');
        }
        $eventData['template_event_slug'] = $templateEventSlug;
      }
      else {
        $result = $client->updateEvent($info['pretix_event_slug'], $data);
        if (isset($result->error)) {
          return $this->apiError($result, 'Cannot update event');
        }
      }

      $event = $result->data;
      $eventData['event'] = $event;
      $info = $this->addPretixEventInfo($node, $event, $eventData);
      $subEvents = $this->synchronizePretixSubEvents($event, $node, $client);

      foreach ($subEvents as $subEvent) {
        if (isset($subEvent['error'])) {
          return $subEvent;
        }
      }
    }

    // Get the event even if it's not syncronized with pretix.
    if (!isset($event) && TRUE !== $wrapper->field_pretix_synchronize->value()) {
      $result = $client->getEvent($info['pretix_event_slug']);
      if (isset($result->error)) {
        return $this->apiError($result, 'Cannot get event');
      }
      $event = $result->data;
    }

    // Update availability on event node.
    $this->updateEventAvailability($node);

    return [
      'status' => $isNewEvent ? 'created' : 'updated',
      'info' => $info,
      'subevents' => $subEvents ?? NULL,
    ];
  }

  /**
   * Set event live (or not) in pretix.
   *
   * @param object $node
   *   The node.
   * @param bool $live
   *   The live-ness of the node's event.
   *
   * @return array
   *   The event info.
   *
   * @throws \InvalidMergeQueryException
   */
  public function setEventLive($node, $live) {
    // Note: 'live' is updated for all events including the ones that are not
    // syncronized with pretix.
    $info = $this->loadPretixEventInfo($node);
    $client = $this->getPretixClient($node);
    $result = $client->getEvent($info['pretix_event_slug']);
    if ($this->isApiError($result)) {
      return $this->apiError($result, 'Cannot get event');
    }
    $event = $result->data;

    $result = $client->updateEvent($event->slug, ['live' => $live]);
    if ($this->isApiError($result)) {
      return $this->apiError($result, $live ? 'Cannot set pretix event live' : 'Cannot set pretix event not live');
    }
    $event = $result->data;
    $info = $this->addPretixEventInfo($node, $event, ['event' => $event]);

    return [
      'status' => $live ? 'live' : 'not live',
      'info' => $info,
    ];
  }

  /**
   * Delete event.
   *
   * @param object $node
   *   The node.
   *
   * @return array
   *   The result.
   */
  public function deletePretixEvent($node) {
    $info = $this->loadPretixEventInfo($node);
    if (NULL !== $info) {
      $client = $this->getPretixClient($node);
      $result = $client->deleteEvent($info['pretix_event_slug']);
      if (isset($result->error)) {
        return $this->apiError($result, 'Cannot delete event');
      }

      return [
        'status' => 'deleted',
        'info' => $info,
      ];
    }

    return [
      'error' => 'Cannot delete event in pretix',
    ];
  }

  /**
   * Get pretix template event url for a user.
   *
   * @param object|int $user
   *   The user or user id.
   * @param string $path
   *   An optional url path.
   *
   * @return string|null
   *   The pretix template url if any.
   */
  public function getPretixTemplateEventUrl($user, $path = '') {
    $user = entity_metadata_wrapper('user', $user);
    if (TRUE === $user->field_pretix_enable->value()) {
      $url = rtrim($user->field_pretix_url->value(), '/');
      $organizerSlug = $user->field_pretix_organizer_slug->value();
      $eventSlug = $user->field_pretix_default_event_slug->value();

      if (isset($url, $organizerSlug, $eventSlug)) {
        return $url . '/control/event/' . $organizerSlug . '/' . $eventSlug . '/' . $path;
      }
    }

    return NULL;
  }

  /**
   * Get pretix event slug.
   *
   * @param object $node
   *   The node.
   *
   * @return string
   *   The event slug.
   */
  private function getEventSlug($node) {
    $template = $this->configuration['pretix_event_slug_template'] ?? '!nid';

    return str_replace(['!nid'], [$node->nid], $template);
  }

  /**
   * Get event name from a node.
   *
   * @param object $node
   *   The node.
   *
   * @return string
   *   The event name.
   */
  private function getEventName($node) {
    return $node->title;
  }

  /**
   * Get event location.
   *
   * @param object $node
   *   The node.
   *
   * @return string
   *   The event location.
   */
  private function getEventLocation($node) {
    return '';
  }

  /**
   * Synchronize pretix sub-events.
   *
   * @param object $event
   *   The event.
   * @param object $node
   *   The node.
   * @param \Drupal\ulf_pretix\Pretix\Client $client
   *   The client.
   *
   * @return array
   *   The sub-event info.
   *
   * @throws \InvalidMergeQueryException
   */
  public function synchronizePretixSubEvents($event, $node, Client $client) {
    $info = [];
    $wrapper = entity_metadata_wrapper('node', $node);
    $items = $wrapper->field_pretix_date->value();
    $subEventIds = [];
    foreach ($items as $item) {
      $result = $this->synchronizePretixSubEvent($item, $event, $node, $client);
      if (isset($result['info'])) {
        $subEventIds[] = $result['info']['pretix_subevent_id'];
      }
      $info[] = $result;
    }

    foreach ($info as $subEvent) {
      if (isset($subEvent['error'])) {
        return $info;
      }
    }

    // Delete pretix sub-events that no longer exist in Drupal.
    $pretixSubEventIds = [];
    $result = $client->getSubEvents($event);
    if (isset($result->data->results)) {
      foreach ($result->data->results as $subEvent) {
        if (!in_array($subEvent->id, $subEventIds, TRUE)) {
          $client->deleteSubEvent($event, $subEvent);
        }
        $pretixSubEventIds[] = $subEvent->id;
      }
    }

    // @TODO Clean up info on pretix sub-events.
    return $info;
  }

  /**
   * Update event availability for a node.
   *
   * @param object $node
   *   The node.
   *
   * @return array|null
   *   On success, the event info. Otherwise an error.
   *
   * @throws \InvalidMergeQueryException
   */
  public function updateEventAvailability($node) {
    $client = $this->getPretixClient($node);
    if (isset($node->pretix['pretix_event_slug'])) {
      $eventSlug = $node->pretix['pretix_event_slug'];
      $result = $client->getEvent($eventSlug);
      if ($this->isApiError($result)) {
        return $this->apiError($result, 'Cannot get event');
      }
      $event = $result->data;

      $result = $client->getQuotas($event);
      if ($this->isApiError($result)) {
        return $this->apiError($result, 'Cannot get quotas');
      }
      $quotas = $result->data->results;

      foreach ($quotas as $quota) {
        $result = $client->getQuotaAvailability($event, $quota);
        if ($this->isApiError($result)) {
          return $this->apiError($result, 'Cannot get quota availability');
        }
        $quota->availability = $result->data;
      }

      $info = $this->addPretixEventInfo($node, $event, ['quotas' => $quotas]);
      $this->setEventAvailability($node, $event);

      return $info;
    }

    return NULL;
  }

  /**
   * Set event availability for on a node.
   *
   * @param object $node
   *   The node.
   * @param object $event
   *   The event.
   *
   * @throws \InvalidMergeQueryException
   */
  private function setEventAvailability($node, $event) {
    $info = $this->loadPretixEventInfo($node);
    $active_subevents = $this->getActivePretixSubEventsInfo($node, TRUE);

    if (isset($info['data']['quotas'])) {
      $available = FALSE;
      $has_subevents = $info['data']['event']['has_subevents'];
      foreach ($info['data']['quotas'] as $quota) {
        $is_active = ($has_subevents && isset($active_subevents[$quota['subevent']])) ? TRUE : !$has_subevents;
        if ($is_active === TRUE && isset($quota['availability']['available']) && TRUE === $quota['availability']['available']) {
          $available = TRUE;
          break;
        }
      }

      if (!isset($info['available']) || $info['available'] !== $available) {
        $this->addPretixEventInfo($node, $event, ['available' => $available]);
        // Flush cache for node.
        $cid = url('node/' . $node->nid, ['absolute' => TRUE]);
        cache_clear_all($cid, 'cache_page');
        // Re-index the node in the 'courses' index.
        if (module_exists('search_api')) {
          $index = search_api_index_load('courses');
          if (NULL !== $index) {

            $events = $this->getSharedEvents($node->nid);
            $events[] = $node->nid;

            search_api_track_item_change('node', $events);
          }
        }
      }
    }
  }

  /**
   * Synchronize pretix sub-event.
   *
   * @param object $item
   *   The item.
   * @param object $event
   *   The event.
   * @param object $node
   *   The node.
   * @param \Drupal\ulf_pretix\Pretix\Client $client
   *   The client.
   *
   * @return array
   *   On success, the sub-event info. Otherwise an error.
   *
   * @throws \InvalidMergeQueryException
   */
  private function synchronizePretixSubEvent($item, $event, $node, Client $client) {
    $item = entity_metadata_wrapper('field_collection_item', $item);
    $itemInfo = $this->loadPretixSubEventInfo($item, TRUE);
    $isNewItem = NULL === $itemInfo;

    $templateEvent = $this->getPretixTemplateEventSlug($node);
    $result = $client->getSubEvents($templateEvent);
    if (isset($result->error) || 0 === $result->data->count) {
      return $this->apiError($result, 'Cannot get template event sub-event');
    }
    $templateSubEvent = $result->data->results[0];

    $product = NULL;
    $data = [];
    if ($isNewItem) {
      // Get first sub-event from template event.
      $result = $client->getItems($event);
      if (isset($result->error) || 0 === $result->data->count) {
        return $this->apiError($result, 'Cannot get template event items');
      }

      // Always use the first product.
      $product = $result->data->results[0];

      // Convert template sub-event to array.
      $data = json_decode(json_encode($templateSubEvent), TRUE);
      // Remove the template id.
      unset($data['id']);

      $data['item_price_overrides'] = [
        [
          'item' => $product->id,
        ],
      ];
    }
    else {
      $data = $itemInfo['data']['subevent'];
    }

    $data['name'] = ['en' => $this->getEventName($node)];
    $data['date_from'] = $this->formatDate($item->field_pretix_start_date->value());
    $data['presale_start'] = $this->formatDate($item->field_pretix_presale->value());
    $data['location'] = NULL;
    $data['active'] = TRUE;
    $data['is_public'] = TRUE;
    $data['date_to'] = NULL;
    $data['date_admission'] = NULL;
    $data['presale_end'] = NULL;
    $data['seating_plan'] = NULL;
    $data['seat_category_mapping'] = (object) [];
    $price = TRUE === $item->field_pretix_free->value() ? 0 : (float) $item->field_pretix_price->value();

    $data['item_price_overrides'][0]['price'] = $price;

    // Attempts to fix an issue where integration would fail if an empty array or object was passed.
    if (empty($data['frontpage_text']) || !is_string($data['frontpage_text'])) {
      $data['frontpage_text'] = NULL;
    }

    // Important: meta_data value must be an object!
    $data['meta_data'] = (object) [];
    $subEventData = [];
    if ($isNewItem) {
      $result = $client->createSubEvent($event->slug, $data);
      if (isset($result->error)) {
        return $this->apiError($result, 'Cannot create sub-event');
      }
    }
    else {
      $subEventId = $itemInfo['pretix_subevent_id'];
      $result = $client->updateSubEvent($event->slug, $subEventId, $data);
      if (isset($result->error)) {
        return $this->apiError($result, 'Cannot update sub-event');
      }
    }

    $subEvent = $result->data;

    // Get sub-event quotas.
    $result = $client->getQuotas($event, ['query' => ['subevent' => $subEvent->id]]);
    if (isset($result->error)) {
      return $this->apiError($result, 'Cannot get sub-event quotas');
    }

    if (0 === $result->data->count) {
      // Create a new quota for the sub-event.
      $result = $client->getQuotas(
        $templateEvent,
        ['subevent' => $templateSubEvent->id]
      );
      if (isset($result->error) || 0 === $result->data->count) {
        return $this->apiError($result, 'Cannot get template sub-event quotas');
      }

      $templateQuota = $result->data->results[0];
      unset($templateQuota->id, $templateQuota->subevent);
      $data = (array) $templateQuota;
      $data['subevent'] = $subEvent->id;
      $data['items'] = [$product->id];
      $result = $client->createQuota($event, $data);
      if (isset($result->error)) {
        return $this->apiError($result, 'Cannot create quota for sub-event');
      }
    }

    // Update the quota.
    $result = $client->getQuotas($event, ['query' => ['subevent' => $subEvent->id]]);
    if (isset($result->error) || 1 !== $result->data->count) {
      return $this->apiError($result, 'Cannot get sub-event quota');
    }

    $quota = $result->data->results[0];

    $size = (int) $item->field_pretix_spaces->value();
    $data = ['size' => $size];
    $result = $client->updateQuota($event, $quota, $data);
    if (isset($result->error)) {
      return $this->apiError($result, 'Cannot update sub-event quota');
    }

    $subEventData['subevent'] = $subEvent;
    $orderHelper = OrderHelper::create()->setClient($client);
    $availability = $orderHelper->getSubEventAvailability($subEvent);
    if (!$this->isApiError($result)) {
      $subEventData['availability'] = $availability->data->results;
    }
    $info = $this->addPretixSubEventInfo($item, $subEvent, $subEventData);

    return [
      'status' => $isNewItem ? 'created' : 'updated',
      'info' => $info,
    ];
  }

  /**
   * Get data from some value.
   *
   * @param mixed $value
   *   Something that may be converted to a DateTime.
   *
   * @return \DateTime|null
   *   The date.
   *
   * @throws \Exception
   */
  private function getDate($value) {
    if (NULL === $value) {
      return NULL;
    }

    if ($value instanceof \DateTime) {
      return $value;
    }

    if (is_numeric($value)) {
      return new \DateTime('@' . $value);
    }

    return new \DateTime($value);
  }

  /**
   * Format a date as a string.
   *
   * @param mixed|null $date
   *   The date.
   *
   * @return string|null
   *   The string representation of the date.
   *
   * @throws \Exception
   */
  private function formatDate($date = NULL) {
    $date = $this->getDate($date);

    if (NULL === $date) {
      return NULL;
    }

    return $date->format(\DateTime::ATOM);
  }

  /**
   * Get pretix template event slug.
   *
   * @param object $node
   *   The node.
   *
   * @return string|null
   *   The template event slug if any.
   */
  private function getPretixTemplateEventSlug($node) {
    $info = $this->loadPretixEventInfo($node);

    return $info['data']['template_event_slug'] ?? NULL;
  }

  /**
   * Get an organizer (user) by slug.
   *
   * @param string $organizerSlug
   *   The organizer slug.
   *
   * @return bool|object
   *   The organizer if found.
   */
  public function getOrganizer($organizerSlug) {
    $query = new \EntityFieldQuery();
    $query->entityCondition('entity_type', 'user')
      ->fieldCondition('field_pretix_organizer_slug', 'value', $organizerSlug, '=');
    $results = $query->execute();

    if (isset($results['user'])) {
      $uids = array_keys($results['user']);
      $uid = reset($uids);

      return user_load($uid);
    }

    return NULL;
  }

  public static function getSharedEvents($id) {
    $query = db_query('SELECT entity_id FROM {field_data_field_pretix_show_widget_from} WHERE field_pretix_show_widget_from_target_id = :id', ['id' => $id]);
    $result = $query->fetchAllKeyed(0,0);
    return array_values($result);
  }

}
