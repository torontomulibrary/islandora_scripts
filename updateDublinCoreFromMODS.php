#!/usr/bin/env drush

#<?php

/**
 * This script is designed to update the DC record of an object based off the MODS record
 * 
 * Call this script using drush and supply it with the object PID you want to change as the parameter
 *
 * @author Paul Church
 * 
 * @date August 2014
 */

// grab the first user supplied parameter as the name of the collection
$objectPID = drush_shift();

if (! $objectPID) {
    drush_print("***Error: please provide the object PID as the first argument");
    drush_print("Example: drush php-script updateDublinCoreFromMODS.php RULA:13");
    return;
}

// include all Tuque php files
$tuquePath = libraries_get_path('tuque') . '/*.php';
foreach (glob($tuquePath) as $filename) {
    require_once ($filename);
}

module_load_include('inc', 'islandora', 'includes/derivatives');
// require_once(drupal_get_path('module', 'islandora').'/includes/derivatives.inc');

// repository connection parameters
$url = 'localhost:8080/fedora';
$username = 'fedoraAdmin';
$password = 'fedoraAdmin';

// set up connection and repository variables
$connection = new RepositoryConnection($url, $username, $password);
$api        = new FedoraApi($connection);
$repository = new FedoraRepository($api, new SimpleCache());


// try to fetch PID from repo
try {
    // drush_print("Attempting to access $objectPID from repository");
    $object = $repository->getObject($objectPID);
} catch (Exception $e) {
    drush_print("\n\n**********#######  ERROR  #######**********");
    drush_print("***Could not get object $objectPID from repo***\n\n");
    return;
}

/**
 * Copy/Paste from the islandora_oai module
 */
$last_modified_before = $object->lastModifiedDate;
islandora_do_derivatives($object, array(
  'force' => TRUE,
  'source_dsid' => 'MODS',
  'destination_dsid' => 'DC',
));
$last_modified_after = $object->lastModifiedDate;
$success = TRUE;

if ($last_modified_before == $last_modified_after) {
  $success = FALSE;
}
if ($success) {
  drush_log(dt("Dublin Core record updated for @pid.", array('@pid' => $object->id)), 'success');
}
else {
  drush_log(dt("Dublin Core record update failed for @pid. Check the Drupal watchdog for detailed errors.", array('@pid' => $object->id)), 'error');
}
/**
 * End copy/paste
 */