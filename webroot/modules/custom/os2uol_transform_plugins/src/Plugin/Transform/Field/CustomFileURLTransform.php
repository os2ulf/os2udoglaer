<?php

namespace Drupal\os2uol_transform_plugins\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\transform_api\Plugin\Transform\Field\FileTransformBase;

/**
 * Transform field plugin for file field types as Urls.
 *
 * @FieldTransform(
 *  id = "custom_file_url",
 *  label = @Translation("Custom File URL"),
 *  field_types = {
 *    "file"
 *  }
 * )
 */
class CustomFileURLTransform extends FileTransformBase {

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode) {
    $values = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $item) {
      if (!empty($item->getValue()['target_id'])) {
        // File ID.
        $fid = $item->getValue()['target_id'];
        // Load file.
        $file = $this->loadFile($fid);

        $values[] = $file->createFileUrl(FALSE);
      }
      else {
        $values[] = NULL;
      }
    }
    return $values;
  }

}
