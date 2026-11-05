<?php

namespace Drupal\simple_access_log\EventSubscriber;

use Drupal\simple_access_log\Controller\SimpleAccessLog;
use Drupal\simple_access_log\SimpleAccessLogDatabaseStorage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class SimpleAccessLogSubscriber.
 *
 * @package Drupal\simple_access_log\EventSubscriber
 *
 * Runs on every request.
 */
class SimpleAccessLogSubscriber implements EventSubscriberInterface {

    /**
     * Executes the logging, gathers the values and calls the
     * database storage log function.
     */
    public function executeLogFunction() {
        if (!empty($values = SimpleAccessLog::logValues())) {
            SimpleAccessLogDatabaseStorage::logAccess($values);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
      $events[KernelEvents::TERMINATE][] = ['executeLogFunction',100];
      return $events;
    }

}
