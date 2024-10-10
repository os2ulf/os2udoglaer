<?php

namespace Drupal\os2uol_pretix\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\NodeInterface;
use Drupal\os2uol_pretix\PretixEventManager;

/**
 * Class PretixController.
 */
class PretixController extends ControllerBase {

  /**
   * @var \Drupal\os2uol_pretix\PretixEventManager
   */
  private PretixEventManager $eventManager;

  /**
   * Constructs a new PretixController.
   *
   * @param \Drupal\os2uol_pretix\PretixEventManager $eventManager
   *   Pretix event manager service.
   */
  public function __construct(PretixEventManager $eventManager) {
    $this->eventManager = $eventManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('os2uol_pretix.pretix_event_manager')
    );
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
  public function removeEventConnection($node) {
    // Call the unlinkEvent method from PretixEventManager
    $result = $this->eventManager->unlinkEvent($node);

    return new JsonResponse([
      'status' => $result ? 'success' : 'failed',
    ]);
  }

}
