#!/usr/bin/env drush

#<?php

/**
 * This script is intended to regenerate the TECHMD, TN, Preview and FULL_TEXT 
 * datastreams for all items in the given collection
 * 
 * Usage: pass the name of the collection to the script as the first argument
 * 
 * Example: drush php-script regenSelectDerivatives.php collection_name
 * 
 * @author Paul Church 
 * @date July 2014
 */

# grab the first user supplied parameter as the name of the collection
$collection = drush_shift();

if (!$collection) {
    drush_print("***Error: please provide the name of the collection as the first argument");
    drush_print("Example: drush php-script regenFITSdata.php islandora:collection_name_here");
    return;
}

# include all php files necessary for Tuque
foreach ( glob("/var/www/drupal/htdocs/sites/all/libraries/tuque/*.php") as $filename) {
	require_once($filename);
}

# Include the file from islandora_fits module that regens the FITS/TECHMD data for us
require_once('/var/www/drupal/htdocs/sites/all/modules/islandora_fits/includes/derivatives.inc');

# Include the file from islandora_pdf module to regenerate the TN datastream for us
require_once('/var/www/drupal/htdocs/sites/all/modules/islandora_solution_pack_pdf/includes/derivatives.inc');

# repository connection parameters
$url      = 'localhost:8080/fedora';
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
                    <info:fedora/$collection> .
                }";

# run query
drush_print("\nQuerying repository for all PDF objects...");
$allPDFObjects = $repository->ri->sparqlQuery($sparqlQuery);
drush_print("Query complete\n");

// check number of objects in the collection to make sure we have some
$totalNumObjects = count($allPDFObjects);
if ($totalNumObjects <= 0) {
    drush_print("***Error: no objects found in the given collection. Check the collection name.");
    drush_print("***No processing was completed. Exiting.");
    return;
}
else {
    drush_print("There are $totalNumObjects objects to be processed");
}

// establish a counter for how many objects we edit
$objectsChanged = 0;

drush_print("\nBeginning main processing loop\n");
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
    
    // Regenerate the TECHMD datastream
    $fitsResult = islandora_fits_create_techmd($object, $forceGeneration, array(
    		'source_dsid' => 'OBJ', 
    		'destination_dsid' => 'TECHMD', 
    		'weight' => '0.01',
    		'function' => array(
			     'islandora_fits_create_techmd',
    		),
    		'file' => drupal_get_path('module', 'islandora_fits') . '/includes/derivatives.inc',
	  ));
    
    // check to make sure the result was successful as reported by the function 
    if ($fitsResult['success'] != 1) {
        print("\n\n**ERROR generating TECHMD datastream for $objectPID\n");
        print_r($fitsResult);
    }
    
    // Regen the TN datastream
    $tnResult = islandora_pdf_add_tn_derivative($object, $forceGeneration);
    
    // check to make sure the result was successful as reported by the function
    if ($tnResult['success'] != 1) {
        print("\n\n**ERROR generating TN datastream for $objectPID\n");
        print_r($tnResult);
    }
    
    // Regen the FULL_TEXT datastream
    $fulltextResult = islandora_pdf_add_fulltext_derivative($object, $forceGeneration);
    
    // check to make sure the result was successful as reported by the function
    if ($fulltextResult['success'] != 1) {
        print("\n\n**ERROR generating FULL_TEXT datastream for $objectPID\n");
        print_r($fulltextResult);
    }
    
    // Regen the Preview datastream
    $previewResult = islandora_pdf_add_preview_derivative($object, $forceGeneration);
    
    // check to make sure the result was successful as reported by the function
    if ($previewResult['success'] != 1) {
        print("\n\n**ERROR generating PREVIEW datastream for $objectPID\n");
        print_r($previewResult);
    }
    
    if ($fitsResult['success']==1 && $tnResult['success']==1 && $fulltextResult['success']==1
            && $previewResult['success']==1) {
        $objectsChanged++;
    }
}
drush_print("Main processing loop complete");
drush_print("$objectsChanged out of $totalNumObjects objects were updated");
echo "\n\nAll operations complete\n";












