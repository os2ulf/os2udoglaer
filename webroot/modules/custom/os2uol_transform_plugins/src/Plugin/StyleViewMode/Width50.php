<?php

namespace Drupal\os2uol_transform_plugins\Plugin\StyleViewMode;

use Drupal\image_style_generator\Annotation\StyleViewMode;
use Drupal\image_style_generator\StyleViewModeBase;

/**
 * @StyleViewMode(
 *  id = "width_50",
 *  title = "Width 50%"
 * )
 */
class Width50 extends ContentRegionBase {

  public function calcWidth($breakpoint, $width): int {
    if (in_array($breakpoint, array('lighthouse', 'xxs', 'xs', 'sm'))) {
      return $this->widths[$breakpoint] = round((parent::calcWidth($breakpoint, $width)) - $this->getGutterWidth($breakpoint));
    } else {
      return $this->widths[$breakpoint] = round((parent::calcWidth($breakpoint, $width) / 2) - $this->getGutterWidth($breakpoint));
    }
  }

}
