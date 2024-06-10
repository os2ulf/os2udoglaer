<?php

namespace Drupal\os2uol_transform_plugins\Plugin\StyleViewMode;

use Drupal\image_style_generator\StyleViewModeBase;

class ContentRegionBase extends StyleViewModeBase {

  protected int $maxContainerSize = 1632;
  protected int $desktopGutter = 48;
  protected int $mobileGutter = 24;

  public function calcWidth($breakpoint, $width): int {
    $width = min($this->maxContainerSize, $width);
    return $this->widths[$breakpoint] = $width;
  }

  protected function getGutterWidth($breakpoint) {
    switch ($breakpoint) {
      case 'sm':
      case 'xs':
      case 'xxs':
      case 'lighthouse':
        return $this->mobileGutter;
      default:
        return $this->desktopGutter;
    }
  }

  protected function isMobile($breakpoint) {
    return in_array($breakpoint, ['xs', 'xxs', 'lighthouse']);
  }

  protected function isTablet($breakpoint) {
    return in_array($breakpoint, ['md', 'sm', 'xs', 'xxs', 'lighthouse']);
  }
}
