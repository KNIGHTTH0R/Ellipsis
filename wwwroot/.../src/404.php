<?php

$message = (isset($_ENV['PARAMS']['message']) ? $_ENV['PARAMS']['message'] : "Missing Page");

echo "404: $message";

