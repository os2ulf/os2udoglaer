<?php

namespace Drupal\os2uol_transform_plugins\Plugin\StyleGroup;

use Drupal\image_style_generator\StyleGroupBase;
use Drupal\image_style_generator\StyleViewModeInterface;

/**
 * @StyleGroup(
 *  id = "page_header",
 *  title = "Page Header",
 *  view_modes = {
 *  }
 * )
 */
class PageHeader extends StyleGroupBase {

  public function getCrop(string $breakpoint, int $width, int $multiplier, ?StyleViewModeInterface $view_mode = NULL): array {
    $calculated_width = 2244;
    $calculated_height = 560;

    if ($breakpoint === 'lighthouse') {
      $calculated_width = 412 * $multiplier;
      $calculated_height = 250 * $multiplier;
    } else if ($breakpoint === 'xxs') {
      $calculated_width = 480 * $multiplier;
      $calculated_height = 250 * $multiplier;
    } else if ($breakpoint === 'xs') {
      $calculated_width = 767 * $multiplier;
      $calculated_height = 320 * $multiplier;
    } else if ($breakpoint === 'sm') {
      $calculated_width = 826;
      $calculated_height = 460;
    } else if ($breakpoint === 'md') {
      $calculated_width = 999;
    } else if ($breakpoint === 'lg') {
      $calculated_width = 1504;
    }

    return [
      'width' => $calculated_width,
      'height' => $calculated_height
    ];
  }

}
