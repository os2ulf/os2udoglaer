<?php

namespace Drupal\os2uol_domain;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Language\LanguageInterface;
use Drupal\domain\DomainInterface;

class DomainConfigHelper {

  public function __construct(
    protected ConfigFactoryInterface $configFactory
  ) {}

  /**
   * Get domain config.
   *
   * @param string $name
   *   The name of the config.
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain.
   * @param \Drupal\Core\Language\LanguageInterface|null $language
   *   The language.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The config.
   */
  public function getDomainConfig(string $name, DomainInterface $domain, LanguageInterface $language = NULL): ImmutableConfig {
    if ($language != NULL) {
      $config = $this->configFactory->get('domain.config.' . $domain->id() . '.' . $language->getId() . '.' . $name);

      // Only return the config if it is not new.
      if (!$config->isNew()) {
        return $config;
      }
    }

    $config = $this->configFactory->get('domain.config.' . $domain->id() . '.' . $name);

    // Only return the config if it is not new.
    if (!$config->isNew()) {
      return $config;
    }

    // Return the default config.
    return $this->configFactory->get($name);
  }

}
