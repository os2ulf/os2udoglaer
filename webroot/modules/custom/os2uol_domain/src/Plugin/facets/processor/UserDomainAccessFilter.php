<?php

namespace Drupal\os2uol_domain\Plugin\facets\processor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\processor\UidToUserNameCallbackProcessor;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a processor that excludes items based on domain access.
 *
 * @FacetsProcessor(
 *   id = "user_domain_access_filter",
 *   label = @Translation("Filter by domain access"),
 *   description = @Translation("Exclude items depending on the accessibility on the current domain."),
 *   stages = {
 *     "build" = 1
 *   }
 * )
 */
class UserDomainAccessFilter extends UidToUserNameCallbackProcessor implements ContainerFactoryPluginInterface {

  protected DomainNegotiatorInterface $domainNegotiator;
  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, DomainNegotiatorInterface $domainNegotiator, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->domainNegotiator = $domainNegotiator;
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('domain.negotiator'),
      $container->get('entity_type.manager')
    );
  }

  public function build(FacetInterface $facet, array $results) {
    $current_domain = $this->domainNegotiator->getActiveDomain();
    $user_storage = $this->entityTypeManager->getStorage('user');

    /** @var \Drupal\facets\Result\ResultInterface $result */
    foreach ($results as $key => $result) {
      /** @var \Drupal\user\Entity\User $user */
      if (($user = $user_storage->load($result->getRawValue())) !== NULL) {
        // Check if the user has access to the current domain.
        $all_affiliates = $user->get('field_domain_all_affiliates')->getString();

        if ($all_affiliates) {
          continue;
        }

        $user_domains = $user->get('field_domain_access')->getValue();
        $user_domain_ids = array_column($user_domains, 'target_id');

        if (!in_array($current_domain->id(), $user_domain_ids)) {
          unset($results[$key]);
        }
      }
    }

    return $results;
  }

}
