<?php

namespace Drupal\os2uol_pretix\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\os2uol_pretix\PretixOrderManager;
use Drupal\os2uol_pretix\PretixEventManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;

class WebhooksController extends ControllerBase {

  /**
   * @var \Drupal\os2uol_pretix\PretixOrderManager
   */
  private PretixOrderManager $orderManager;

  /**
   * @var \Drupal\os2uol_pretix\PretixEventManager
   */
  private PretixEventManager $eventManager;

  public function __construct(PretixOrderManager $orderManager, PretixEventManager $eventManager) {
    $this->orderManager = $orderManager;
    $this->eventManager = $eventManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('os2uol_pretix.order_manager'),
      $container->get('os2uol_pretix.pretix_event_manager')
    );
  }

  /**
   * Handles Pretix webhook actions.
   */
  public function order($organizer, Request $request) {
    $payload = json_decode($request->getContent(), TRUE);

    if (empty($payload)) {
      throw new BadRequestHttpException('Invalid or empty payload');
    }

    $action = $payload['action'] ?? NULL;
    switch ($action) {
      case PretixOrderManager::PRETIX_EVENT_ORDER_PLACED:
      case PretixOrderManager::PRETIX_EVENT_ORDER_CANCELED:
        return $this->orderManager->handleOrderUpdated($payload, $action);

      case 'event.soldout':
        return $this->handleSoldOutEvent($payload);

      case 'event.available':
        return $this->handleAvailableEvent($payload);
    }

    return new JsonResponse(['status' => 'unhandled action']);
  }

  /**
   * Handle the sold-out event.
   */
  public function handleSoldOutEvent(array $payload) {
    $eventId = $payload['event']['id'];
    $nodeId = $this->getNodeIdFromPretixEventId($eventId);
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nodeId);

    if ($node) {
      // Set the Boolean field for "Sold Out" to TRUE.
      $node->set('field_sold_out', TRUE);
      $node->save();
    }

    return new JsonResponse(['status' => 'event marked as sold out']);
  }

  /**
   * Handle the available event (when tickets become available again).
   */
  public function handleAvailableEvent(array $payload) {
    $eventId = $payload['event']['id'];
    $nodeId = $this->getNodeIdFromPretixEventId($eventId);
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nodeId);

    if ($node) {
      // Set the Boolean field for "Sold Out" to FALSE.
      $node->set('field_sold_out', FALSE);
      $node->save();
    }

    return new JsonResponse(['status' => 'event marked as available']);
  }

  /**
   * Unlinks the Pretix event from a Drupal node.
   *
   * @param int $node
   *   The ID of the node representing the Drupal event.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response indicating the result.
   */
  public function unlinkEvent($node) {
    // Call the unlinkEvent method from PretixEventManager
    return $this->eventManager->unlinkEvent($node);
  }

  /**
   * Maps the Pretix event ID to a Drupal node ID for allowed content types.
   *
   * @param string $eventId
   *   The Pretix event ID.
   *
   * @return int|null
   *   The Drupal node ID corresponding to the Pretix event, or NULL if not found.
   */
  private function getNodeIdFromPretixEventId($eventId) {
    // Use entityQuery to search for a node where 'field_pretix_event_id' matches the Pretix event ID.
    // Limit the search to specific content types that can have Pretix events.
    $query = \Drupal::entityQuery('node')
      ->condition('type', ['internship', 'course_educators', 'course'], 'IN')
      ->condition('field_pretix_event_id', $eventId)
      ->range(0, 1);

    $nids = $query->execute();

    if (!empty($nids)) {
      // Return the first node ID found.
      return reset($nids);
    }

    // Return NULL if no node was found.
    return NULL;
  }
}
