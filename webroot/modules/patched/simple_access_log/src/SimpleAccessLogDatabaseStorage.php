<?php

namespace Drupal\simple_access_log;

use Drupal\Component\Utility\Unicode;
/**
 * Provides the default database storage backend for statistics.
 */
class SimpleAccessLogDatabaseStorage implements SimpleAccessLogStorageInterface
{

    /**
     * {@inheritdoc}
     */
    public static function logAccess($values)
    {
        $db = \Drupal::database();
        if ($db) {
            $fields = ['remote_host', 'host', 'title', 'uri', 'path', 'referer', 'user_agent'];
            foreach ($fields as $field) {
                if (!empty($values[$field])) {
                    //Truncate overlength database fields
                    $values[$field] = Unicode::truncate($values[$field], 255);
                }
            }
            try{
                $db->insert('simple_access_log')->fields($values)->execute();
            } catch(\Exception $e) {
                \Drupal::logger('simple_access_log')->error('An error occurred while trying to save in the database.\n' . $e->getMessage());
                return FALSE;
            }
            return TRUE;
        } else {
            \Drupal::logger('simple_access_log')->error('Could not write to database.');
            return FALSE;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function purgeOld($timestamp)
    {
        // Get a database connection.
        $db = \Drupal::database();
        // Delete values older than the oldest timestamp.
        $num_deleted = $db->delete('simple_access_log')
            ->condition('timestamp', $timestamp, '<')
            ->execute();
        // Return the number of records deleted. I don't think we're
        // actually doing anything with this value.
        return $num_deleted;
    }

}
