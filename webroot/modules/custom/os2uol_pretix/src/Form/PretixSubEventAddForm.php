<?php

namespace Drupal\os2uol_pretix\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Form\FormStateInterface;

class PretixSubEventAddForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return NULL;
  }

  public function form(array $form, FormStateInterface $form_state) {
    $form = [
      '#title' => $this->t('Add new date')
    ];
    $event = [];
    $form['quota'] = [
      '#type' => 'number',
      '#title' => $this->t('Quota'),
      '#required' => TRUE,
      '#default_value' => $event['quota'] ?? 0
    ];
    $form['date_from'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Event start time'),
      '#required' => TRUE,
      '#default_value' => $event['date_from'] ?? ''
    ];
    $form['date_to'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Event end time'),
      '#required' => FALSE,
      '#default_value' => $event['date_to'] ?? ''
    ];
    $form['presale_start'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Start of presale'),
      '#required' => FALSE,
      '#default_value' => $event['presale_start'] ?? ''
    ];
    $form['presale_end'] = [
      '#type' => 'datetime',
      '#title' => $this->t('End of presale'),
      '#required' => FALSE,
      '#default_value' => $event['presale_end'] ?? ''
    ];

    $price = 0;
    $ajax = [
      'callback' => '::priceCallback',
      'event' => 'change',
      'wrapper' => 'pretix_price_wrapper',
      'progress' => [
        'type' => 'throbber',
      ],
    ];
    $form['price'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'pretix_price_wrapper'
      ],
    ];
    $free = $form_state->getValue('free') ?? ($price == 0);
    $form['price']['free'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Free'),
      '#required' => FALSE,
      '#ajax' => $ajax,
      '#default_value' => $free
    ];
    if ($free) {
      $form['price']['price'] = [
        '#type' => 'hidden',
        '#required' => FALSE,
        '#default_value' => $form_state->getValue('price') ?? $price
      ];
    } else {
      $form['price']['price'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Price'),
        '#size' => 14,
        '#field_suffix' => 'kr.',
        '#required' => FALSE,
        '#default_value' => $form_state->getValue('price') ?? $price
      ];
    }

    return $form;
  }

  /**
   * AJAX callback for refreshing content.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function priceCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#pretix_price_wrapper', $form['price']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm'],
      '#button_type' => 'primary',
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): void {
    // Not saving.
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\os2uol_pretix\PretixEventManager $eventManager */
    $eventManager = \Drupal::service('os2uol_pretix.event_manager');
    /** @var EditorialContentEntityBase $entity */
    $entity = $this->getEntity();

    if ($entity->get('field_pretix_event_short_form')->isEmpty()) {
      return;
    }

    $subevent = [
      'slug' => $entity->id(),
      'is_public' => $entity->isPublished(),
      'date_from' => $eventManager->formatDateFormValue($form_state->getValue('date_from'))
    ];
    if (!empty($form_state->getValue('date_to'))) {
      $subevent['date_to'] = $eventManager->formatDateFormValue($form_state->getValue('date_to'));
    }
    if (!empty($form_state->getValue('presale_start'))) {
      $subevent['presale_start'] = $eventManager->formatDateFormValue($form_state->getValue('presale_start'));
    }
    if (!empty($form_state->getValue('presale_end'))) {
      $subevent['presale_end'] = $eventManager->formatDateFormValue($form_state->getValue('presale_end'));
    }
    if (empty($form_state->getValue('free'))) {
      $subevent['free'] = FALSE;
    } else {
      $subevent['free'] = TRUE;
    }
    if (!empty($form_state->getValue('price'))) {
      $subevent['price'] = $form_state->getValue('price');
    }
    if (!empty($form_state->getValue('quota'))) {
      $subevent['quota'] = $form_state->getValue('quota');
    }

    if (!is_null($eventManager->addSubEvent($entity, $subevent))) {
      $this->messenger()
        ->addStatus($this->t('Successfully created new event'));
    }
  }

}
