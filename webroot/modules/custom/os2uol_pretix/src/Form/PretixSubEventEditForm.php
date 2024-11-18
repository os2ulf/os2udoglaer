<?php

namespace Drupal\os2uol_pretix\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_overview\Ajax\HistoryReplaceStateCommand;
use Drupal\entity_overview\OverviewFilter;
use Drupal\os2uol_pretix\PretixEventManager;
use Drupal\os2uol_pretix\Routing\PretixRouteProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PretixSubEventEditForm extends FormBase {

  /**
   * The Pretix event manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Pretix event manager.
   *
   * @var PretixEventManager
   */
  protected $eventManager;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\os2uol_pretix\PretixEventManager $pretix_event_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PretixEventManager $pretix_event_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventManager = $pretix_event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('os2uol_pretix.event_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2uol_pretix.subevent.edit';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $entity_id = NULL, $subevent = NULL): array {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    /** @var EditorialContentEntityBase $entity */
    $entity = $storage->load($entity_id);
    $subevent = $this->eventManager->getSubEvent($entity, $subevent);
    $quotas = $this->eventManager->getQuotas($entity, $subevent);
    $quota = $quotas['results'][0]['size'] ?? NULL;
    $price = $subevent['item_price_overrides'][0]['price'] ?? 0;

    $form = [
      '#title' => $this->t('Edit date'),
      '#cache' => ['max-age' => 0]
    ];
    $form['subevent_id'] = [
      '#type' => 'hidden',
      '#default_value' => $subevent['id']
    ];
    $form['entity_type_id'] = [
      '#type' => 'hidden',
      '#default_value' => $entity_type_id
    ];
    $form['entity_id'] = [
      '#type' => 'hidden',
      '#default_value' => $entity_id
    ];
    $form['quota'] = [
      '#type' => 'number',
      '#title' => $this->t('Quota'),
      '#required' => TRUE,
      '#default_value' => $quota ?? 0
    ];
    $form['date_from'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Event start time'),
      '#required' => TRUE,
      '#default_value' => new DrupalDateTime($subevent['date_from'])
    ];
    $form['date_to'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Event end time'),
      '#required' => FALSE,
      '#default_value' => is_null($subevent['date_to']) ? NULL : new DrupalDateTime($subevent['date_to'])
    ];
    $form['presale_start'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Start of presale'),
      '#required' => FALSE,
      '#default_value' => is_null($subevent['presale_start']) ? NULL : new DrupalDateTime($subevent['presale_start'])
    ];
    $form['presale_end'] = [
      '#type' => 'datetime',
      '#title' => $this->t('End of presale'),
      '#required' => FALSE,
      '#default_value' => is_null($subevent['presale_end']) ? NULL : new DrupalDateTime($subevent['presale_end'])
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

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type_id = $form_state->getValue('entity_type_id');
    $entity_id = $form_state->getValue('entity_id');
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    /** @var EditorialContentEntityBase $entity */
    $entity = $storage->load($entity_id);

    $subevent = [
      'id' => $form_state->getValue('subevent_id'),
      'date_from' => $this->eventManager->formatDateFormValue($form_state->getValue('date_from'))
    ];
    if (!empty($form_state->getValue('date_to'))) {
      $subevent['date_to'] = $this->eventManager->formatDateFormValue($form_state->getValue('date_to'));
    }
    if (!empty($form_state->getValue('presale_start'))) {
      $subevent['presale_start'] = $this->eventManager->formatDateFormValue($form_state->getValue('presale_start'));
    }
    if (!empty($form_state->getValue('presale_end'))) {
      $subevent['presale_end'] = $this->eventManager->formatDateFormValue($form_state->getValue('presale_end'));
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

    if (!is_null($this->eventManager->editSubEvent($entity, $subevent))) {
      $this->messenger()
        ->addStatus($this->t('Successfully edited date'));
      $routeName = PretixRouteProvider::getPretixRouteName($entity->getEntityType());
      $form_state->setRedirect($routeName, [$entity->getEntityTypeId() => $entity->id()]);
    }
  }
}
