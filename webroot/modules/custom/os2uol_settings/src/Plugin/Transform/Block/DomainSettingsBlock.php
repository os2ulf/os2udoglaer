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
      $logo_url = $file->createFileUrl(FALSE);
    }

    return [
      'logo' => $logo_url,
      'font' => $config->get('font'),
      'primary_background_color' => $config->get('primary_background_color'),
      'primary_background_text_color' => $config->get('primary_background_text_color'),
      'secondary_background_color' => $config->get('secondary_background_color'),
      'secondary_background_text_color' => $config->get('secondary_background_text_color'),
      'text_positive_color' => $config->get('text_positive_color'),
      'text_negative_color' => $config->get('text_negative_color'),
      'site_tracking_script' => $config->get('site_tracking_script'),
      'email_signature' => $config->get('email_signature'),
      'free_course_application_reference' => $config->get('free_course_application_reference') ? \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $config->get('free_course_application_reference')) : '',
      'ufcr_receipt' => $config->get('ufcr_receipt'),
      'transport_pool_application_reference' => $config->get('transport_pool_application_reference') ? \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $config->get('transport_pool_application_reference')) : '',
      'free_choice' => $config->get('free_choice'),
      'course_not_found' => $config->get('course_not_found'),
      'district_1' => $config->get('district_1'),
      'district_2' => $config->get('district_2'),
      'district_3' => $config->get('district_3'),
      'district_4' => $config->get('district_4'),
      'district_5' => $config->get('district_5'),
      'no__district' => $config->get('no__district'),
      'denied_distance' => $config->get('denied_distance'),
      'denied_private' => $config->get('denied_private'),
      'confirmation' => $config->get('confirmation'),
    ];
  }

  public function getCacheTags() {
    return $this->configFactory->get('os2uol_settings.settings')->getCacheTags();
  }

}
