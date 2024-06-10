<?php

namespace Drupal\os2uol_transform_plugins\Plugin\StyleViewMode;

use Drupal\image_style_generator\Annotation\StyleViewMode;
use Drupal\image_style_generator\StyleViewModeBase;

/**
 * @StyleViewMode(
 *  id = "width_100",
 *  title = "Width 100%"
 * )
 */
class Width100 extends ContentRegionBase {

  public function calcWidth($breakpoint, $width): int {
    return $this->widths[$breakpoint] = round(parent::calcWidth($breakpoint, $width) - $this->getGutterWidth($breakpoint));
  }
}
