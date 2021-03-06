<?php

$config = array();

// Set true to put the application in debug mode.
$config['debug'] = false;

// Whether to trust headers set by proxies and load balancers.
// This should only be true when the application is behind a revers proxy.
$config['request.trust_proxy'] = false;

// The HTTP port. This is used to generate absolute URLs.
$config['request.http_port'] = 80;

// The HTTPs port. This is used to generate absolute URLs.
$config['request.https_port'] = 443;

// The controller namespace prefix is used by the dispatcher
// to generate the full class name of the controller.
$config['controller.namespace_prefix'] = '\Tikilive\Api\Controller\\';

return $config;
