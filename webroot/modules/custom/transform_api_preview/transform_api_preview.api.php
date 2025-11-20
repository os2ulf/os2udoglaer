<?php

/**
 * @file
 * Hooks related to Transform API module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the url redirected to when trying to preview a transformation.
 *
 * @param string $url
 *    The that will be redirected to.
 * @param string $uuid
 *    The uuid of the preview.
 */
function hook_transform_preview_url_alter(&$url, $uuid) {

}

/**
 * @} End of "addtogroup hooks".
 */
