<?php

namespace Drupal\headless_domain_simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\domain_simple_sitemap\Plugin\simple_sitemap\UrlGenerator\DomainEntityUrlGenerator;
use Drupal\simple_sitemap\Entity\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Manager\EntityManager;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager;
use Drupal\simple_sitemap\Settings;

/**
 * Generates URLs for entity bundles and bundle overrides.
 *
 * @UrlGenerator(
 *   id = "headless_domain_entity",
 *   label = @Translation("Headless domain entity URL generator"),
 *   description = @Translation("Generates URLs for entity bundles and bundle
 *   overrides."),
 * )
 */
class HeadlessDomainUrlGenerator extends DomainEntityUrlGenerator {

  protected ImmutableConfig $config;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, Logger $logger, Settings $settings, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, EntityHelper $entity_helper, EntityManager $entities_manager, UrlGeneratorManager $url_generator_manager, MemoryCacheInterface $memory_cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $settings, $language_manager, $entity_type_manager, $entity_helper, $entities_manager, $url_generator_manager, $memory_cache);

    $this->config = \Drupal::config('headless_domain.settings');
  }

  protected function replaceBaseUrlWithCustom(string $url): string {
    /** @var \Drupal\domain\DomainInterface $domain */
    $domain = \Drupal::service('domain.negotiator')->getActiveDomain();

    if ($domain) {
      $domain_id = $domain->id();
      $custom_base_url = $this->config->get($domain_id);

      if ($custom_base_url) {
        return str_replace($GLOBALS['base_url'], $custom_base_url, $url);
      }
    }

    return parent::replaceBaseUrlWithCustom($url);
  }

}
