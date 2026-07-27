<?php

declare(strict_types=1);

namespace Drupal\os2uol_moderation\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\os2uol_moderation\Plugin\Action\MoveToTrashAction;
use Drupal\os2uol_moderation\TrashManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Confirms moving multiple moderated nodes to the trash.
 */
final class MoveToTrashMultipleForm extends ConfirmFormBase {

  /**
   * The private tempstore.
   */
  private readonly PrivateTempStore $tempStore;

  /**
   * Selected node IDs keyed by selected language codes.
   *
   * @var array<int|string, array<string, string>>
   */
  private array $selection = [];

  public function __construct(
    private readonly AccountInterface $currentUser,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    PrivateTempStoreFactory $tempStoreFactory,
    private readonly TrashManager $trashManager,
  ) {
    $this->tempStore = $tempStoreFactory->get(MoveToTrashAction::TEMPSTORE_COLLECTION);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
      $container->get('os2uol_moderation.trash_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'os2uol_moderation_move_to_trash_multiple_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(
      count($this->selection),
      'Are you sure you want to delete this content item?',
      'Are you sure you want to delete these content items?',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('The content can be restored from the trash until it is automatically purged.');
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
  public function getCancelUrl(): Url {
    return Url::fromRoute('system.admin_content');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array|RedirectResponse {
    $selection = $this->tempStore->get(MoveToTrashAction::selectionKey($this->currentUser));
    $this->selection = is_array($selection) ? $selection : [];
    if ($this->selection === []) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $items = [];
    $nodes = $this->entityTypeManager
      ->getStorage(MoveToTrashAction::ENTITY_TYPE_ID)
      ->loadMultiple(array_keys($this->selection));

    foreach ($this->selection as $id => $langcodes) {
      if (!isset($nodes[$id]) || !$nodes[$id] instanceof NodeInterface) {
        continue;
      }

      $node = $nodes[$id];
      $langcode = reset($langcodes);
      if (is_string($langcode) && $node->hasTranslation($langcode)) {
        $node = $node->getTranslation($langcode);
      }
      $items[] = $node->label();
    }

    $form['entities'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $moved_count = 0;
    $skipped_count = 0;
    $storage = $this->entityTypeManager->getStorage(MoveToTrashAction::ENTITY_TYPE_ID);
    $entities = $storage->loadMultiple(array_keys($this->selection));

    foreach ($this->selection as $id => $selected_langcodes) {
      if (!isset($entities[$id])) {
        $skipped_count++;
        continue;
      }

      $entity = $entities[$id];
      if (!$entity instanceof NodeInterface) {
        $skipped_count++;
        continue;
      }

      $langcode = reset($selected_langcodes);
      if (is_string($langcode) && $entity->hasTranslation($langcode)) {
        $entity = $entity->getTranslation($langcode);
      }

      if ($this->trashManager->moveToTrash($entity, $this->currentUser)) {
        $moved_count++;
      }
      else {
        $skipped_count++;
      }
    }

    if ($moved_count > 0) {
      $this->messenger()->addStatus($this->formatPlural(
        $moved_count,
        'Deleted @count content item.',
        'Deleted @count content items.',
      ));
    }

    if ($skipped_count > 0) {
      $this->messenger()->addWarning($this->formatPlural(
        $skipped_count,
        '@count content item could not be deleted.',
        '@count content items could not be deleted.',
      ));
    }

    $this->tempStore->delete(MoveToTrashAction::selectionKey($this->currentUser));
    $form_state->setRedirectUrl(Url::fromRoute('view.trash.page_1'));
  }

}
