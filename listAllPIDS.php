#!/usr/bin/env drush

#<?php

// grab the first user supplied parameter as the name of the collection
$collection = drush_shift();

if (! $collection) {
    drush_print("***Error: please provide the name of the collection as the first argument");
    drush_print("Example: drush php-script listAllPIDS.php islandora:collection_name_here");
    return;
}

// include all php files necessary for Tuque
foreach (glob("/var/www/drupal/htdocs/sites/all/libraries/tuque/*.php") as $filename) {
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
                              
// query to grab all pdf collection objects from the repository
$sparqlQuery = "SELECT ?s
                FROM <#ri>
                WHERE {
                    ?s <info:fedora/fedora-system:def/relations-external#isMemberOfCollection>
                    <info:fedora/$collection> .
                }";

// run query
drush_print("\nQuerying repository for all objects in the $collection collection...");
$allPDFObjects = $repository->ri->sparqlQuery($sparqlQuery);
drush_print("Query complete\n");

// check number of objects in the collection to make sure we have some
$totalNumObjects = count($allPDFObjects);
if ($totalNumObjects <= 0) {
    drush_print("***Error: no objects found in the given collection. Check the collection name.");
    drush_print("***No processing was completed. Exiting.");
    return;
} else {
    drush_print("There are $totalNumObjects objects to be processed");
}

drush_print("\nBeginning main processing loop\n");
for ($counter = 0; $counter < $totalNumObjects; $counter ++) {
    // grab the next object from the result set
    $theObject = $allPDFObjects[$counter];
    
    // increment the counter shown to the user
    $realCount = $counter + 1;
//     drush_print("Processing record $realCount of $totalNumObjects");
    
    // grab the PID value from the object array
    $objectPID = $theObject['s']['value'];
    
    drush_print($objectPID);
}


drush_print("\nMain processing loop complete\n");

echo "\n\nAll operations complete\n";