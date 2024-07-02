<?php

namespace Drupal\os2uol_entity_forms\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;

/**
 * Plugin implementation of the 'datetime_year_list' widget.
 *
 * @FieldWidget(
 *   id = "custom_datetime_year_list",
 *   label = @Translation("OS2UOL year list"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class CustomDateTimeDatelistWidget extends DateTimeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'date_order' => 'Y',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Wrap all of the select elements with a fieldset.
    $element['#theme_wrappers'][] = 'fieldset';

    $increment = '';
    $date_part_order = ['year'];

    $start_year = date('Y');
    $end_year = date('Y', strtotime('+10 years'));


    $element['value'] = [
      '#type' => 'datelist',
      '#date_increment' => $increment,
      '#date_part_order' => $date_part_order,
      '#date_year_range' => $start_year . ':' . $end_year,

    ] + $element['value'];

    return $element;
  }

    /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['date_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Date part order'),
      '#default_value' => $this->getSetting('date_order'),
      '#options' => [
        'Y' => $this->t('Year'),
      ],
    ];

    $element['time_type'] = [
      '#type' => 'hidden',
      '#value' => 'none',
    ];

    $element['increment'] = [
      '#type' => 'hidden',
      '#value' => $this->getSetting('increment'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Date part order: @order', ['@order' => $this->getSetting('date_order')]);

    return $summary;
  }
}
