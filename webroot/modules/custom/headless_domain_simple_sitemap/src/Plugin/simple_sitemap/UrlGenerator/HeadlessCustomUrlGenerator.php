<?php

namespace Drupal\headless_domain_simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\simple_sitemap\Entity\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Manager\CustomLinkManager;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\CustomUrlGenerator;
use Drupal\simple_sitemap\Settings;

/**
 * Provides the custom URL generator.
 *
 * @UrlGenerator(
 *   id = "headless_custom",
 *   label = @Translation("Headless Custom URL generator"),
 *   description = @Translation("Generates URLs set in admin/config/search/simplesitemap/custom."),
 * )
 */
class HeadlessCustomUrlGenerator extends CustomUrlGenerator {

  protected ImmutableConfig $config;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, Logger $logger, Settings $settings, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, EntityHelper $entity_helper, CustomLinkManager $custom_links, PathValidatorInterface $path_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $settings, $language_manager, $entity_type_manager, $entity_helper, $custom_links, $path_validator);

    $this->config = \Drupal::config('headless_domain_simple_sitemap.settings');
  }

  protected function processDataSet($data_set): array {
    $results = parent::processDataSet($data_set);

    // Get the selected domain for the sitemap type.
    $sitemap_domain_id = $this->sitemap->getType()->getThirdPartySetting('domain_simple_sitemap', 'sitemap_domain');
    if (!$sitemap_domain_id) {
      return $results;
    }

    // Set the base URL to the headless domain.
    $domain_base_url = $this->config->get($sitemap_domain_id);

    if (empty($domain_base_url)) {
      return $results;
    }

    $results['url']->setOption('base_url', $domain_base_url);

    return $results;
  }

}
