<?php
namespace Drupal\os2uol_application_forms\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * @Action(
 *   id = "set_closed_status",
 *   label = @Translation("Set status to Closed"),
 *   type = "node"
 * )
 */
class SetClosedStatus extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity instanceof Node) {
      $entity->set('field_rfc_status', 'closed');
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }
}
