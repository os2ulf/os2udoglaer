<?php

declare(strict_types=1);

namespace Drupal\os2uol_application_forms;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Defines a service provider for the OS2udoglær Application Forms (GDPR) module.
 *
 * @see https://www.drupal.org/node/2026959
 */
final class Os2uolApplicationFormsServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    if ($container->hasDefinition('content_moderation_notifications.notification')) {
      $definition = $container->getDefinition('content_moderation_notifications.notification');
      $definition->setClass('Drupal\os2uol_application_forms\Os2Notification');
    }
  }

}
