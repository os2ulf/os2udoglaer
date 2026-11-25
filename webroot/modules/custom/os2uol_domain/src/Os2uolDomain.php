<?php

namespace Drupal\os2uol_domain;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Class Os2uolDomain.
 */
class Os2uolDomain {

  public const DEFAULT_DOMAIN_ID = 'api_os2udoglaer_dk';

  /**
   * The domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * Constructs a new Os2uolDomain object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   The domain negotiator.
   */
  public function __construct(DomainNegotiatorInterface $domain_negotiator) {
    $this->domainNegotiator = $domain_negotiator;
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
        $field = $entity->get('field_domain_access');
        $field_definition = $field->getFieldDefinition();
        // Get the current domain values.
        $current_domains = $field->getValue();

        // API submissions do not run Domain Access's entity form preparation.
        if ($field->isEmpty()
          && $field_definition instanceof ThirdPartySettingsInterface
          && $field_definition->getThirdPartySetting('domain_access', 'add_current_domain')) {
          $active_domain = $this->domainNegotiator->getActiveDomain();
          if ($active_domain) {
            $current_domains[] = ['target_id' => $active_domain->id()];
          }
        }

        if (!in_array(self::DEFAULT_DOMAIN_ID, array_column($current_domains, 'target_id'), TRUE)) {
          $current_domains[] = ['target_id' => self::DEFAULT_DOMAIN_ID];
        }

        // Set the domain field with the updated array.
        $entity->set('field_domain_access', $current_domains);
      }
    }
  }
}
