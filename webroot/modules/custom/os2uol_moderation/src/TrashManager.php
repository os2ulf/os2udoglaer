<?php

declare(strict_types=1);

namespace Drupal\os2uol_moderation;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;

/**
 * Moves moderated content to the trash state.
 */
final class TrashManager {

  /**
   * The trash moderation state ID.
   */
  public const TRASH_STATE = 'trash';

  public function __construct(
    private readonly ModerationInformationInterface $moderationInformation,
    private readonly StateTransitionValidationInterface $transitionValidation,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly TimeInterface $time,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Checks whether an entity uses a workflow with a trash state.
   */
  public function supports(ContentEntityInterface $entity): bool {
    if (!$entity->hasField('moderation_state') || !$this->moderationInformation->isModeratedEntity($entity)) {
      return FALSE;
    }

    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);

    return $workflow !== NULL && $workflow->getTypePlugin()->hasState(self::TRASH_STATE);
  }

  /**
   * Checks whether an account may move an entity to the trash.
   */
  public function access(ContentEntityInterface $entity, AccountInterface $account): AccessResultInterface {
    $entity = $this->loadLatestRevision($entity);

    if (!$this->supports($entity)) {
      return AccessResult::forbidden()
        ->addCacheableDependency($entity);
    }

    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $update_access = $entity->access('update', $account, TRUE);
    if (!$update_access->isAllowed()) {
      return $update_access;
    }

    $has_transition = FALSE;
    foreach ($this->transitionValidation->getValidTransitions($entity, $account) as $transition) {
      if ($transition->to()->id() === self::TRASH_STATE) {
        $has_transition = TRUE;
        break;
      }
    }

    $transition_access = AccessResult::allowedIf($has_transition)
      ->cachePerPermissions()
      ->addCacheableDependency($entity)
      ->addCacheableDependency($workflow);

    return $update_access->andIf($transition_access);
  }

  /**
   * Moves an entity's latest revision to the trash.
   */
  public function moveToTrash(ContentEntityInterface $entity, AccountInterface $account): bool {
    $entity = $this->loadLatestRevision($entity);
    if (!$this->access($entity, $account)->isAllowed()) {
      return FALSE;
    }

    $entity->setNewRevision(TRUE);
    $entity->set('moderation_state', self::TRASH_STATE);

    if ($entity instanceof RevisionLogInterface) {
      $entity->setRevisionUserId($account->id());
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionLogMessage('Moved to trash.');
    }

    $entity->save();

    $this->logger->notice('Moved @entity_type @entity_id to the trash by user @uid.', [
      '@entity_type' => $entity->getEntityTypeId(),
      '@entity_id' => $entity->id(),
      '@uid' => $account->id(),
    ]);

    return TRUE;
  }

  /**
   * Loads the latest translation-affected revision of an entity.
   */
  private function loadLatestRevision(ContentEntityInterface $entity): ContentEntityInterface {
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    if (!$storage instanceof ContentEntityStorageInterface || $entity->isNew()) {
      return $entity;
    }

    $langcode = $entity->language()->getId();
    $revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $langcode);
    if ($revision_id === NULL || (string) $revision_id === (string) $entity->getLoadedRevisionId()) {
      return $entity;
    }

    $latest_revision = $storage->loadRevision($revision_id);
    if (!$latest_revision instanceof ContentEntityInterface) {
      return $entity;
    }

    if ($latest_revision->hasTranslation($langcode)) {
      $latest_revision = $latest_revision->getTranslation($langcode);
    }

    return $latest_revision;
  }

}
