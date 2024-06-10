<?php

namespace Drupal\os2uol_transform_plugins\Plugin\StyleViewMode;

use Drupal\image_style_generator\Annotation\StyleViewMode;
use Drupal\image_style_generator\StyleViewModeBase;

/**
 * @StyleViewMode(
 *  id = "width_33",
 *  title = "Width 33%"
 * )
 */
class Width33 extends ContentRegionBase {

  public function calcWidth($breakpoint, $width): int {
    if (in_array($breakpoint, array('lighthouse', 'xxs', 'xs', 'sm'))) {
      return $this->widths[$breakpoint] = round((parent::calcWidth($breakpoint, $width)) - $this->getGutterWidth($breakpoint));
    } else {
      return $this->widths[$breakpoint] = round((parent::calcWidth($breakpoint, $width) / 3) - $this->getGutterWidth($breakpoint));
    }
  }
}
