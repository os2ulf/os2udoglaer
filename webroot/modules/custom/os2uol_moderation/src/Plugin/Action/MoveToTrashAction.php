<?php

declare(strict_types=1);

namespace Drupal\os2uol_moderation\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\node\NodeInterface;
use Drupal\os2uol_moderation\TrashManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Prepares selected nodes for the move-to-trash confirmation form.
 */
#[Action(
  id: 'os2uol_moderation_move_to_trash',
  label: new TranslatableMarkup('Delete content'),
  type: 'node',
  confirm_form_route_name: 'os2uol_moderation.node.move_to_trash_multiple',
)]
final class MoveToTrashAction extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The private tempstore collection used by the confirmation form.
   */
  public const TEMPSTORE_COLLECTION = 'os2uol_moderation_move_to_trash';

  /**
   * The selected entity type.
   */
  public const ENTITY_TYPE_ID = 'node';

  /**
   * The private tempstore.
   */
  private readonly PrivateTempStore $tempStore;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PrivateTempStoreFactory $tempStoreFactory,
    private readonly AccountInterface $currentUser,
    private readonly TrashManager $trashManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tempStore = $tempStoreFactory->get(self::TEMPSTORE_COLLECTION);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tempstore.private'),
      $container->get('current_user'),
      $container->get('os2uol_moderation.trash_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!$object instanceof NodeInterface) {
      $result = AccessResult::forbidden();
    }
    else {
      $result = $this->trashManager->access($object, $account ?? $this->currentUser);
    }

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities): void {
    $selection = [];
    foreach ($entities as $entity) {
      if (!$entity instanceof NodeInterface) {
        continue;
      }

      $langcode = $entity->language()->getId();
      $selection[$entity->id()][$langcode] = $langcode;
    }

    $key = self::selectionKey($this->currentUser);
    if ($selection === []) {
      $this->tempStore->delete($key);
      return;
    }

    $this->tempStore->set($key, $selection);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL): void {
    $this->executeMultiple($object instanceof NodeInterface ? [$object] : []);
  }

  /**
   * Builds the per-user tempstore key used by the action and form.
   */
  public static function selectionKey(AccountInterface $account): string {
    return $account->id() . ':' . self::ENTITY_TYPE_ID;
  }

}
