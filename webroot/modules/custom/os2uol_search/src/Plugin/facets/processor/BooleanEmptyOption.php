<?php

namespace Drupal\os2uol_search\Plugin\facets\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\processor\BooleanItemProcessor;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Result\Result;

/**
 * Provides a processor for boolean labels.
 *
 * @FacetsProcessor(
 *   id = "boolean_empty_option",
 *   label = @Translation("Boolean empty option"),
 *   description = @Translation("Display configurable On/Off labels and empty option instead 1/0 values for boolean fields."),
 *   stages = {
 *     "pre_query" = 70,
 *     "build" = 35
 *   }
 * )
 */
class BooleanEmptyOption extends BooleanItemProcessor implements PreQueryProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $config = $this->getConfiguration();

    // Get total count from both results.
    $total_count = $results[0]->getCount() + $results[1]->getCount();

    $results = parent::build($facet, $results);

    if (!empty($config['all_value'])) {
      $result = new Result($facet, 'all', $config['all_value'], $total_count);

      // Clone the first result to get the URL.
      $url = reset($results)->getUrl();

      // Set the URL to contain only the "all" option.
      $url->setOption('query', ['f' => [$facet->getUrlAlias() . ':all']]);

      $result->setUrl($url);

      $results = array_merge([$result], $results);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $build = parent::buildConfigurationForm($form, $form_state, $facet);

    $config = $this->getConfiguration();

    $build['all_value'] = [
      '#title' => $this->t('All value'),
      '#type' => 'textfield',
      '#default_value' => $config['all_value'],
      '#description' => $this->t('Use this label to set label for "All" item options. Leave empty to hide this item.'),
    ];

    // Warning message.
    $build['warning'] = [
      '#markup' => '<div class="messages messages--warning">' . $this->t('This processor requires "Minimum count" setting to be set to 0') . '</div>',
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();

    $configuration['all_value'] = 'All';

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function preQuery(FacetInterface $facet): void {
    // If "All" option is selected, behave as if no options are selected.
    if ($facet->isActiveValue('all')) {
      $facet->setActiveItems([]);
    }
  }

}
