<?php

namespace Drupal\os2uol_application_forms\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a VBO action for transitioning nodes through the application workflow.
 *
 * @Action(
 *   id = "os2uol_application_forms_moderation_action",
 *   label = @Translation("Change application workflow state"),
 *   type = "node",
 *   confirm = TRUE
 * )
 */
class ApplicationModerationActions extends ViewsBulkOperationsActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(ContentEntityInterface $entity = NULL) {
    // Check if the entity has a moderation state field.
    if (!$entity->hasField('moderation_state')) {
      return $this->t('This content does not have a moderation state field.');
    }

    // Get the current state of the entity.
    $current_state = $entity->get('moderation_state')->getString();

    // Switch the state based on the current state.
    switch ($current_state) {
      case 'draft':
        $entity->set('moderation_state', 'accepted');
        break;

      case 'accepted':
        $entity->set('moderation_state', 'awaiting_payment');
        break;

      case 'awaiting_payment':
        $entity->set('moderation_state', 'ready_for_payment');
        break;

      case 'ready_for_payment':
        $entity->set('moderation_state', 'published');
        break;

      case 'published':
        $entity->set('moderation_state', 'closed');
        break;

      case 'draft':
        $entity->set('moderation_state', 'refused');
        break;

      case 'draft':
        $entity->set('moderation_state', 'cancelled');
        break;
    }

    // Save the entity to persist the state change.
    $entity->save();

    return $this->t('The moderation state of :title has been changed to :state.', [
      ':title' => $entity->label(),
      ':state' => $entity->get('moderation_state')->getString(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // Check if the user has permission to update the entity.
    return $object->access('update', $account, $return_as_object);
  }
}
