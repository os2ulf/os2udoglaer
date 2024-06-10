<?php

namespace Drupal\os2uol_transform_plugins\Plugin\StyleViewMode;

use Drupal\image_style_generator\Annotation\StyleViewMode;
use Drupal\image_style_generator\StyleViewModeBase;

/**
 * @StyleViewMode(
 *  id = "full_width",
 *  title = "Full width"
 * )
 */
class FullWidth extends StyleViewModeBase {

  public function calcWidth($breakpoint, $width): int {
    $width = min(1920, $width);

    return $this->widths[$breakpoint] = $width;
  }
}
