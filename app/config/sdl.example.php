<?php
if (!defined('APP_ROOT')) die('Direct access not permitted');
return [
    'enabled' => false,
    'mode' => 'remote',
    'base_url' => 'https://krumost.com/SDL_school/api/v1/integrations/student-data',
    'token' => '', // Set through the super-admin integration page. Never commit a live token.
];
