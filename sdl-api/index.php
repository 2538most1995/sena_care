<?php
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/services/SdlLegacyGateway.php';
(new SdlLegacyGateway())->handle();
