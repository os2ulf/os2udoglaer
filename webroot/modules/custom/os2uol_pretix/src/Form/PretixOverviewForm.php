<?php

namespace Drupal\os2uol_pretix\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\os2uol_pretix\PretixEventManager;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PretixOverviewForm extends ContentEntityForm {

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

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = clone $this->entity;

    // Invoke all specified builders for copying form values to entity
    // properties.
    if (isset($form['#entity_builders'])) {
      foreach ($form['#entity_builders'] as $function) {
        call_user_func_array($form_state->prepareCallback($function), [$entity->getEntityTypeId(), $entity, &$form, &$form_state]);
      }
    }

    // Mark the entity as requiring validation.
    $entity->setValidationRequired(FALSE);

    // Save as a new revision if requested to do so.
    if ($this->showRevisionUi()) {
      $entity->setNewRevision();
      if ($entity instanceof RevisionLogInterface) {
        // If a new revision is created, save the current user as
        // revision author.
        $entity->setRevisionUserId($this->currentUser()->id());
        $entity->setRevisionCreationTime($this->time->getRequestTime());
      }
    }

    return $entity;
  }

  public function form(array $form, FormStateInterface $form_state) {
    $form = [];
    // Add #process callbacks.
    $form['#process'][] = '::processForm';

    /** @var \Drupal\Core\Entity\EditorialContentEntityBase $entity */
    $entity = $this->getEntity();
    /** @var EntityOwnerTrait $entityOwner */
    $entityOwner = $this->getEntity();
    /** @var \Drupal\user\UserInterface $user */
    $user = $entityOwner->getOwner();

    if (!$this->eventManager->isPretixEnabledUser($user)) {
      $form['message'] = [
        '#type' => 'item',
        '#title' => $this->t("User don't support Pretix"),
        '#description' => $this->t("The user does not have all the information necessary for Pretix integration.")
      ];
    } elseif ($this->eventManager->isPretixEventEntity($entity)) {
      $form = $this->formOverview($form, $form_state, $entity, $user);
    } elseif ($this->eventManager->hasPretixShopURL($entity)) {
      $form['slug'] = [
        '#type' => 'item',
        '#title' => $this->t('Event shop URL'),
        '#description' => $this->eventManager->getEventShopUrl($entity)
      ];
    } else {
      $form = $this->formInitialize($form, $form_state, $entity, $user);
    }

    return $form;
  }

  public function formInitialize(array $form, FormStateInterface $form_state, EditorialContentEntityBase $entity, UserInterface $user): array {
    $ajax = [
      'callback' => '::actionCallback',
      'event' => 'change',
      'wrapper' => 'pretix_action_wrapper',
      'progress' => [
        'type' => 'throbber',
      ],
    ];
    $form['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#ajax' => $ajax,
      '#options' => [
        '' => ' - ' . $this->t('Choose an action') . ' - ',
        'create' => $this->t('Create new event'),
        'choose' => $this->t('Choose existing event'),
        'url' => $this->t('Enter shop URL')
      ]
    ];

    $form['values'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'pretix_action_wrapper'
      ],
    ];

    if ($form_state->getValue('action') == 'choose') {
      $events = [];
      $result = $this->eventManager->getEvents($entity);
      if ($result['count'] > 0) {
        foreach ($result['results'] as $event) {
          $name = 'N/A';
          foreach ($event['name'] as $language => $value) {
            $name = $value;
          }
          $events[$event['slug']] = $name;
        }
      }

      $form['values']['event'] = [
        '#type' => 'select',
        '#title' => $this->t('Event'),
        '#options' => $events
      ];

      $form['values']['template'] = $this->templateFormElement($user);
    } elseif ($form_state->getValue('action') == 'create') {
      $form['values']['template'] = $this->templateFormElement($user);
    } elseif ($form_state->getValue('action') == 'url') {
      $form['values']['shop_url'] = [
        '#type' => 'url',
        '#title' => $this->t('Event shop URL'),
      ];
    }

    return $form;
  }

  protected function templateFormElement(UserInterface $user) {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $field */
    $field = $user->get('field_pretix_default_events');
    $templates = [];
    foreach ($field->referencedEntities() as $paragraph) {
      $templates[$paragraph->get('field_event_short_form')
        ->first()
        ->getString()] = $paragraph->get('field_name')->first()->getString();
    }
    return [
      '#type' => 'select',
      '#title' => $this->t('Template'),
      '#options' => $templates
    ];
  }

  public function formOverview(array $form, FormStateInterface $form_state, EditorialContentEntityBase $entity, UserInterface $user) {
    $form['slug'] = [
      '#type' => 'item',
      '#title' => $this->t('URL'),
      '#description' => $this->eventManager->getEventUrl($entity)
    ];

    $form['dates'] = [
      '#type' => 'table',
      '#responsive' => TRUE,
      '#empty' => $this->t('No dates available.'),
      '#header' => [
        'date_from' => $this->t('Event start time'),
        [
          'data' => $this->t('Event end time'),
          'class' => [RESPONSIVE_PRIORITY_LOW],
        ],
        [
          'data' => $this->t('Start of presale'),
          'class' => [RESPONSIVE_PRIORITY_LOW],
        ],
        [
          'data' => $this->t('End of presale'),
          'class' => [RESPONSIVE_PRIORITY_LOW],
        ],
        [
          'data' => $this->t('Price'),
          'class' => [RESPONSIVE_PRIORITY_MEDIUM],
        ],
        'operations' => $this->t('Operations'),
      ],
    ];

    $subevents = $this->eventManager->getSubEvents($entity);
    if (!isset($subevents['results'])) {
      return $form;
    }
    foreach ($subevents['results'] as $key => $subevent) {
      $date_from = new DrupalDateTime($subevent['date_from']);
      $form['dates'][$key] = [
        'date_from' => [
          '#type' => 'markup',
          '#markup' => $date_from->format('d/m/Y - H:i'),
        ],
        'date_to' => [],
        'presale_start' => [],
        'presale_end' => [],
        'price' => [],
        'operations' => []
      ];

      if (!is_null($subevent['date_to'])) {
        $date_to = new DrupalDateTime($subevent['date_to']);
        $form['dates'][$key]['date_to'] = [
          '#type' => 'markup',
          '#markup' => $date_to->format('d/m/Y - H:i'),
        ];
      }

      if (!is_null($subevent['presale_start'])) {
        $presale_start = new DrupalDateTime($subevent['presale_start']);
        $form['dates'][$key]['presale_start'] = [
          '#type' => 'markup',
          '#markup' => $presale_start->format('d/m/Y - H:i'),
        ];
      }

      if (!is_null($subevent['presale_end'])) {
        $presale_end = new DrupalDateTime($subevent['presale_end']);
        $form['dates'][$key]['presale_end'] = [
          '#type' => 'markup',
          '#markup' => $presale_end->format('d/m/Y - H:i'),
        ];
      }

      if (isset($subevent['item_price_overrides'][0]['price'])) {
        $price = $subevent['item_price_overrides'][0]['price'];
        if ($price == 0) {
          $price = $this->t('Free');
        } else {
          $price = number_format(floatval($subevent['item_price_overrides'][0]['price']), 2, ',', '.') . ' kr.';
        }
        $form['dates'][$key]['price'] = [
          '#type' => 'markup',
          '#markup' => $price,
        ];
      }

      $form['dates'][$key]['operations'] = [
        '#type' => 'operations',
        '#links' => [
          [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('os2uol_pretix.edit_subevent', ['entity_type_id' => $entity->getEntityTypeId(), 'entity_id' => $entity->id(), 'subevent' => $subevent['id']]),
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => '80%',
              ]),
            ],
          ],
          [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('os2uol_pretix.delete_subevent', ['entity_type_id' => $entity->getEntityTypeId(), 'entity_id' => $entity->id(), 'subevent' => $subevent['id']]),
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => '500',
                'height' => '200'
              ]),
            ],
          ],
        ],
      ];
    }

    $form['#cache']['max-age'] = 0;
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
  public function dateCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#pretix_date_wrapper', $form['date']));
    return $response;
  }

  /**
   * AJAX callback for refreshing content.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function actionCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#pretix_action_wrapper', $form['values']));
    return $response;
  }

  /**
   * @return DateFormatter
   */
  protected function getDateFormatter() {
    if (!$this->dateFormatter) {
      $this->dateFormatter = \Drupal::service('date.formatter');
    }
    return $this->dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\Core\Entity\EditorialContentEntityBase $entity */
    $entity = $this->getEntity();
    /** @var EntityOwnerTrait $entityOwner */
    $entityOwner = $this->getEntity();
    /** @var \Drupal\user\UserInterface $user */
    $user = $entityOwner->getOwner();

    if (!$this->eventManager->isPretixEnabledUser($user)) {
      return [];
    } elseif (!$this->eventManager->hasPretixShopURL($entity)) {
      $actions['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#submit' => ['::submitForm'],
        '#button_type' => 'primary',
      ];
      return $actions;
    } else {
      $actions['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#submit' => ['::resetForm'],
        '#button_type' => 'danger',
      ];
      return $actions;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Do nothing
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EditorialContentEntityBase $entity */
    $this->entity = $this->buildEntity($form, $form_state);

    if ($form_state->getValue('action') == 'create') {
      /** @var \Drupal\os2uol_pretix\PretixEventManager $eventManager */
      $eventManager = \Drupal::service('os2uol_pretix.event_manager');

      $event = [
        'slug' => $this->entity->id(),
        'currency' => 'DKK',
        'is_public' => $this->entity->isPublished(),
        'date_from' => $eventManager->formatDateFormValue(new DrupalDateTime())
      ];
      $result = $eventManager->createEvent($this->entity, $form_state->getValue('template'), $event);
      if (!empty($result)) {
        $this->entity->set('field_pretix_template_event', [0 => ['value' => $form_state->getValue('template')]]);
        $this->entity->set('field_pretix_event_short_form', [0 => ['value' => $result['slug']]]);
        if (!\Drupal::currentUser()->hasPermission('use editorial transition publish')) {
          $this->entity->set('moderation_state', 'draft');
        }
      }
    } elseif ($form_state->getValue('action') == 'choose') {
      $this->entity->set('field_pretix_template_event', [0 => ['value' => $form_state->getValue('template')]]);
      $this->entity->set('field_pretix_event_short_form', [0 => ['value' => $form_state->getValue('event')]]);
      if (!\Drupal::currentUser()->hasPermission('use editorial transition publish')) {
        $this->entity->set('moderation_state', 'draft');
      }
    } elseif ($form_state->getValue('action') == 'url') {
      $this->entity->set('field_pretix_shop_url', [0 => ['uri' => $form_state->getValue('shop_url')]]);
      if (!\Drupal::currentUser()->hasPermission('use editorial transition publish')) {
        $this->entity->set('moderation_state', 'draft');
      }
    }
    $this->entity->save();
  }

  public function resetForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EditorialContentEntityBase $entity */
    $this->entity = $this->buildEntity($form, $form_state);

    $this->entity->set('field_pretix_template_event', []);
    $this->entity->set('field_pretix_event_short_form', []);
    $this->entity->set('field_pretix_shop_url', []);
    if (!\Drupal::currentUser()->hasPermission('use editorial transition publish')) {
      $this->entity->set('moderation_state', 'draft');
    }
    $this->entity->save();
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
