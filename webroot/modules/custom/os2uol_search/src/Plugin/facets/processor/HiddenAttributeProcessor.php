<?php

namespace Drupal\os2uol_search\Plugin\facets\processor;

use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides a processor for adding hidden attribute to the facet.
 *
 * This processor does not have any configuration options.
 * Hidden attribute is added to the facet in the transform alter hook.
 *
 * @see os2uol_search_facet_transform_alter()
 * @todo: Update transform_api_facets module to support transform stage for
 *   facet processors.
 *
 * @FacetsProcessor(
 *   id = "hidden_attribute_processor",
 *   label = @Translation("Hidden attribute"),
 *   description = @Translation("Add hidden attribute to the facet."),
 * )
 */
class HiddenAttributeProcessor extends ProcessorPluginBase {

}
