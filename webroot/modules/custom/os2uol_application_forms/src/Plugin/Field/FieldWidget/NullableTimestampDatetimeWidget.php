<?php

namespace Drupal\os2uol_application_forms\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Plugin\Field\FieldWidget\TimestampDatetimeWidget;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'datetime timestamp' widget.
 */
#[FieldWidget(
  id: 'nullable_datetime_timestamp',
  label: new TranslatableMarkup('Nullable Datetime Timestamp'),
  field_types: [
    'timestamp',
    'created',
  ],
)]
class NullableTimestampDatetimeWidget extends TimestampDatetimeWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Code copied and adjusted from TimestampDatetimeWidget::formElement
    $default_value = isset($items[$delta]->value) ? DrupalDateTime::createFromTimestamp($items[$delta]->value) : '';
    $element['value'] = $element + [
        '#type' => 'datetime',
        '#default_value' => $default_value,
        '#date_year_range' => '1902:2037',
        '#description' => $element['#description'],
      ];

    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Code copied and adjusted from TimestampDatetimeWidget::massageFormValues
    foreach ($values as &$item) {
      // @todo The structure is different whether access is denied or not, to
      //   be fixed in https://www.drupal.org/node/2326533.
      if (isset($item['value']) && $item['value'] instanceof DrupalDateTime) {
        $date = $item['value'];
      }
      elseif (isset($item['value']['object']) && $item['value']['object'] instanceof DrupalDateTime) {
        $date = $item['value']['object'];
      }
      else {
        $date = NULL;
      }
      $item['value'] = $date ? $date->getTimestamp() : NULL;
    }
    return $values;
  }

}
