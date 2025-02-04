<?php

namespace Drupal\os2uol_domain\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles matching of current domain.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("domain_access_user_current_filter")
 */
class DomainAccessUserCurrentFilter extends FilterPluginBase {

  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->domainNegotiator = $container->get('domain.negotiator');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->value = $this->domainNegotiator->getActiveId();
    parent::query();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    $contexts[] = 'url.site';

    return $contexts;
  }

}
