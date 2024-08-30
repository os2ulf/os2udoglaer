<?php

declare(strict_types = 1);

namespace Drupal\os2uol_pretix\Plugin\Menu\LocalAction;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines local action class.
 */
class PretixLocalAction extends LocalActionDefault implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new ScheduledTransitionsLocalTask.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    RouteProviderInterface $route_provider,
    protected RouteMatchInterface $routeMatch
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_provider);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.route_provider'),
      $container->get('current_route_match')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    $options['query']['destination'] = Url::fromRoute('<current>')->toString();
    // Hide action if content doesn't support Pretix
    if (!$this->supportsPretix()) {
      // Only way I could find to hide a link. May only work on the Gin theme
      $options['attributes']['class'][] = 'js-hide';
    }
    return $options;
  }

  protected function supportsPretix() {
    if (empty($this->pluginDefinition['base_route'])) {
      return TRUE;
    } elseif ($entity = $this->getEntityFromRouteMatch()) {
      if ($entity->hasField('field_pretix_template_event') && $entity->hasField('field_pretix_event_short_form')) {
        return !$entity->get('field_pretix_event_short_form')->isEmpty();
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url';
    return $contexts;
  }

  /**
   * Get entity from route match.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity from route match.
   */
  protected function getEntityFromRouteMatch(): ?ContentEntityInterface {
    [1 => $entityTypeId] = explode('.', $this->pluginDefinition['base_route']);

    // Get the first parameter in the route definition matching the entity type,
    // since the upcasted entity parameter could be something like {entity}.
    $parameters = $this->routeMatch->getParameters()->all();
    foreach ($parameters as $parameter) {
      if ($parameter instanceof ContentEntityInterface && $parameter->getEntityTypeId() === $entityTypeId) {
        return $parameter;
      }
    }

    return NULL;
  }

}
