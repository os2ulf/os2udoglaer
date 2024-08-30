<?php

namespace Drupal\os2uol_pretix\Form;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\os2uol_pretix\PretixEventManager;
use Drupal\os2uol_pretix\Routing\PretixRouteProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PretixSubEventDeleteForm extends ConfirmFormBase {

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

  protected $subevent_id;
  protected $entity_type_id;
  protected $entity_id;

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
    return 'os2uol_pretix.subevent.delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this date?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity = $this->getEntity();
    $routeName = PretixRouteProvider::getPretixRouteName($entity->getEntityType());
    return new Url($routeName, [$this->entity_type_id => $this->entity_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $entity_id = NULL, $subevent = NULL): array {
    $this->entity_type_id = $entity_type_id;
    $this->entity_id = $entity_id;
    $this->subevent_id = $subevent;

    return parent::buildForm($form, $form_state);
  }

  public function getEntity(): EditorialContentEntityBase {
    $entity_type_id = $this->entity_type_id;
    $entity_id = $this->entity_id;
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    /** @var EditorialContentEntityBase $entity */
    $entity = $storage->load($entity_id);
    return $entity;
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $subevent_id = $this->subevent_id;

    if (!is_null($this->eventManager->deleteSubEvent($entity, $subevent_id))) {
      $this->messenger()
        ->addStatus($this->t('Successfully deleted date'));
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
  }

}
