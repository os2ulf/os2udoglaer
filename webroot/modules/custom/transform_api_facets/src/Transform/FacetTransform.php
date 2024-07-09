<?php

namespace Drupal\transform_api_facets\Transform;

use Drupal\transform_api\Transform\PluginTransformBase;

/**
 * A transform of a facet.
 */
class FacetTransform extends PluginTransformBase {

  public function __construct($facet_id) {
    $this->values = [
      'facet_id' => $facet_id,
    ];
  }

  /**
   * Returns the facet id.
   *
   * @return string
   *   The facet id.
   */
  public function getFacetId(): string {
    return $this->getValue('facet_id');
  }

  /**
   * {@inheritdoc}
   */
  public function getTransformType() {
    return 'facet';
  }

  /**
   * {@inheritdoc}
   */
  public function getAlterIdentifiers() {
    return [
      $this->getTransformType(),
      $this->getTransformType() . '_' . $this->values['facet_id'],
    ];
  }

}
