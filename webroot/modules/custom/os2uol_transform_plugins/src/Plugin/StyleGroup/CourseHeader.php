<?php

namespace Drupal\os2uol_transform_plugins\Plugin\StyleGroup;

use Drupal\image_style_generator\StyleGroupBase;
use Drupal\image_style_generator\StyleViewModeInterface;

/**
 * @StyleGroup(
 *  id = "course_header",
 *  title = "Course Header",
 *  view_modes = {
 *  }
 * )
 */
class CourseHeader extends StyleGroupBase {

  public function getCrop(string $breakpoint, int $width, int $multiplier, ?StyleViewModeInterface $view_mode = NULL): array {
    $calculated_width = 1584;
    $calculated_height = 700;

    if ($breakpoint === 'lighthouse') {
      $calculated_width = 388 * $multiplier;
      $calculated_height = 250 * $multiplier;
    } else if ($breakpoint === 'xxs') {
      $calculated_width = 456 * $multiplier;
      $calculated_height = 320 * $multiplier;
    } else if ($breakpoint === 'xs') {
      $calculated_width = 743 * $multiplier;
      $calculated_height = 500 * $multiplier;
    } else if ($breakpoint === 'sm') {
      $calculated_width = 967;
      $calculated_height = 500;
    } else if ($breakpoint === 'md') {
      $calculated_width = 1151;
      $calculated_height = 600;
    }

    return [
      'width' => $calculated_width,
      'height' => $calculated_height
    ];
  }

}
