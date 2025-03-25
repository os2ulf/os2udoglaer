<?php

namespace Drupal\os2uol_application_forms\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;


/**
 * Handles matching of current domain.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("os2uol_application_forms_year_filter")
 */
class YearFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    parent::buildExposedForm($form, $form_state);

    $current_year = (int) date('Y');

    // Array of year +-5 years from current year
    $years = range($current_year - 5, $current_year + 5);

    $year_options = array_combine($years, $years);

    $values = ['' => $this->t('- Any -')] + $year_options;

    $form[$this->options['expose']['identifier']] = [
      '#type' => 'select',
      '#title' => $this->t('Budget Year'),
      '#options' => $values,
      '#default_value' => $this->value ?? '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (is_array($this->value)) {
      $this->value = reset($this->value);
    }

    if (empty($this->value)) {
      return;
    }

    $this->value = "{$this->value}%";
    $this->operator = 'like';
    parent::query();
  }

}
