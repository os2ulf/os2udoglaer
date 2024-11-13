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

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new BannerQueueWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $logger) {
      parent::__construct($configuration, $plugin_id, $plugin_definition);
      $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration,
        $plugin_id,
        $plugin_definition,
        $container->get('logger.channel.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
      // Process the item in the queue
      $this->logger->info('Processing data: @data', ['@data' => json_encode($data)]);
  }

}
