<?php

namespace Drupal\simple_access_log;

/**
 * Provides an interface defining Simple Access Log Storage.
 *
 * Stores basic details of each access request.
 */
interface SimpleAccessLogStorageInterface {

  /**
   * Count a entity view.
   *
   * @param array $values
   *   An array of keyed values to be logged.
   *
   * @return bool
   *   TRUE if the access has been logged.
   */
  public static function logAccess($values);

  /**
   * @param int $timestamp
   *   Epoch timestamp of the oldest record to be purged.
   *
   * @return int
   *   Return number of records that were purged.
   */
  public static function purgeOld($timestamp);

}
