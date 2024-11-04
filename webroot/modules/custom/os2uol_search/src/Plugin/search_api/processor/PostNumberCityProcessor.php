<?php

namespace Drupal\os2uol_search\Plugin\search_api\processor;

use Drupal\node\NodeInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the item's URL to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "post_number_city",
 *   label = @Translation("Post number and city"),
 *   description = @Translation("Adds the item's post number and city to the
 *   indexed data."), stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class PostNumberCityProcessor extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Post number and city'),
        'description' => $this->t('The post number and city of the internship or company.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['post_number_city'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $original_object = $item->getOriginalObject();

    if ($original_object == NULL) {
      return;
    }

    $entity = $original_object->getEntity();

    if (!$entity instanceof NodeInterface) {
      return;
    }

    $value = $this->getPostNumberCity($entity);

    if ($value) {
      $fields = $item->getFields(FALSE);
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($fields, NULL, 'post_number_city');
      foreach ($fields as $field) {
        $field->addValue($value);
      }
    }
  }

  /**
   * Get post number and city from entity.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return string
   *   The post number and city.
   */
  protected function getPostNumberCity(NodeInterface $node): string {
    // Check if the node has the required fields.
    if (!$node->hasField('field_view_on_map')) {
      return '';
    }

    $visibility = $node->get('field_view_on_map')->getString();

    return match ($visibility) {
      'show_vendor_address' => $this->getAuthorPostNumberCity($node),
      'show_alternative_address' => $this->getNodePostNumberCity($node),
      default => '',
    };
  }

  /**
   * Get post number and city from node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return string
   *   The post number and city.
   */
  protected function getNodePostNumberCity(NodeInterface $node) {
    // Check if the node has the required fields.
    if (!$node->hasField('field_location_zipcode') || !$node->hasField('field_location_city')) {
      return '';
    }

    $post_number = $node->get('field_location_zipcode')->getString();
    $address = $node->get('field_location_city')->getString();

    return $post_number . ' ' . $address;
  }

  /**
   * Get post number and city from company.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return string
   *   The post number and city.
   */
  protected function getAuthorPostNumberCity(NodeInterface $node) {
    $user = $node->getOwner();

    // Check if the required fields are not empty.
    if ($user->get('field_location_zipcode')->isEmpty() || $user->get('field_location_city')->isEmpty()) {
      return '';
    }

    $post_number = $user->get('field_location_zipcode')->getString();
    $address = $user->get('field_location_city')->getString();

    return $post_number . ' ' . $address;
  }

}
