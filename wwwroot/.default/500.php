<?php

/**
 * default 500 error handler
 *
 * @package default
 */

$message = (isset($_PARAM['message']) ? $_PARAM['message'] : "Server Error");

echo "500: $message";

