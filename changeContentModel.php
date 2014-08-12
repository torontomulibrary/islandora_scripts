#!/usr/bin/env drush

#<?php

/**
 * This script is designed to change the content model of an object in the repo
 * 
 * Call this script using drush and supply it with the object PID you want to change as the parameter
 *
 * @author Paul Church
 * @author MJ Suhonos
 * 
 * @date August 2014
 */

// grab the first user supplied parameter as the name of the collection
$objectPID = drush_shift();

if (! $objectPID) {
    drush_print("***Error: please provide the object PID as the first argument");
    drush_print("Example: drush php-script editMODSTopicsSingleObject.php RULA:13");
    return;
}

// include all Tuque php files
$tuquePath = libraries_get_path('tuque') . '/*.php';
foreach (glob($tuquePath) as $filename) {
    require_once ($filename);
}

// repository connection parameters
$url = 'localhost:8080/fedora';
$username = 'fedoraAdmin';
$password = 'fedoraAdmin';

// set up connection and repository variables
$connection = new RepositoryConnection($url, $username, $password);
$api = new FedoraApi($connection);
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
drush_print("*************Object relationships before changes*************");
$object_content_models = $object->relationships->get('info:fedora/fedora-system:def/model#', 'hasModel');
print_r($object_content_models);


/** 
 * THIS IS FOR CHANGING PDF MODEL -> CITATION MODEL
 * 
 * Change the third argument to the 'add' and 'remove' methods to reflect the changes you need
**/
$fedrelsext = new FedoraRelsExt($object);

// add this relationship to all objects in the Research Collection
$fedrelsext->add("info:fedora/fedora-system:def/model#", "hasModel", "ir:thesisCModel");

// delete this relationship from all objects in the Research Collection
$fedrelsext->remove("info:fedora/fedora-system:def/model#", "hasModel", "islandora:sp_pdf"); 


drush_print("*************Object relationships AFTER changes*************");
$object_content_models = $object->relationships->get('info:fedora/fedora-system:def/model#', 'hasModel');
print_r($object_content_models);

drush_print("\nAll operations complete\n");
