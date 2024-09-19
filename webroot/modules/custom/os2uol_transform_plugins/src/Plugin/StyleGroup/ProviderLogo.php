<?php

namespace Drupal\os2uol_transform_plugins\Plugin\StyleGroup;

use Drupal\image_style_generator\StyleGroupBase;
use Drupal\image_style_generator\StyleViewModeInterface;

/**
 * @StyleGroup(
 *  id = "provider_logo",
 *  title = "Provider logo",
 *  view_modes = {
 *  }
 * )
 */
class ProviderLogo extends StyleGroupBase {

  public function getCrop(string $breakpoint, int $width, int $multiplier, ?StyleViewModeInterface $view_mode = NULL): array {
    $calculated_width = 188;

    if ($breakpoint === 'lighthouse') {
      $calculated_width = 180 * $multiplier;
    } else if ($breakpoint === 'xxs') {
      $calculated_width = 180 * $multiplier;
    } else if ($breakpoint === 'xs') {
      $calculated_width = 180 * $multiplier;
    } else if ($breakpoint === 'sm') {
      $calculated_width = 160;
    } else if ($breakpoint === 'md') {
      $calculated_width = 129;
    }

    return [
      'width' => $calculated_width,
    ];
  }

}
