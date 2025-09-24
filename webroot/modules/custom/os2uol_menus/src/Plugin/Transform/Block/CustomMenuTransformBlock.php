<?php

namespace Drupal\os2uol_menus\Plugin\Transform\Block;

use Drupal\menu_link_content\Entity\MenuLinkContent;
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
  protected function transformMenuItems(array $items): array {
    $result = [];

    foreach ($items as $array) {
      /** @var MenuLinkContent $entity */
      $entity = $array['original_link']->getEntity();

      $item = (new EntityTransform($entity))->transform();

      $item['label'] = $array['title'];
      $item['title'] = $array['title'];
      $item['enabled'] = $entity->isEnabled();
      $item['expanded'] = $entity->isExpanded();
      $item['link']['url'] = $array['url']->toString();
      $item['field_icon'] = $this->getIconUrl($entity);
      $item['field_description'] = $entity->get('field_description')->value;

      $item['below'] = $this->transformMenuItems($array['below']);

      $result[] = $item;
    }

    return $result;
  }

  protected function getIconUrl(MenuLinkContent $item): ?string {
    if (!$item->hasField('field_icon') || $item->get('field_icon')->isEmpty()) {
      return NULL;
    }

    /** @var \Drupal\file\Entity\File $file */
    $file = $item->get('field_icon')->entity;

    return $file?->createFileUrl(FALSE);
  }

}
