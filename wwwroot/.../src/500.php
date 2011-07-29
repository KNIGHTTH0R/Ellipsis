<?php

$message = (isset($_PARAM['message']) ? $_PARAM['message'] : "Server Error");

echo "500: $message";

