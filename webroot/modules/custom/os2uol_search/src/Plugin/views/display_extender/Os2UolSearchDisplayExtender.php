<?php

namespace Drupal\os2uol_search\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Display extender plugin to control OS2UOL search settings.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "os2uol_search",
 *   title = @Translation("OS2UOL Search"),
 *   help = @Translation("Manage OS2UOL Search settings."),
 *   no_ui = FALSE,
 * )
 */
class Os2UolSearchDisplayExtender extends DisplayExtenderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    return [
      'os2uol_search_result_string' => [
        'default' => '',
      ],
    ] + parent::defineOptions();
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'os2uol_search_result_string') {
      $form['#title'] = $this->t('Add result string');

      $form['os2uol_search_result_string'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Result string'),
        '#default_value' => $this->options['os2uol_search_result_string'],
        '#description' => $this->t('The result string. Use @count to display the number of results.'),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'os2uol_search_result_string') {
      $this->options['os2uol_search_result_string'] = $form_state->getValue('os2uol_search_result_string');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    $categories['os2uol_search'] = [
      'title' => $this->t('OS2UOL Search'),
      'column' => 'second',
    ];

    $result_string_value = $this->options['os2uol_search_result_string'];

    if (empty($result_string_value)) {
      $result_string_value = $this->t('Not set', [], ['context' => 'os2uol_search']);
    }

    $options['os2uol_search_result_string'] = [
      'category' => 'os2uol_search',
      'title' => $this->t('Result string'),
      'value' => $result_string_value,
    ];
  }

}
