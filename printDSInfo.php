#!/usr/bin/env drush

#<?php

// grab the first user supplied parameter as the PID 
$PID = drush_shift();

if (! $PID) {
    drush_print("***Error: please provide the PID as the first argument");
    drush_print("Example: drush php-script printDSInfo.php RULA:193");
    return;
}

// grab the second user supplied parameter as the DS name 
$dslabel = drush_shift();

if (! $dslabel) {
    drush_print("***Error: please provide the DS name as the second argument");
    drush_print("Example: drush php-script printDSInfo.php RULA:193 OBJ.0");
    return;
}

// include all php files necessary for Tuque
foreach (glob("/var/www/drupal/htdocs/sites/all/libraries/tuque/*.php") as $filename) {
    require_once ($filename);
}

// repository connection parameters
$url = 'localhost:8080/fedora';
$username = 'fedoraAdmin';
$password = '***REMOVED***';

// set up connection and repository variables
$connection = new RepositoryConnection($url, $username, $password);
$api = new FedoraApi($connection);
$repository = new FedoraRepository($api, new SimpleCache());
$api_m = $repository->api->m;

$info = array_reverse($api_m->getDatastreamHistory($PID, $dslabel));
print_r($info);

drush_print("\nMain processing loop complete\n");

echo "\n\nAll operations complete\n";
