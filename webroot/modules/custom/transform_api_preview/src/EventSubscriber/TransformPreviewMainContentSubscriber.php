<?php

namespace Drupal\transform_api_preview\EventSubscriber;

use Drupal\transform_api\Event\TransformMainContentEvent;
use Drupal\transform_api\TransformEvents;
use Drupal\transform_api_preview\TransformPreview;
use Drupal\transform_api_views\Transform\ViewTransform;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles canonical content entity urls as entity transforms.
 */
class TransformPreviewMainContentSubscriber implements EventSubscriberInterface {

  protected TransformPreview $transformPreview;

  function __construct(TransformPreview $transformPreview) {
    $this->transformPreview = $transformPreview;
  }

  /**
   * Handle entity routes as entity transforms.
   *
   * @param \Drupal\transform_api\Event\TransformMainContentEvent $event
   *   The Event to process.
   */
  public function onMainContent(TransformMainContentEvent $event) {
    $match = $event->getRouteMatch();
    if ($match->getRouteObject()->getDefaults()['_controller'] == '\Drupal\transform_api_preview\Controller\TransformPreviewController::preview' && !empty($match->getParameter('uuid')) && !empty($match->getParameter('transform_mode'))) {
      $uuid = $match->getParameter('uuid');
      $transform_mode = $match->getParameter('transform_mode');
      $transform = $this->transformPreview->getTransform($uuid, $transform_mode);
      if (!is_null($transform)) {
        $event->setTransform($transform);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[TransformEvents::MAIN_CONTENT][] = ['onMainContent'];
    return $events;
  }

}
