<?php

namespace Drupal\os2uol_domain\Plugin\views\field;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to output site's base url based on headless
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("headless_base_url")
 */
class HeadlessBaseUrl extends FieldPluginBase {

  protected DomainNegotiatorInterface $domainNegotiator;
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DomainNegotiatorInterface $domainNegotiator, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->domainNegotiator = $domainNegotiator;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('domain.negotiator'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Intentionally left empty, as we don't need to do anything here.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $current_domain_id = $this->domainNegotiator->getActiveId();

    $headless_base_url = $this->configFactory
      ->get('headless_domain_simple_sitemap.settings')
      ->get($current_domain_id);

    if (!empty($headless_base_url)) {
      // Strip trailing slash.
      $headless_base_url = rtrim($headless_base_url, '/');

      return [
        '#plain_text' => $headless_base_url,
      ];
    }

    // Fallback to base_url if headless_base_url is not set.
    global $base_url;

    return [
      '#plain_text' => $base_url,
    ];
  }

}
