<?php

$envDbHost = getenv('DB_HOST');
$envDbName = getenv('DB_NAME');
$envDbUser = getenv('DB_USER');
$envDbPass = getenv('DB_PASS');
$envDbCharset = getenv('DB_CHARSET');
$envAdminPassword = getenv('ADMIN_PASSWORD');
$envGoogleMapsApiKey = getenv('GOOGLE_MAPS_API_KEY');

$defaults = [
    'db_host' => '127.0.0.1',
    'db_name' => 'house_sale',
    'db_user' => 'root',
    'db_pass' => '',
    'db_charset' => 'utf8mb4',
    'admin_password' => 'NoweJagatowo',
    'google_maps_api_key' => 'AIzaSyAF25tpv9s7agDDU988xz-wjbj0VkhraFo',
];

$definedDbHost = $envDbHost !== false && $envDbHost !== '' ? $envDbHost : $defaults['db_host'];
$definedDbName = $envDbName !== false && $envDbName !== '' ? $envDbName : $defaults['db_name'];
$definedDbUser = $envDbUser !== false && $envDbUser !== '' ? $envDbUser : $defaults['db_user'];
$definedDbPass = $envDbPass !== false ? $envDbPass : $defaults['db_pass'];
$definedDbCharset = $envDbCharset !== false && $envDbCharset !== '' ? $envDbCharset : $defaults['db_charset'];
$definedAdminPassword = $envAdminPassword !== false && $envAdminPassword !== '' ? $envAdminPassword : $defaults['admin_password'];
$definedGoogleMapsApiKey = $envGoogleMapsApiKey !== false ? $envGoogleMapsApiKey : $defaults['google_maps_api_key'];

if (!defined('DB_HOST')) define('DB_HOST', $definedDbHost);
if (!defined('DB_NAME')) define('DB_NAME', $definedDbName);
if (!defined('DB_USER')) define('DB_USER', $definedDbUser);
if (!defined('DB_PASS')) define('DB_PASS', $definedDbPass);
if (!defined('DB_CHARSET')) define('DB_CHARSET', $definedDbCharset);
if (!defined('ADMIN_PASSWORD')) define('ADMIN_PASSWORD', $definedAdminPassword);
if (!defined('GOOGLE_MAPS_API_KEY')) define('GOOGLE_MAPS_API_KEY', $definedGoogleMapsApiKey);
