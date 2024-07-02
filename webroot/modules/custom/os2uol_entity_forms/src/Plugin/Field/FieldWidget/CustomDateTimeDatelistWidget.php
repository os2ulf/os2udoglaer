<?php

namespace Drupal\os2uol_entity_forms\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;

/**
 * Plugin implementation of the 'datetime_datelist' widget.
 *
 * @FieldWidget(
 *   id = "custom_datetime_datelist",
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

    $start_year = date('Y');
    $end_year = date('Y') + 10;


    $element['value'] = [
      '#type' => 'datelist',
      '#date_part_order' => ['year'],
      '#date_year_range' => $start_year . ':' . $end_year,

    ] + $element['value'];

    return $element;
  }

}
