<?php

namespace Drupal\os2uol_transform_plugins\Plugin\StyleGroup;

use Drupal\image_style_generator\StyleGroupBase;
use Drupal\image_style_generator\StyleViewModeInterface;

/**
 * @StyleGroup(
 *  id = "related_courses",
 *  title = "Related Courses",
 *  view_modes = {
 *  }
 * )
 */
class RelatedCourses extends StyleGroupBase {

  public function getCrop(string $breakpoint, int $width, int $multiplier, ?StyleViewModeInterface $view_mode = NULL): array {
    $calculated_width = 388;
    $calculated_height = 500;

    if ($breakpoint === 'lighthouse') {
      $calculated_width = 384 * $multiplier;
      $calculated_height = 250 * $multiplier;
    } else if ($breakpoint === 'xxs') {
      $calculated_width = 452 * $multiplier;
      $calculated_height = 250 * $multiplier;
    } else if ($breakpoint === 'xs') {
      $calculated_width = 739 * $multiplier;
      $calculated_height = 350 * $multiplier;
    } else if ($breakpoint === 'sm') {
      $calculated_width = 482;
      $calculated_height = 400;
    } else if ($breakpoint === 'md') {
      $calculated_width = 574;
    }

    return [
      'width' => $calculated_width,
      'height' => $calculated_height
    ];
  }

}
