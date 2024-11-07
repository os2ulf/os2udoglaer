<?php

namespace Drupal\os2uol_pretix\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @QueueWorker(
 *   id = "pretix_banner",
 *   title = @Translation("Pretix banner worker"),
 *   cron = {"time" = 60}
 * )
 */
class BannerQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // TODO: Implement create() method.
  }

  public function processItem($data) {
    // TODO: Implement processItem() method.
  }

}
