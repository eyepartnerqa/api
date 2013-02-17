<?php

$config = array();

// The hostname on which the database server resides.
$config['dsn']['host'] = 'localhost';

// The port number where the database server is listening.
$config['dsn']['port'] = '3306';

// The name of the database.
$config['dsn']['dbname'] = 'tikilive5';

// The character set.
$config['dsn']['charset'] = 'utf8';

// The user name for the DSN string.
$config['username'] = 'root';

// The password for the DSN string.
$config['password'] = '';

// Error reporting.
$config['options'][\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

// Set default fetch mode.
$config['options'][\PDO::ATTR_DEFAULT_FETCH_MODE] = \PDO::FETCH_ASSOC;

// Request a persistent connection, rather than creating a new connection.
$config['options'][\PDO::ATTR_PERSISTENT] = true;

// Do not emulate prepares in PDO driver (best used with MySQL >= 5.1.17).
$config['options'][\PDO::ATTR_EMULATE_PREPARES] = false;

// Do not use the buffered versions of the MySQL API.
$config['options'][\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;

return $config;
