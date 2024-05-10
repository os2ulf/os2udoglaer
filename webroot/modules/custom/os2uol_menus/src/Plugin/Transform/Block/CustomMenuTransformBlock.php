<?php

namespace Drupal\os2uol_menus\Plugin\Transform\Block;

use Drupal\transform_api\Plugin\Transform\Block\SystemMenuTransformBlock;
use Drupal\transform_api\Transform\EntityTransform;

/**
 * Provides a generic Menu block.
 *
 * @TransformBlock(
 *   id = "custom_menu_block",
 *   admin_label = @Translation("Menu (with custom fields)"),
 *   category = @Translation("Menus (with custom fields)"),
 *   deriver = "Drupal\transform_api\Plugin\Derivative\SystemMenuTransformBlock",
 * )
 */
class CustomMenuTransformBlock extends SystemMenuTransformBlock {

  /**
   * Take an array of menu items and transform them.
   *
   * @param array $items
   *   Array of menu items.
   *
   * @return array
   *   The JSON array.
   */
  protected function transformMenuItems(array $items) {
    $result = [];

    foreach ($items as $array) {
      $entity = $array['original_link']->getEntity();

      $item = EntityTransform::createFromEntity($entity)->transform();

      $item['below'] = $this->transformMenuItems($array['below']);

      $result[] = $item;
    }

    return $result;
  }

}
