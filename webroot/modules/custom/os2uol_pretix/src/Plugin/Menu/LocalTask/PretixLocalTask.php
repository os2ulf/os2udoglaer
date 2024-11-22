<?php

declare(strict_types = 1);

namespace Drupal\os2uol_pretix\Plugin\Menu\LocalTask;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\scheduled_transitions\ScheduledTransitionsUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a local task showing the pretix tab for an entity.
 */
class PretixLocalTask extends LocalTaskDefault implements ContainerFactoryPluginInterface {

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
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    protected RouteMatchInterface $routeMatch
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    // Hide task if content doesn't support Pretix
    if (!$this->supportsPretix()) {
      // Only way I could find to hide a link. May only work on the Gin theme
      //$options['attributes']['class'][] = 'js-hide';
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url';
    return $contexts;
  }

  protected function supportsPretix() {
    if (empty($this->pluginDefinition['base_route'])) {
      return TRUE;
    } elseif ($entity = $this->getEntityFromRouteMatch()) {
      return ($entity->hasField('field_pretix_template_event') && $entity->hasField('field_pretix_event_short_form'));
    } else {
      return FALSE;
    }
  }

  /**
   * Get entity from route match.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity from route match.
   */
  protected function getEntityFromRouteMatch(): ?ContentEntityInterface {
    $route_parts = explode('.', $this->pluginDefinition['base_route']);
    $entityTypeId = $route_parts[1] ?? '';

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
