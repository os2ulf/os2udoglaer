<?php

namespace Drupal\os2uol_pretix\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\os2uol_pretix\PretixEventManager;
use Drupal\user\EntityOwnerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PretixSubEventAddForm extends ContentEntityForm {

  /**
   * The Pretix event manager.
   *
   * @var PretixEventManager
   */
  protected $eventManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, PretixEventManager $pretix_event_manager) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->eventManager = $pretix_event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('os2uol_pretix.event_manager')
    );
  }

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
    // Add #process callbacks.
    $form['#process'][] = '::processForm';

    /** @var \Drupal\Core\Entity\EditorialContentEntityBase $entity */
    $entity = $this->getEntity();
    /** @var EntityOwnerTrait $entityOwner */
    $entityOwner = $this->getEntity();
    /** @var \Drupal\user\UserInterface $user */
    $user = $entityOwner->getOwner();

    $subevents = $this->eventManager->getSubEvents($entity);
    if (!isset($subevents['results'])) {
      return $form;
    }
    $subevent = [];
    $price = 0;
    $quota = 0;
    foreach ($subevents['results'] as $key => $subevent) {
    }
    if (!empty($subevent)) {
      $quotas = $this->eventManager->getQuotas($entity, $subevent);
      $quota = $quotas['results'][0]['size'] ?? NULL;
      $price = $subevent['item_price_overrides'][0]['price'] ?? 0;
    }

    $form['quota'] = [
      '#type' => 'number',
      '#title' => $this->t('Quota'),
      '#required' => TRUE,
      '#default_value' => $quota
    ];
    $form['date_from'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Event start time'),
      '#required' => TRUE,
      '#default_value' => empty($subevent['date_from']) ? NULL : new DrupalDateTime($subevent['date_from'])
    ];
    $form['date_to'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Event end time'),
      '#required' => FALSE,
      '#default_value' => empty($subevent['date_to']) ? NULL : new DrupalDateTime($subevent['date_to'])
    ];
    $form['presale_start'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Start of presale'),
      '#required' => FALSE,
      '#default_value' => empty($subevent['presale_start']) ? NULL : new DrupalDateTime($subevent['presale_start'])
    ];
    $form['presale_end'] = [
      '#type' => 'datetime',
      '#title' => $this->t('End of presale'),
      '#required' => FALSE,
      '#default_value' => empty($subevent['presale_end']) ? NULL : new DrupalDateTime($subevent['presale_end'])
    ];

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
      'date_from' => $eventManager->formatDateFormValue($form_state->getValue('date_from')),
      'quota' => $form_state->getValue('quota')
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
    $subevent['free'] = !empty($form_state->getValue('free'));

    if (!empty($form_state->getValue('price'))) {
      $subevent['price'] = $form_state->getValue('price');
    }

    if (!is_null($eventManager->addSubEvent($entity, $subevent))) {
      $this->messenger()->addStatus($this->t('Successfully added new date.'));

      // Redirect to the Pretix page after form submission.
      $form_state->setRedirect('entity.node.pretix', ['node' => $entity->id()]);
    }
  }

  /**
   * {@inheritdoc}
   *
   * Button-level validation handlers are highly discouraged for entity forms,
   * as they will prevent entity validation from running. If the entity is going
   * to be saved during the form submission, this method should be manually
   * invoked from the button-level validation handler, otherwise an exception
   * will be thrown.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->buildEntity($form, $form_state);
    return $entity;
  }
}
