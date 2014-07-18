#!/usr/bin/env drush

#<?php
/**
 * This script is intended to delete the oldest version of the given datastream
 * for the given object
 * 
 * Usage: pass the PID of the object and the DS label to the script
 *
 * Example: drush php-script deleteOldestDS.php PID datastreamlabel
 * ie) "drush scr deleteOldestDS.php islandora:3 OBJ"
 * will delete the OBJ.0 or oldest DS version from the islandora:3 object
 *
 * @author Paul Church
 * @date July 2014
 */



/**
 * Taken from stackoverflow: 
 * https://stackoverflow.com/questions/2510434/format-bytes-to-kilobytes-megabytes-gigabytes
 * 
 * @param unknown $size
 * @param number $precision
 * @return string
 */
function formatBytes($size, $precision = 2)
{
    $base = log($size) / log(1024);
    $suffixes = array('', 'kB', 'MB', 'GB', 'TB');

    return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
}

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

$spaceFreed = 0;

$dshistory = $api_m->getDatastreamHistory($PID, $dslabel); //NB: ds's are returned in order from most to least recent
$oldestToNewestDS = array_reverse($dshistory);

drush_print("$dslabel datastream history before deletion");
print_r($oldestToNewestDS);

$oldestDS = $oldestToNewestDS[0];
$spaceFreed += $oldestDS['dsSize'];

// remove the oldest DS version
$api_m->purgeDatastream($PID, $dslabel, array(
    'startDT' => $oldestToNewestDS[0]['dsCreateDate'],
    'endDT' => $oldestToNewestDS[0]['dsCreateDate'],
    'logMessage' => '',
));

$dshistory = $api_m->getDatastreamHistory($PID, $dslabel); //NB: ds's are returned in order from most to least recent
$oldestToNewestDS = array_reverse($dshistory);

drush_print("$dslabel datastream history after deletion");
print_r($oldestToNewestDS);

drush_print("\nAmount of space freed: " . formatBytes($spaceFreed, 3));

return;
