<?php

namespace Drupal\os2uol_pretix;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\UserInterface;

class PretixOrderManager extends PretixAbstractManager {

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

  const PRETIX_EVENT_ORDER_PAID_TEMPLATE = 'ulf_pretix_event_order_paid_template';

  const PRETIX_EVENT_ORDER_CANCELED_TEMPLATE = 'ulf_pretix_event_order_canceled_template';

  /**
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private DateFormatterInterface $dateFormatter;

  /**
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  private MailManagerInterface $mailManager;

  public function __construct(
    PretixConnector $connector,
    LoggerChannelFactoryInterface $logger_factory,
    MessengerInterface $messenger,
    DateFormatterInterface $date_formatter,
    MailManagerInterface $mail_manager)
  {
    parent::__construct($connector, $logger_factory, $messenger);
    $this->dateFormatter = $date_formatter;
    $this->mailManager = $mail_manager;
  }

  /**
   * Ensure that the Pretix callback webhook exists.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   *
   * @return array
   *   The result from calling the Pretix api.
   */
  public function ensureWebhook(UserInterface $user): array {
    if (!$this->isPretixEnabledUser($user)) {
      return [];
    }

    $client = $this->getClientByUser($user);

    $targetUrl = new Url('os2uol_pretix.webhooks.order', ['organizer' => $client->getOrganizer()], ['absolute' => TRUE]);
    $existingWebhook = NULL;

    $webhooks = $client->getWebhooks();
    if (isset($webhooks['results'])) {
      foreach ($webhooks['results'] as $webhook) {
        if ($targetUrl->toString() === $webhook['target_url']) {
          $existingWebhook = $webhook;
          break;
        }
      }
    }

    $actionTypes = [
      self::PRETIX_EVENT_ORDER_PLACED,
      self::PRETIX_EVENT_ORDER_PLACED_REQUIRE_APPROVAL,
      self::PRETIX_EVENT_ORDER_PAID,
      self::PRETIX_EVENT_ORDER_CANCELED,
      self::PRETIX_EVENT_ORDER_EXPIRED,
      self::PRETIX_EVENT_ORDER_MODIFIED,
      self::PRETIX_EVENT_ORDER_CONTACT_CHANGED,
      self::PRETIX_EVENT_ORDER_CHANGED,
      self::PRETIX_EVENT_ORDER_REFUND_CREATED_EXTERNALLY,
      self::PRETIX_EVENT_ORDER_APPROVED,
      self::PRETIX_EVENT_ORDER_DENIED,
      self::PRETIX_EVENT_CHECKIN,
      self::PRETIX_EVENT_CHECKIN_REVERTED,
    ];

    $webhookSettings = [
      'target_url' => $targetUrl->toString(),
      'enabled' => TRUE,
      'all_events' => TRUE,
      'limit_events' => [],
      'action_types' => $actionTypes,
    ];

    return NULL === $existingWebhook
      ? $client->createWebhook($webhookSettings)
      : $client->updateWebhook($existingWebhook, $webhookSettings);
  }

  /**
   * Handle order updated.
   *
   * @param array $payload
   *   The payload.
   * @param string $action
   *   The action.
   *
   * @return array
   *   The payload.
   */
  public function handleOrderUpdated(array $payload, string $action): array {
    $organizerSlug = $payload['organizer'] ?? NULL;
    $eventSlug = $payload['event'] ?? NULL;
    $orderCode = $payload['code'] ?? NULL;

    /** @var EditorialContentEntityBase $entity */
    $entity = $this->getEntity($organizerSlug, $eventSlug);

    if (NULL !== $entity && $entity instanceof Node) {
      $this->logger->info('Pretix webhook handled of type @action', ['@action' => $action]);
      $this->notifyEventChanged($entity);
      $mailKey = match ($action) {
        PretixOrderManager::PRETIX_EVENT_ORDER_PLACED => self::PRETIX_EVENT_ORDER_PAID_TEMPLATE,
        PretixOrderManager::PRETIX_EVENT_ORDER_CANCELED => self::PRETIX_EVENT_ORDER_CANCELED_TEMPLATE,
        default => '',
      };
      if (empty($mailKey)) {
        return $payload;
      }

      $texts = $this->renderMail($entity, $orderCode, $action);

      $to = implode(',', $this->getMailRecipients($entity));
      $langcode = $entity->language()->getId();
      /** @var EntityOwnerTrait $entity_owner */
      $entity_owner = $entity;

      $params = [
        'entity' => $entity,
        'user' => $entity_owner->getOwner(),
        'subject' => $texts['subject'],
        'content' => $texts['body']
      ];

      $result = $this->mailManager->mail('os2uol_pretix', $mailKey, $to, $langcode, $params);
    }

    return $payload;
  }

  public function getOrderByIds($organizerSlug, $eventSlug, $orderCode) {
    $entity = $this->getEntity($organizerSlug, $eventSlug);
    return $this->getOrder($entity, $orderCode);
  }

  public function getOrder(EditorialContentEntityBase $entity, $orderCode): array {
    $eventSlug = $this->getEventSlug($entity);
    $client = $this->getClient($entity);
    $order = $client->getOrder($this->getEventSlug($entity), $orderCode);
    if ($this->isApiError($order)) {
      return $this->apiError($order, 'Cannot get order');
    }

    // Get all items.
    $result = $client->getProducts($eventSlug);
    if ($this->isApiError($result)) {
      return $this->apiError($result, 'Cannot get event items');
    }
    // Index by id.
    $items = array_column($result['results'], NULL, 'id');

    // Get all quotas.
    $result = $client->getQuotas($eventSlug);
    if ($this->isApiError($result)) {
      return $this->apiError($result, 'Cannot get event quotas');
    }
    $allQuotas = $result['results'];
    // Index quotas by item id and sub-event id and augment with availability.
    $quotas = [];
    foreach ($allQuotas as $quota) {
      foreach ($quota['items'] as $itemId) {
        $result = $client->getQuotaAvailability($eventSlug, $quota['id']);
        if ($this->isApiError($result)) {
          return $this->apiError($result, 'Cannot get quota availability');
        }
        $quota['availability'] = $result;
        $quotas[$itemId][$quota['subevent']][] = $quota;
      }
    }

    // Get sub-events.
    $result = $client->getSubEvents($eventSlug);
    if ($this->isApiError($result)) {
      return $this->apiError($result, 'Cannot get sub-events');
    }
    // Index by id.
    $subEvents = array_column($result['results'], NULL, 'id');

    // Add quotas and expanded sub-events to order lines.
    foreach ($order['positions'] as $key => $position) {
      $order['positions'][$key]['quotas'] = $quotas[$position['item']][$position['subevent']];
      $order['positions'][$key]['subevent'] = $subEvents[$position['subevent']];
    }

    return $order;
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
  public function getQuestions(EditorialContentEntityBase $entity) {
    $client = $this->getClient($entity);
    $questions = [];
    $data = $client->getQuestions($this->getEventSlug($entity));

    if($this->isApiError($data)) {
      $this->apiError($data, 'Cannot get questions');
    }

    $data = $data['results'];

    if (!empty($data)) {
      foreach ($data as $item) {
        $questions[$item['id']] = $item['question'][array_key_first($item['question'])];
      }
    }

    return $questions;
  }

  /**
   * @param \Drupal\Core\Entity\EditorialContentEntityBase $entity
   *
   * @return array
   */
  public function getMailRecipients(EditorialContentEntityBase $entity): array {
    $recipients = [];
    foreach ($entity->get('field_pretix_email_notifiers')->getValue() as $value) {
      $recipients[] = $value['value'];
    }
    return $recipients;
  }

  /**
   * Get order lines grouped by sub-event.
   *
   * @param array $order
   *   The pretix order.
   *
   * @return array
   *   The order lines.
   */
  public function getOrderLines(array $order): array {
    $lineNumber = 1;
    $orderLines = [];

    foreach ($order['positions'] as $position) {
      if (isset($orderLines[$position['subevent']['id']])) {
        $orderLines[$position['subevent']['id']]['quantity'] += 1;
        $orderLines[$position['subevent']['id']]['total_price'] += $position['price'];
      }
      else {
        $subEvent = $position['subevent'];
        $name = $subEvent['name'];
        $orderLines[$subEvent['id']] = [
          'line_number' => $lineNumber,
          'quantity' => 1,
          'name' => $name,
          'date_from' => new DrupalDateTime($subEvent['date_from']),
          'date_to' => empty($subEvent['date_to']) ? NULL : new DrupalDateTime($subEvent['date_to']),
          'presale_start' => empty($subEvent['presale_start']) ? NULL : new DrupalDateTime($subEvent['presale_start']),
          'presale_end' => empty($subEvent['presale_end']) ? NULL : new DrupalDateTime($subEvent['presale_end']),
          'item_price' => $position['price'],
          'total_price' => $position['price'],
          'quotas' => $position['quotas'],
          'answers' => $position['answers'],
        ];
      }
    }

    return $orderLines;
  }

  /**
   * Render pretix order as plain text.
   *
   * @param array $order
   *   The pretix order.
   * @param array[] $orderLines
   *   The order lines.
   *
   * @return array|string[]
   *   The textual representation of the order.
   */
  private function renderOrder(EntityInterface $entity, array $order, array $orderLines): array {
    if ($entity instanceof EntityOwnerInterface) {
      $owner = $entity->getOwner();
      $name = $owner->getDisplayName();
    }

    $blocks = [];

    foreach ($orderLines as $line) {
      /** @var DrupalDateTime $date_from */
      $date_from = $line['date_from'];
      $block = [
        [
          $line['name']['da'] ?? $line['name']['en'],
        ],
        [
          t('Start time') . ':',
          $this->dateFormatter->format($date_from->getTimestamp(), 'long'),
        ],
        [
          t('Quantity') . ':',
          $line['quantity'],
        ],
      ];

      if (isset($line['quotas'])) {
        foreach ($line['quotas'] as $quota) {
          $block[] = [
            t('Availability') . ':',
            t('@count of @total', ['@count' => $quota['availability']['available_number'], '@total' => $quota['availability']['total_size']]),
          ];
        }
      }

      if ($line['item_price'] > 0) {
        $block[] = [
          t('Item price') . ':',
          number_format($line['item_price'], 2),
        ];
        $block[] = [
          t('Total price') . ':',
          number_format($line['total_price'], 2),
        ];
      }

      if($line['answers']) {
        foreach($line['answers'] as $answer) {
          $question = $order['questions'][$answer['question']] ?? NULL;
          if($question) {
            $block[] = [
              $question . ': ',
              $answer['answer'],
            ];
          }
        }
      }

      $block[] = [''];

      $blocks[] = $block;

    }

    return array_map(static function ($line) {
      return 2 === count($line)
        ? sprintf('%-16s%s', $line[0], $line[1])
        : sprintf('%s', $line[0]);
    }, array_merge(...$blocks));
  }

  public function renderMailByIds(string $organizerSlug, string $eventSlug, string $orderCode, string $action): ?array {
    $entity = $this->getEntity($organizerSlug, $eventSlug);
    if ($entity instanceof Node) {
      return $this->renderMail($entity, $orderCode, $action);
    } else {
      return NULL;
    }
  }

  public function renderMail(Node $node, string $orderCode, string $action): array {
    $config = \Drupal::config('os2uol_settings.settings');
    $order = $this->getOrder($node, $orderCode);

    switch ($action) {
      case PretixOrderManager::PRETIX_EVENT_ORDER_PLACED:
        $subject_template = $config->get('pretix_order_placed_subject') ?? '';
        $body_template = $config->get('pretix_order_placed_message')['value'] ?? '';
        break;

      case PretixOrderManager::PRETIX_EVENT_ORDER_CANCELED:
        $subject_template = $config->get('pretix_order_canceled_subject') ?? '';
        $body_template = $config->get('pretix_order_canceled_message')['value'] ?? '';
        break;

      default:
        return [];
    }

    $token = \Drupal::token();
    $subject = $token->replace($subject_template, ['node' => $node, 'user' => $node->getOwner(), 'pretix_order' => $order]);
    $markup = $token->replace($body_template, ['node' => $node, 'user' => $node->getOwner(), 'pretix_order' => $order]);

    return [
      'subject' => $subject,
      'body' => $markup
    ];
  }

  /**
   * @param $organizerSlug
   * @param $eventSlug
   *
   * @return \Drupal\Core\Entity\EditorialContentEntityBase|null
   */
  protected function getEntity($organizerSlug, $eventSlug): ?EditorialContentEntityBase {
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
      /** @var EditorialContentEntityBase $entity */
      $entity = $nodeStorage->load($ids[array_key_first($ids)]);
    }

    return $entity;
  }

}
