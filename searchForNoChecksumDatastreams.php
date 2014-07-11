#!/usr/bin/env drush

#<?php

// grab the first user supplied parameter as the name of the collection
$collection = drush_shift();

if (! $collection) {
    drush_print("***Error: please provide the name of the collection as the first argument");
    drush_print("Example: drush php-script searchForNoChecksumDatastreams.php islandora:collection_name_here FULL_TEXT");
    return;
}

// grab the second user supplied paramter as the name of the datastream we care about
$dslabel = drush_shift();

if (! $dslabel) {
    drush_print("***ERROR: please provide the name of the datastream label as the second argument");
    drush_print("Example: drush php-script searchForNoChecksumDatastreams.php islandora:collection_name_here FULL_TEXT");
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
$api_m = $repository->api->m; // Fedora management API
$api_a = $repository->api->a;
                              
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

$noChecksumDatastreams = array();
$objZeroChecksumsMissing = array();
$numberOfChecksums = 0;

drush_print("\nBeginning main processing loop\n");
for ($counter = 0; $counter < $totalNumObjects; $counter ++) {
    // grab the next object from the result set
    $theObject = $allPDFObjects[$counter];
    
    // increment the counter shown to the user
    $realCount = $counter + 1;
    drush_print("Processing record $realCount of $totalNumObjects");
    
    // grab the PID value from the object array
    $objectPID = $theObject['s']['value'];
    
    
    /****************** ONLY THE SPECIFIED DATASTREAM *****************/
    
    $allDSforObject = array_reverse($api_m->getDatastreamHistory($objectPID, $dslabel));
    foreach($allDSforObject as $objectDS) {
        $numberOfChecksums++;
        if (empty($objectDS['dsChecksum']) || $objectDS['dsChecksum'] == 'none') {
            if ($objectDS['dsVersionID'] == 'OBJ.0') {
                // this is the original ingested objected, expected to be missing a checksum
                $objZeroChecksumsMissing[] = $objectDS;
            }
            else {
                // this is any other DS other than OBJ.0 so shouldn't be missing any checksums hopefully
                $noChecksumDatastreams[] = $objectDS;
                drush_print("$objectPID is missing a checksum on the ".$objectDS['dsVersionID']." datastream");
            }
            
        }
    }
    /*******************************************************************/
    
    
    
    /*************  ALL DATASTREAMS *******************/
    /*
    $dsLabels = array_keys($api_a->listDatastreams($objectPID));
    $dsLabels = array_unique($dsLabels);
//     print_r($dsLabels);
        
    foreach($dsLabels as $theDSLabelkey => $theDSLabelvalue) {
        $allObjectDSs = array_reverse($api_m->getDatastreamHistory($objectPID, $theDSLabelvalue));
        foreach ($allObjectDSs as $objectDS) {
            $numberOfChecksums++;
            if (empty($objectDS['dsChecksum']) || $objectDS['dsChecksum'] == 'none') {
                $noChecksumDatastreams[] = $objectDS;
//                 print_r($objectDS);
                drush_print("$objectPID is missing a checksum on the ".$objectDS['dsVersionID']." datastream");
            }
        }
    }
    */
    /***************************************************/
    
}
$ttlWithNoChecksum = count($noChecksumDatastreams);
$objZeroNumberChecksumsMissing = count($objZeroChecksumsMissing);

drush_print("Main processing loop complete\n");
// drush_print("There are $ttlWithNoChecksum out of $numberOfChecksums objects with missing checksums on the $dslabel datastream");
drush_print("There are $objZeroNumberChecksumsMissing OBJ.0 datastreams without a checksum");
drush_print("There are $ttlWithNoChecksum out of $numberOfChecksums objects with missing checksums on the $dslabel datastream");
echo "\n\nAll operations complete\n";