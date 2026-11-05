<?php

namespace Drupal\os2uol_domain\Hook;

use Drupal\Core\Extension\ProceduralCall;
use Drupal\Core\Hook\Attribute\ReorderHook;
use Drupal\Core\Hook\Order\OrderAfter;

/**
 * Preserves the project's user-domain filter after Domain 2.0's alteration.
 *
 * Domain Access now runs at weight 20 and defines current_all for users too.
 * Our existing filter must replace that definition, as it did before upgrading.
 */
#[ReorderHook(
  hook: 'views_data_alter',
  class: ProceduralCall::class,
  method: 'os2uol_domain_views_data_alter',
  order: new OrderAfter(modules: ['domain_access']),
)]
final class ViewsDataOrder {}
