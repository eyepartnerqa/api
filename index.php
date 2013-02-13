<?php

// Setup Composer autoloader.
require '../../vendor/autoload.php';

// Load the API application.
$api = require 'application.php';

// Run the application.
$api()->handle()->send();
