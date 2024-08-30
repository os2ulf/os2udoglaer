<?php

namespace Drupal\ulf_pretix\Pretix;

/**
 * Pretix order helper.
 */
class OrderHelper extends AbstractHelper {

  const PRETIX_EVENT_ORDER_PLACED = 'pretix.event.order.placed';

  const PRETIX_EVENT_ORDER_PLACED_REQUIRE_APPROVAL = 'pretix.event.order.placed.require_approval';

  const PRETIX_EVENT_ORDER_PAID = 'pretix.event.order.paid';

  const PRETIX_EVENT_ORDER_CANCELED = 'pretix.event.order.canceled';

  const PRETIX_EVENT_ORDER_EXPIRED = 'pretix.event.order.expired';

  const PRETIX_EVENT_ORDER_MODIFIED = 'pretix.event.order.modified';

  const PRETIX_EVENT_ORDER_CONTACT_CHANGED = 'pretix.event.order.contact.changed';

  const PRETIX_EVENT_ORDER_CHANGED = 'pretix.event.order.changed.*';

  const PRETIX_EVENT_ORDER_REFUND_CREATED_EXTERNALLY = 'pretix.event.order.refund.created.externally';

  const PRETIX_EVENT_ORDER_APPROVED = 'pretix.event.order.approved';

  const PRETIX_EVENT_ORDER_DENIED = 'pretix.event.order.denied';

  const PRETIX_EVENT_CHECKIN = 'pretix.event.checkin';

  const PRETIX_EVENT_CHECKIN_REVERTED = 'pretix.event.checkin.reverted';

  /**
   * Get pretix order augmented with quota information and expanded sub-events.
   *
   * @param string|object $organizer
   *   The organizer (slug).
   * @param string|object $event
   *   The event (slug).
   * @param string $orderCode
   *   The order code.
   *
   * @return array|object
   *   The pretix order object or an error.
   */
  public function getOrder($organizer, $event, $orderCode) {
    // Get order.
    $orderResult = $this->client->getOrder($organizer, $event, $orderCode);
    if ($this->isApiError($orderResult)) {
      return $this->apiError($orderResult, 'Cannot get order');
    }
    $order = $orderResult->data;

    // Get all items.
    $result = $this->client->getItems($event);
    if ($this->isApiError($result)) {
      return $this->apiError($result, 'Cannot get event items');
    }
    // Index by id.
    $items = array_column($result->data->results, NULL, 'id');

    // Get all quotas.
    $result = $this->client->getQuotas($event);
    if ($this->isApiError($result)) {
      return $this->apiError($result, 'Cannot get event quotas');
    }
    $allQuotas = $result->data->results;
    // Index quotas by item id and sub-event id and augment with availability.
    $quotas = [];
    foreach ($allQuotas as $quota) {
      foreach ($quota->items as $itemId) {
        $result = $this->client->getQuotaAvailability($event, $quota);
        if ($this->isApiError($result)) {
          return $this->apiError($result, 'Cannot get quota availability');
        }
        $quota->availability = $result->data;
        $quotas[$itemId][$quota->subevent][] = $quota;
      }
    }

    // Get sub-events.
    $result = $this->client->getSubEvents($event);
    if ($this->isApiError($result)) {
      return $this->apiError($result, 'Cannot get sub-events');
    }
    // Index by id.
    $subEvents = array_column($result->data->results, NULL, 'id');

    // Add quotas and expanded sub-events to order lines.
    foreach ($order->positions as $position) {
      $position->quotas = $quotas[$position->item][$position->subevent];
      $position->subevent = $subEvents[$position->subevent];
    }

    return $orderResult;
  }

  /**
   * Get order lines grouped by sub-event.
   *
   * @param object $order
   *   The pretix order.
   *
   * @return array
   *   The order lines.
   *
   * @throws \Exception
   */
  public function getOrderLines($order) {
    $orderLines = [];

    foreach ($order->positions as $position) {
      if (isset($orderLines[$position->subevent->id])) {
        $orderLines[$position->subevent->id]->quantity += 1;
        $orderLines[$position->subevent->id]->total_price += $position->price;
      }
      else {
        $subEvent = $position->subevent;
        $name = (array) $subEvent->name;
        $orderLines[$position->subevent->id] = (object) [
          'quantity' => 1,
          'name' => $name,
          'date_from' => new \DateTime($subEvent->date_from),
          'item_price' => $position->price,
          'total_price' => $position->price,
          'quotas' => $position->quotas,
          'answers' => $position->answers,
        ];
      }
    }

    return $orderLines;
  }

  /**
   * Get availability information for a pretix event.
   *
   * @param object $node
   *   The node.
   *
   * @return array
   *   The node's event's quotas.
   */
  public function getAvailability($node) {
    $this->setPretixClient($node);
    $event = $node->pretix['pretix_event_slug'];

    $result = $this->client->getSubEvents($event);
    if ($this->isApiError($result)) {
      return $this->apiError($result, 'Cannot get sub-events');
    }
    $subEvents = array_column($result->data->results, NULL, 'id');

    $result = $this->client->getQuotas($event);
    if ($this->isApiError($result)) {
      return $this->apiError($result, 'Cannot get quotas');
    }
    $quotas = array_column($result->data->results, NULL, 'id');

    $quotas = array_filter(
      $quotas, static function ($quota) use ($subEvents) {
      return isset($quota->subevent, $subEvents[$quota->subevent]);
    }
    );

    foreach ($quotas as $quota) {
      $result = $this->client->getQuotaAvailability($event, $quota);
      if ($this->isApiError($result)) {
        return $this->apiError($result, 'Cannot get quota availability');
      }
      $quota->availability = $result->data;
    }

    return $quotas;
  }

  /**
   * Get sub-event availability from pretix.
   *
   * @param object $subEvent
   *   The sub-event.
   *
   * @return object
   *   A pretix API result with quotas enriched with availability information.
   */
  public function getSubEventAvailability($subEvent) {
    $event = $subEvent->event;
    $quotasResult
      = $this->client->getQuotas($event, ['query' => ['subevent' => $subEvent->id]]);
    if ($this->isApiError($quotasResult)) {
      return $this->apiError($quotasResult, 'Cannot get quotas for sub-event');
    }
    $quotas = $quotasResult->data->results;

    foreach ($quotas as $quota) {
      $result = $this->client->getQuotaAvailability($event, $quota);
      if ($this->isApiError($result)) {
        return $this->apiError($result, 'Cannot get quota availability');
      }
      $quota->availability = $result->data;
    }

    return $quotasResult;
  }

  /**
   * Get the questions for a particular event
   *
   * @param $organizer
   *   The organizer slug.
   * @param $event
   *   The event slug.
   *
   * @return array
   *   All questions for the event.
   */
  public function getQuestions($organizer, $event) {
    $questions = [];
    $data = $this->client->getQuestions($organizer, $event);

    if($this->isApiError($data)) {
      $this->apiError($data, 'Cannot get questions');
    }

    $data = $data->data->results;

    if (!empty($data)) {
      foreach ($data as $item) {
        $questions[$item->id] = $item->question->da;
      }
    }

    return $questions;
  }

}
