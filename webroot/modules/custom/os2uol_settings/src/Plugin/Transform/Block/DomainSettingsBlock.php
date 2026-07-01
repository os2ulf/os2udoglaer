<?php

namespace Drupal\os2uol_settings\Plugin\Transform\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
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
    protected FileSystemInterface $fileSystem,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('file_system')
    );
  }

  public function transform() {
    $config = $this->configFactory->get('os2uol_settings.settings');
    $site_config = $this->configFactory->get('system.site');

    $logo = [
      'src' => '',
      'alt' => $site_config->get('name') ?? '',
      'width' => NULL,
      'height' => NULL,
    ];
    if ($logo_fid = $config->get('logo')) {
      $file = File::load($logo_fid);
      if ($file) {
        $logo['src'] = $file->createFileUrl(FALSE);
        $logo = array_replace($logo, $this->getImageDimensions($file));
      }
    }

    // Get favicon URL.
    $favicon_url = '';
    if ($favicon_fid = $config->get('favicon')) {
      $file = File::load($favicon_fid);
      if ($file) {
        $favicon_url = $file->createFileUrl(FALSE);
      }
    }

    return [
      'logo' => $logo,
      'favicon' => $favicon_url,
      'font' => $config->get('font'),
      'primary_background_color' => $config->get('primary_background_color'),
      'primary_background_text_color' => $config->get('primary_background_text_color'),
      'secondary_background_color' => $config->get('secondary_background_color'),
      'secondary_background_text_color' => $config->get('secondary_background_text_color'),
      'tertiary_background_color' => $config->get('tertiary_background_color'),
      'tertiary_background_text_color' => $config->get('tertiary_background_text_color'),
      'primary_button_color' => $config->get('primary_button_color'),
      'primary_button_text_color' => $config->get('primary_button_text_color'),
      'primary_button_hover_color' => $config->get('primary_button_hover_color'),
      'primary_button_hover_text_color' => $config->get('primary_button_hover_text_color'),
      'secondary_button_color' => $config->get('secondary_button_color'),
      'secondary_button_text_color' => $config->get('secondary_button_text_color'),
      'secondary_button_hover_color' => $config->get('secondary_button_hover_color'),
      'secondary_button_hover_text_color' => $config->get('secondary_button_hover_text_color'),
      'text_positive_color' => $config->get('text_positive_color'),
      'text_negative_color' => $config->get('text_negative_color'),
      'site_tracking_script' => $config->get('site_tracking_script'),
      'site_cookie_script' => $config->get('site_cookie_script'),
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
      'no_district' => $config->get('no_district'),
      'denied_distance' => $config->get('denied_distance'),
      'denied_private' => $config->get('denied_private'),
      'confirmation' => $config->get('confirmation'),
      'tr_receipt' => $config->get('tr_receipt'),
    ];
  }

  public function getCacheTags() {
    return Cache::mergeTags(
      $this->configFactory->get('os2uol_settings.settings')->getCacheTags(),
      $this->configFactory->get('system.site')->getCacheTags(),
    );
  }

  private function getImageDimensions(FileInterface $file): array {
    $path = $this->fileSystem->realpath($file->getFileUri());
    if (!$path || !is_file($path)) {
      return [];
    }

    $size = @getimagesize($path);
    if (is_array($size) && !empty($size[0]) && !empty($size[1])) {
      return [
        'width' => (int) $size[0],
        'height' => (int) $size[1],
      ];
    }

    if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'svg') {
      return $this->getSvgDimensions($path);
    }

    return [];
  }

  private function getSvgDimensions(string $path): array {
    $previous = libxml_use_internal_errors(TRUE);
    $svg = simplexml_load_file($path, 'SimpleXMLElement', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (!$svg) {
      return [];
    }

    $width = $this->parseSvgLength((string) $svg['width']);
    $height = $this->parseSvgLength((string) $svg['height']);

    if ($width && $height) {
      return [
        'width' => $width,
        'height' => $height,
      ];
    }

    $view_box = preg_split('/[\s,]+/', trim((string) $svg['viewBox']));
    if (is_array($view_box) && count($view_box) === 4) {
      return [
        'width' => (int) round((float) $view_box[2]),
        'height' => (int) round((float) $view_box[3]),
      ];
    }

    return [];
  }

  private function parseSvgLength(string $length): ?int {
    if (preg_match('/^-?\d*\.?\d+/', $length, $matches)) {
      return (int) round((float) $matches[0]);
    }

    return NULL;
  }

}
