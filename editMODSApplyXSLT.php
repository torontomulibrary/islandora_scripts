#!/usr/bin/env drush

#<?php

/**
 * This script is designed to transform MODS records by applying 
 * a provided path to an XSLT file.
 * 
 * This script also updates the DC record after the changes are made.
 * 
 * @author MJ Suhonos
 * @date September 2014
 */

# grab the first user supplied parameter as the name of the collection
$collection = drush_shift();

if (!$collection) {
    drush_print("***Error: please provide the name of the collection as the first argument");
    drush_print("Example: drush php-script editMODSTopics.php islandora:collection_name_here /path/to/file.xsl");
    return;
}

# grab the second user supplied parameter as the name of the XSLT file
$xslt = drush_shift();

if (!$xslt) {
    drush_print("***Error: please provide the name of the XSLT as the second argument");
    drush_print("Example: drush php-script editMODSTopics.php islandora:collection_name_here /path/to/file.xsl");
    return;
}

if (!file_exists($xslt)) {
	drush_print("***Error: provided XSLT file/path does not exist: $xslt");
	return;
}

// include all Tuque php files
$tuquePath = libraries_get_path('tuque') . '/*.php';
foreach (glob($tuquePath) as $filename) {
    require_once ($filename);
}

# repository connection parameters
$url = 'localhost:8080/fedora';
$username = 'fedoraAdmin';
$password = 'fedoraAdmin';

# set up connection and repository variables
$connection = new RepositoryConnection($url, $username, $password);
$api = new FedoraApi($connection);
$repository = new FedoraRepository($api, new SimpleCache());

# query to grab all collection objects from the repository
$sparqlQuery = "SELECT ?s
                FROM <#ri>
                WHERE {
                    ?s <info:fedora/fedora-system:def/relations-external#isMemberOfCollection> 
                    <info:fedora/$collection> .
                }";

# run query
drush_print("\n*****Querying repository for all objects in collection...");
$allObjects = $repository->ri->sparqlQuery($sparqlQuery);
drush_print("\n*****Query complete*****\n");

// how many total objects are there in the collection?
$totalNumObjects = count($allObjects);
drush_print("There are $totalNumObjects objects to be processed");

// establish a counter for how many objects we edit
$objectsChanged = 0;

// keep track of how many troublesome objects we had to skip
$skippedObjects = array();

// main loop for ALL OBJECTS in the collection
drush_print("\n******Beginning main processing loop*****\n");
for ($counter = 0; $counter < $totalNumObjects; $counter++) {
    
    $theObject = $allObjects[$counter];
    $realCount = $counter + 1;
    drush_print("Processing record $realCount of $totalNumObjects");
    
    //print $theObject['s']['value'];
    $objectPID = $theObject['s']['value'];
       
    # try to fetch PID from repo
    try {
        //drush_print("Attempting to access $objectPID from repository");
        $object = $repository->getObject($objectPID);
    }
    catch (Exception $e) {
        drush_print("\n\n**********#######  ERROR  #######*********");
        drush_print("***Could not get object $objectPID from repo***\n\n");
        $skippedObjects[] = $objectPID;
        continue;
    }
    
    // flag to indicate if datastream reingest and DC regen is needed
    $updateThisRecord = FALSE;
    
    # grab the MODS data stream
    $modsDS = $object['MODS'];

    /****************MODS RECORD**********************/
    drush_print("Opening MODS record");

    $modsDOMDoc = new DOMDocument();
    $modsDOMDoc->preserveWhiteSpace = false;
    $modsDOMDoc->formatOutput = true;
    $modsDOMDoc->loadXML($modsDS->content);

	$xslDoc = new DOMDocument();
	$xslDoc->load($xslt);

	// Apply the XSL to the document and get the resultant transformed XML
	$proc = new XSLTProcessor();
	$proc->importStylesheet($xslDoc);
	$transformedXML = $proc->transformToXML($modsDOMDoc);

    if ($updateThisRecord) {
        
        try {
            // write the new updated info back into the datastream
            $modsDS->setContentFromString($transformedXML);
            
            # ingest edited datastream into the repository
            $object->ingestDatastream($modsDS);

			drush_print("MODS record updated for object pid: $objectPID\n");
        }
        catch (Exception $e) {
            drush_print("\n\n**********#######  ERROR  #######*********");
            drush_print("***Could not set $objectPID MODS datastream content or ingest into repo ****\n\n");
            $skippedObjects[] = $objectPID;
            continue;
        }
            
            
        try {
            /******************DUBLIN CORE ********************/
            drush_print("Re-generating Dublin Core");
            // update the DC based on the MODS record
            $document = new DOMDocument();
            $document->loadXML($modsDS->content);
            $transform = 'mods_to_dc.xsl';
            
            // the magic call
            xml_form_builder_update_dc_datastream($object, $transform, $document);

            drush_print("Dublin core regenerated");
            /*************DUBLIN CORE COMPLETE*****************/
            
        }
        catch (Exception $e) {
            drush_print("\n\n**********#######  ERROR  #######*********");
            drush_print("***Could not update $objectPID DC record ****\n\n");
            $skippedObjects[] = $objectPID;
            continue;
        }

        // keep track of how many objects we edited
        $objectsChanged++;
        
    } else {
		print_r($transformedXML);
    }

    /*************MODS RECORD COMPLETE*****************/	
}

drush_print("Main processing loop complete");
drush_print("$objectsChanged out of $totalNumObjects were updated");
if (!empty($skippedObjects)) {
    $skippedObjects = array_unique($skippedObjects);
    drush_print("The script had problems with the following PID's");
    foreach ($skippedObjects as $skipped) {
        drush_print($skipped);
    }
}
echo "\n\nAll operations complete\n";
