<?php

namespace Drupal\os2uol_settings\Plugin\Transform\Block;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\file\Entity\File;
use Drupal\transform_api\TransformBlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @TransformBlock(
 *   id = "domain_settings",
 *   admin_label = "Domain Settings",
 *   category = @Translation("OS2OUL"),
 * )
 */
class DomainSettingsBlock extends TransformBlockBase {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  public function transform() {
    $config = $this->configFactory->get('os2uol_settings.settings');

    $logo = $config->get('logo');

    // Get logo URL.
    $logo_url = '';

    if ($logo) {
      $file = File::load($logo);
      $logo_url = $file->createFileUrl();
    }

    return [
      'logo' => $logo_url,
      'primary_background_color' => $config->get('primary_background_color'),
      'primary_background_text_color' => $config->get('primary_background_text_color'),
      'secondary_background_color' => $config->get('secondary_background_color'),
      'secondary_background_text_color' => $config->get('secondary_background_text_color'),
    ];
  }

  public function getCacheTags() {
    return ['config:os2uol_settings.settings'];
  }

}
