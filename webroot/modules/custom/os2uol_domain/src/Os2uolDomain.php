<?php

namespace Drupal\os2uol_domain;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class Os2uolDomain.
 */
class Os2uolDomain {

  public const DEFAULT_DOMAIN_ID = 'api_os2udoglaer_dk';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new Os2uolDomain object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Set default domain on node create.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function entityCreate(EntityInterface $entity) {
    // Check if the entity is a node.
    if ($entity->getEntityTypeId() == 'node') {
      // Check if the domain field exists.
      if ($entity->hasField('field_domain_access')) {
        // Get the current domain values.
        $current_domains = $entity->get('field_domain_access')->getValue();

        // Add the new domain. Replace default domain ID with your actual domain ID.
        $current_domains[] = ['target_id' => self::DEFAULT_DOMAIN_ID];

        // Set the domain field with the updated array.
        $entity->set('field_domain_access', $current_domains);
      }
    }
  }
}
