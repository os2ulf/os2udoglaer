<?php
namespace Drupal\os2uol_application_forms\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * @Action(
 *   id = "set_ready_for_payment_status",
 *   label = @Translation("Set status to Ready for payment"),
 *   type = "node"
 * )
 */
class SetReadyForPaymentStatus extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity instanceof Node) {
      $entity->set('field_rfc_status', 'ready_for_payment');
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
