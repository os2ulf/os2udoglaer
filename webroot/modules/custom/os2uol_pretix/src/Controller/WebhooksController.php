<?php

namespace Drupal\os2uol_pretix\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\os2uol_pretix\PretixBannerManager;
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
   * @var \Drupal\os2uol_pretix\PretixBannerManager
   */
  private PretixBannerManager $bannerManager;

  public function __construct(PretixOrderManager $orderManager, PretixBannerManager $bannerManager) {
    $this->orderManager = $orderManager;
    $this->bannerManager = $bannerManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('os2uol_pretix.order_manager'),
      $container->get('os2uol_pretix.banner_manager')
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
    return new JsonResponse($this->orderManager->handleOrderUpdated($payload, $action));
  }

  public function process() {
    $this->bannerManager->processQueue();
    return new JsonResponse(['status' => 'processed queue']);
  }

  public function viewOrder($organizerSlug, $eventSlug, $orderCode, Request $request) {
    $mail = $this->orderManager->renderMailByIds($organizerSlug, $eventSlug, $orderCode, PretixOrderManager::PRETIX_EVENT_ORDER_PLACED);

    return [
      [
        '#type' => 'item',
        '#title' => $mail['subject'],
      ],
      [
        '#markup' => $mail['body']
      ]
    ];
  }
}
