<?php

namespace Drupal\os2uol_transform_plugins\Plugin\StyleGroup;

use Drupal\image_style_generator\StyleGroupBase;
use Drupal\image_style_generator\StyleViewModeInterface;

/**
 * @StyleGroup(
 *  id = "video",
 *  title = "Video",
 *  view_modes = {
 *  }
 * )
 */
class Video extends StyleGroupBase {

  public function getCrop(string $breakpoint, int $width, int $multiplier, ?StyleViewModeInterface $view_mode = NULL): array {
    $calculated_width = 768;

    if ($breakpoint === 'lighthouse') {
      $calculated_width = 388 * $multiplier;
    } else if ($breakpoint === 'xxs') {
      $calculated_width = 456 * $multiplier;
    } else if ($breakpoint === 'xs') {
      $calculated_width = 743 * $multiplier;
    } else if ($breakpoint === 'sm') {
      $calculated_width = 472;
    } else if ($breakpoint === 'md') {
      $calculated_width = 562;
    }

    return [
      'width' => $calculated_width,
      'height' => $calculated_width / 16 * 9
    ];
  }

}
