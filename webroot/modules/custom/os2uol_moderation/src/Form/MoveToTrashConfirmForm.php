<?php

declare(strict_types=1);

namespace Drupal\os2uol_moderation\Form;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\os2uol_moderation\TrashManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirms moving a moderated node to the trash.
 */
final class MoveToTrashConfirmForm extends ConfirmFormBase {

  /**
   * The node being moved.
   */
  private ?NodeInterface $node = NULL;

  public function __construct(
    private readonly TrashManager $trashManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('os2uol_moderation.trash_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'os2uol_moderation_move_to_trash_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL): array {
    $this->node = $node;
    return parent::buildForm($form, $form_state);
  }

  /**
   * Checks access to the move-to-trash form.
   */
  public function access(NodeInterface $node, AccountInterface $account): AccessResultInterface {
    return $this->trashManager->access($node, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %label?', [
      '%label' => $this->node?->label(),
    ]);
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
    return $this->node?->toUrl('canonical') ?? Url::fromRoute('system.admin_content');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($this->node === NULL || !$this->trashManager->moveToTrash($this->node, $this->currentUser())) {
      $this->messenger()->addError($this->t('The content could not be deleted.'));
      return;
    }

    $this->messenger()->addStatus($this->t('%label has been deleted.', [
      '%label' => $this->node->label(),
    ]));
    $form_state->setRedirectUrl(Url::fromRoute('view.trash.page_1'));
  }

}
