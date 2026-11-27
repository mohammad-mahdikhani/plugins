<?php
/**
 * Backend for php-proxy.php. Edit on the host; do not commit secrets here.
 *
 * Local gunicorn (same VPS / SSH process): keep allow_remote false.
 * Python on a second 512MB VPS, PHP only on webakery.ir: set backend to that
 * VPS URL and allow_remote to true.
 */
return array(
    'backend' => 'http://127.0.0.1:8080',
    'allow_remote' => false,
    'timeout' => 60,
);
