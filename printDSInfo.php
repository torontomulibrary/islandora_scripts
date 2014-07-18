#!/usr/bin/env drush

#<?php
/**
 * This script simply prints out the datastream information of a single object
 * and single datastream label
 * 
 * @author Paul Church
 * @date July 2014
 * 
 * 
 * Usage: drush scr printDSInfo.php objectPID datastreamLabel
 * 
 * Example:  drush scr printDSInfo.php islandora:3 TECHMD
 * 
 */

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

# include all Tuque php files
$tuquePath = libraries_get_path('tuque') . '/*.php';
foreach ( glob($tuquePath) as $filename) {
    require_once($filename);
}

// repository connection parameters
$url = 'localhost:8080/fedora';
$username = 'fedoraAdmin';
$password = 'fedoraAdmin';

// set up connection and repository variables
$connection = new RepositoryConnection($url, $username, $password);
$api = new FedoraApi($connection);
$repository = new FedoraRepository($api, new SimpleCache());
$api_m = $repository->api->m;
$api_a = $repository->api->a;

$info = array_reverse($api_m->getDatastreamHistory($PID, $dslabel));
print_r($info);

$history = $api_a->getObjectHistory($PID);
$profile = $api_a->getObjectProfile($PID, new DateTime());

print_r($history);
print_r($profile);

drush_print("\nMain processing loop complete\n");

echo "\n\nAll operations complete\n";
