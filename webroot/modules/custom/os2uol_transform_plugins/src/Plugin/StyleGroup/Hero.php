<?php

namespace Drupal\os2uol_transform_plugins\Plugin\StyleGroup;

use Drupal\image_style_generator\StyleGroupBase;
use Drupal\image_style_generator\StyleViewModeInterface;

/**
 * @StyleGroup(
 *  id = "hero",
 *  title = "Hero",
 *  view_modes = {
 *    "full_width",
 *    "width_100",
 *    "width_66",
 *    "width_50",
 *    "width_33"
 *  }
 * )
 */
class Hero extends StyleGroupBase {

  public function getCrop(string $breakpoint, int $width, int $multiplier, ?StyleViewModeInterface $view_mode = NULL): array {
    $calculated_width = $width * $multiplier;

    return [
      'width' => $calculated_width,
      'height' => $calculated_width / 16 * 9
    ];
  }

}
