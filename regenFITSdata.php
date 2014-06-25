#!/usr/bin/env drush

#<?php

# include all php files necessary for Tuque
foreach ( glob("/var/www/drupal/htdocs/sites/all/libraries/tuque/*.php") as $filename) {
	require_once($filename);
}

# Include the file from islandora_fits module that regens the FITS data for us
require_once('/var/www/drupal/htdocs/sites/all/modules/islandora_fits/includes/derivatives.inc');

# repository connection parameters
$url = 'localhost:8080/fedora';
$username = 'fedoraAdmin';
$password = 'fedoraAdmin';

# set up connection and repository variables
$connection = new RepositoryConnection($url, $username, $password);
$api = new FedoraApi($connection);
$repository = new FedoraRepository($api, new SimpleCache());

# query to grab all pdf collection objects from the repository
$sparqlQuery = "SELECT ?s
                FROM <#ri>
                WHERE {
                    ?s <info:fedora/fedora-system:def/relations-external#isMemberOfCollection> 
                    <info:fedora/islandora:sp_pdf_collection> .
                }";

# run query
drush_print("\n*****Querying repository for all PDF objects...");
$allPDFObjects = $repository->ri->sparqlQuery($sparqlQuery);
drush_print("\n*****Query complete*****\n");

// main loop for ALL PDF OBJECTS in the collection
$totalNumObjects = count($allPDFObjects);
drush_print("There are $totalNumObjects objects to be processed");

// establish a counter for how many objects we edit
$objectsChanged = 0;

drush_print("\n******Beginning main processing loop*****\n");
for ($counter = 0; $counter < $totalNumObjects; $counter++) {
    
    // grab the next object from the result set
    $theObject = $allPDFObjects[$counter];
    
    // increment the counter shown to the user
    $realCount = $counter + 1;
    drush_print("Processing record $realCount of $totalNumObjects");
    
    // grab the PID value from the object array
    $objectPID = $theObject['s']['value'];
    
    # try to fetch PID from repo
    try {
        $object = $repository->getObject($objectPID);
    }
    catch (Exception $e) {
        drush_print("\n\n**********#######  ERROR  #######*********");
        drush_print("***Could not get object $objectPID from repo***\n\n");
        continue;
    }

    // forces generation/regeneration of FITS data
    $forceGeneration = TRUE;
    
    // the magic call
    $result = islandora_fits_create_techmd($object, $forceGeneration);
    
    // check to make sure the result was successful as reported by the function 
    if ($result['success'] == 1) {
        $objectsChanged++;
    }
    else {
        print("\n\n**ERROR generating FITS data for $objectPID\n");
        print_r($result);
    }
    
}
drush_print("Main processing loop complete");
drush_print("$objectsChanged out of $totalNumObjects objects were updated");
echo "\n\nAll operations complete\n";
