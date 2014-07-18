#!/usr/bin/env drush

#<?php
/**
 * TL;DR removes OBJ.0 DS's if there's a more recent datastream version with the same checksum
 * 
 * This script is intended to scan an Islandora PDF collection and find objects
 * that have an OBJ.0 datastream without a checksum and an OBJ.X datastream with a checksum.
 * The script computes a SHA-256 checksum for the OBJ.0 datastream and checks to see
 * if it matches the next most recent datastream version's checksum. 
 * 
 * If there is a match, the script will remove the OBJ.0 (oldest) datastream version.
 * 
 * Usage: pass the collection name and the datastream label to the program
 *
 * Example: drush scr purgeNonChecksumOBJDS.php collection DatastreamLabel
 * ie) drush scr purgeNonChecksumOBJDS.php islandora:sp_pdf_collection OBJ
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


// grab the first user supplied parameter as the name of the collection
$collection = drush_shift();

if (! $collection) {
    drush_print("***Error: please provide the name of the collection as the first argument");
    drush_print("Example: drush scr purgeNonChecksumOBJDS.phpp islandora:collection_name_here OBJ");
    return;
}

// grab the second user supplied paramter as the name of the datastream we care about
$dslabel = drush_shift();

if (! $dslabel) {
    drush_print("***ERROR: please provide the name of the datastream label as the second argument");
    drush_print("Example: drush scr purgeNonChecksumOBJDS.php islandora:collection_name_here OBJ");
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

// we will use this to compute the hash
$hashType = 'sha256';

// setup variables for later use
$spaceFreed = 0;
$dsToDelete = 0;

// setup arrays for later use
$objectsWithProblems = array();
$followsThePattern = array();
$skippedObjects = array();



// set up connection and repository variables
$connection = new RepositoryConnection($url, $username, $password);
$api = new FedoraApi($connection);
$repository = new FedoraRepository($api, new SimpleCache());
$api_m = $repository->api->m; // Fedora management API
$api_a = $repository->api->a; // Fedora Access API

// query to grab all pdf collection objects from the repository
$sparqlQuery = "SELECT ?s
                FROM <#ri>
                WHERE {
                    ?s <info:fedora/fedora-system:def/relations-external#isMemberOfCollection>
                    <info:fedora/$collection> .
                }";


drush_print("\nQuerying repository for all PDF objects...");

// run query
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
    drush_print("Processing record $realCount of $totalNumObjects");

    // grab the PID value from the object array
    $objectPID = $theObject['s']['value'];
    
    // get the datastream history of the object
    $dshistory = $api_m->getDatastreamHistory($objectPID, $dslabel); //NB: ds's are returned in order from most to least recent
    
    // reverse the history so the first element is the oldest (first created)
    $oldestToNewestDS = array_reverse($dshistory);

    $oldestDS = $oldestToNewestDS[0];
    $oldestDSChecksum = $oldestDS['dsChecksum'];
    
    // if the oldest version of this DS has no checksum...
    if ($oldestDSChecksum == 'none' || empty($oldestDSChecksum)) {
        
        // if the second oldest version of this DS has a checksum...
        if (!empty($oldestToNewestDS[1]['dsChecksum'])) {
            
            // this object fits our search pattern, lets keep track of that
            $followsThePattern[] = $objectPID;
//             drush_print("$objectPID matches the pattern");

            try {
                // grab OBJ.0 datastream content
                $oldestDSVersionContent = $api_a->getDatastreamDissemination($objectPID, $dslabel, $oldestDS['dsCreateDate'], NULL);
                
                // hash the OBJ.0 datastream
                $computedHash = hash($hashType, $oldestDSVersionContent);

                // if the hash of the oldest version of the DS's content (just computed) equals the
                // hash of the next most recent DS's content (computed already and stored) then we 
                // can delete the oldest version of the DS as it is exactly the same as the 
                // more recent version
                if ($oldestToNewestDS[1]['dsChecksum'] === $computedHash) {
                    drush_print("Deleting the OBJ.0 datastream as the hashes are the same");
                    
                    // track the amount of space we are freeing by deleting this DS
                    // (not available after we delete it)
                    $spaceFreed += $oldestDS['dsSize'];	
                    
                    // delete the .0 datastream (oldest version)
                    $api_m->purgeDatastream($objectPID, $dslabel, array(
                        'startDT' => $oldestDS['dsCreateDate'],
                        'endDT' => $oldestDS['dsCreateDate'],
                        'logMessage' => "Deleting $objectPID $dslabel.0 datastream",
                    ));
                    
                    // tracking how many datastreams we delete
                    $dsToDelete++;
                }
            } catch (Exception $e) {
                drush_print("Skipping object $objectPID");
                $skippedObjects[] = $objectPID;
                continue;
            }
        } 
    }
}
drush_print("Main processing loop complete");
print "\n";

$theCount = count($followsThePattern);
drush_print("There are $theCount number of datastreams contained in $totalNumObjects objects that match the pattern");
drush_print("We deleted $dsToDelete OBJ.0 datastreams");
drush_print("We freed " . formatBytes($spaceFreed, 3) . " of space");

if (!empty($skippedObjects)) {
	drush_print("We skipped " . count($skippedObjects) . " objects due to issues");
	foreach ($skippedObjects as $skip) {
		drush_print("Skipped: $skip");
	}
}

return;
