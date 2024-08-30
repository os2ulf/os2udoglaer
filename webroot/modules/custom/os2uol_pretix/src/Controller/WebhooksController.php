<?php

namespace Drupal\os2uol_pretix\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\os2uol_pretix\PretixOrderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WebhooksController extends ControllerBase {

  /**
   * @var \Drupal\os2uol_pretix\PretixOrderManager
   */
  private PretixOrderManager $orderManager;

  public function __construct(PretixOrderManager $orderManager) {
    $this->orderManager = $orderManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('os2uol_pretix.order_manager')
    );
  }

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
    }

    return $payload;
  }

  public function test(NodeInterface $node, $order) {
    $content = $this->orderManager->viewOrder($node, $order);
    $build = [];
    foreach ($content as $line) {
      $build[] = [
        '#markup' => $line . '<br/>'
      ];
    }
    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
