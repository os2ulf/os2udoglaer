<?php

namespace Drupal\os2uol_transform_plugins\Plugin\StyleGroup;

use Drupal\image_style_generator\StyleGroupBase;
use Drupal\image_style_generator\StyleViewModeInterface;

/**
 * @StyleGroup(
 *  id = "appetizer",
 *  title = "Appetizer",
 *  view_modes = {
 *  }
 * )
 */
class Appetizer extends StyleGroupBase {

  public function getCrop(string $breakpoint, int $width, int $multiplier, ?StyleViewModeInterface $view_mode = NULL): array {
    $calculated_width = 1700;
    $calculated_height = 650;

    if ($breakpoint === 'lighthouse') {
      $calculated_width = 388 * $multiplier;
      $calculated_height = 300 * $multiplier;
    } else if ($breakpoint === 'xxs') {
      $calculated_width = 480 * $multiplier;
      $calculated_height = 350 * $multiplier;
    } else if ($breakpoint === 'xs') {
      $calculated_width = 767 * $multiplier;
      $calculated_height = 400 * $multiplier;
    } else if ($breakpoint === 'sm') {
      $calculated_width = 991;
      $calculated_height = 500;
    } else if ($breakpoint === 'md') {
      $calculated_width = 600;
    } else if ($breakpoint === 'lg') {
      $calculated_width = 960;
    }

    return [
      'width' => $calculated_width,
      'height' => $calculated_height
    ];
  }

}
