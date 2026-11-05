<?php

namespace Drupal\simple_access_log\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\simple_access_log\SimpleAccessLogDatabaseStorage;
use Drupal\Core\Routing\RouteObjectInterface;

/**
 * Class SimpleAccessLog.
 *
 * @package Drupal\simple_access_log\Controller
 *
 * Basic log value gathering and cleanup functions.
 */
class SimpleAccessLog extends ControllerBase implements ContainerInjectionInterface {

  /**
   * logValues function.
   *
   * @return array|bool
   *
   * Returns an array of the current values to be logged or false if the current
   * config prevents logging this request.
   */
  public static function logValues() {
    // Load module settings.
    $settings = \Drupal::config('simple_access_log.settings');

    // Get the request itself, this should supply most of the needed values.
      $request = \Drupal::request();

    // Stop log if DNT Header present and option selected.
    if($settings->get('respect_dnt') && $request->headers->get('dnt')){
      return FALSE;
    }

    $access_log = [];
    //request Time
    $access_log['timestamp'] = \Drupal::time()->getRequestTime();

    // User or Anon?
    $access_log['uid'] = \Drupal::currentUser()->id();
    if(is_null($access_log['uid'])){
      $access_log['uid']=0;
    }

    // skip logging user 0 if enabled
    if ($settings->get('do_not_log_0') && $access_log['uid'] == 0) {
      return FALSE;
    }

    // Skip logging user 1 if enabled.
    if ($settings->get('do_not_log_1') && $access_log['uid'] == 1) {
      return FALSE;
    }

    // Skip logging all users with administrator role if enabled.
    if ($settings->get('do_not_log_admin')) {
      $roles = \Drupal::currentUser()->getRoles();
      foreach ($roles as $rid => $role) {
        if ($role == 'administrator') {
          return FALSE;
        }
      }
    }

    $exclude_or_include = (bool) $settings->get('exclude_or_include');

    // Get the current path.
    $access_log['path'] = $request->getPathInfo();
    // Check exclude paths.
    if (!empty($settings->get('exclude_paths'))) {
      $path_match = \Drupal::service('path.matcher')->matchPath($access_log['path'], $settings->get('exclude_paths'));
      if ($exclude_or_include) {
        //include only
        if (!$path_match) {
          return FALSE;
        }
      }else{
        //exclude
        if ($path_match) {
          return FALSE;
        }
      }
    }

    //Exclude Admin Paths
    if ($settings->get('not_admin_paths') && substr($access_log['path'], 0, 7) == '/admin/') {
      return FALSE;
    }

    // Get the Client IP.
    $access_log['remote_host'] = $request->getClientIp();

    // Get hostname or host IP.
    $access_log['host'] = (strlen($request->server->get('SERVER_NAME')) > 1 ? $request->server->get('SERVER_NAME') : $request->server->get('SERVER_ADDR'));

    // Get Page Title from the current route.
    if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $access_log['title'] = (is_array(\Drupal::service('title_resolver')->getTitle($request, $route))?\Drupal::service('title_resolver')->getTitle($request, $route)['#markup']:\Drupal::service('title_resolver')->getTitle($request, $route));
    }

    // Ge the URI requested.
    $access_log['uri'] = $request->getRequestUri();


    // Get the Referer variable.
    $access_log['referer'] = $request->server->get('HTTP_REFERER');

    // Get the clietn's User Agent.
    $access_log['user_agent'] = $request->server->get('HTTP_USER_AGENT');

    // Return our loaded access log array.
    return $access_log;
  }

  /**
   * @return int number of records deleted
   *
   * Clean up old records from the database.
   * @todo this should probably be partially moved the the DatabaseStorage class.
   */
  public static function cleanup() {
    // Get the module's settings.
    $settings = \Drupal::config('simple_access_log.settings');
    // Set the oldest timestamp to be the time now minus the difference setting.
    $oldest = time() - $settings->get('delete_log_after');
    // Purge old data
    SimpleAccessLogDatabaseStorage::purgeOld($oldest);
  }

}