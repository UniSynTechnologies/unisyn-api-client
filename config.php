<?php
require_once __DIR__ . '/initialize.php';

define('UNISYNAPIKEY', UNISYN_API_KEY);
define('UNISYNAPISECRET', UNISYN_API_SECRET);
define('APIVERSION', UNISYN_API_VERSION);

define('UNISYN_API_CLIENT_DIR', __DIR__ . '/');

define('UNISYN_API_CLIENT_DIR_REL', str_replace($_SERVER['DOCUMENT_ROOT'], '/', UNISYN_API_CLIENT_DIR));

$httphost = (strtolower($_SERVER['HTTP_HOST']));
$endOfHost = substr($httphost, strrpos($httphost, '.') + 1);
if ($endOfHost === 'dev' || substr(substr($httphost, 0, strpos($httphost, '.') ), -4) === 'test' || $httphost === 'netsuite.unisyntechnologies.com' ) {
	// host ends with dev such as reese.dev or subdomain ends with test such as reesetest
	define('UNISYN_API_CLIENT_ENV', 'dev');
    if (substr(substr($httphost, 0, strpos($httphost, '.') ), -9) === 'stacytest') {
        define('UNISYN_API_SERVER_URL', 'https://stacyapi.unisyntechnologies.com/' . APIVERSION);
    }
    else if (substr(substr($httphost, 0, strpos($httphost, '.') ), -9) === 'noahtest') {
        define('UNISYN_API_SERVER_URL', 'https://noahapi.unisyntechnologies.com/' . APIVERSION);
    }
    else if (substr(substr($httphost, 0, strpos($httphost, '.') ), -9) === 'jaketest') {
        define('UNISYN_API_SERVER_URL', 'https://jakeapi.unisyntechnologies.com/' . APIVERSION);
    }
	else {
		define('UNISYN_API_SERVER_URL', 'https://apistaging.unisyntechnologies.com/' . APIVERSION);
	}
}
else {
	define('UNISYN_API_CLIENT_ENV', 'prod');
	define('UNISYN_API_SERVER_URL', 'https://api.unisyntechnologies.com/' . APIVERSION);
}

if ( empty(UNISYNAPIKEY) ) {
	echo 'No api key specified';
	die();
}

if ( empty(UNISYNAPISECRET) ) {
	echo 'No api secret specified';
	die();
}

if ( empty(APIVERSION) ) {
	echo 'No api version specified';
	die();
}
