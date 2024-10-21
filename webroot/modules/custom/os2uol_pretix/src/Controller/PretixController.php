<?php

namespace Drupal\os2uol_pretix\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
   * @param int $node_id
   *   The ID of the node representing the Drupal event.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response indicating the result.
   */
  public function removeEventConnection($node_id) {
    // Load the node entity using the NodeInterface.
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node_id);

    // Check if the node exists and is an instance of NodeInterface.
    if (!$node instanceof NodeInterface) {
      \Drupal::logger('os2uol_pretix')->error('Failed to load node with ID: ' . $node_id);
      throw new NotFoundHttpException('Node not found.');
    }

    // Call the unlinkEvent method from PretixEventManager.
    try {
      $result = $this->eventManager->unlinkEvent($node);

      if ($result) {
        \Drupal::logger('os2uol_pretix')->notice('Successfully removed Pretix event connection for node ID: ' . $node_id);
        return new JsonResponse(['status' => 'success', 'message' => 'Event connection removed.']);
      }
      else {
        \Drupal::logger('os2uol_pretix')->warning('Failed to remove Pretix event connection for node ID: ' . $node_id);
        return new JsonResponse(['status' => 'failed', 'message' => 'Failed to remove event connection.']);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('os2uol_pretix')->error('Error while removing Pretix event connection: ' . $e->getMessage());
      return new JsonResponse(['status' => 'failed', 'message' => 'An error occurred while removing the event connection.'], 500);
    }
  }

}
