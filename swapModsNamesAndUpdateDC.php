#!/usr/bin/env drush

#<?php

# include all php files necessary for Tuque
foreach ( glob("/var/www/drupal/htdocs/sites/all/libraries/tuque/*.php") as $filename) {
	require_once($filename);
}

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

drush_print("\n******Beginning main processing loop*****\n");
for ($counter = 0; $counter < $totalNumObjects; $counter++) {
    
    $theObject = $allPDFObjects[$counter];
    $realCount = $counter + 1;
    drush_print("Processing record $realCount of $totalNumObjects");
    
    //print $theObject['s']['value'];
    $objectPID = $theObject['s']['value'];
    
    # what is the pid you want?
    //$pid = 'islandora:1';
    
    # try to fetch PID from repo
    try {
        //drush_print("Attempting to access $objectPID from repository");
        $object = $repository->getObject($objectPID);
    }
    catch (Exception $e) {
        drush_print("\n\n**********#######  ERROR  #######*********");
        drush_print("***Could not get object $objectPID from repo***\n\n");
        continue;
    }
    
    # grab the MODS data stream
    $modsDS = $object['MODS'];
    
    /****************MODS RECORD**********************/
    //drush_print("Editing MODS record");
    $modsDOMDoc = new DOMDocument();
    $modsDOMDoc->loadXML($modsDS->content);
    $modsXPath = new DOMXPath($modsDOMDoc);
    $modsXPath->registerNameSpace('mods', 'http://www.loc.gov/mods/v3');
    
    // loop through all <name type="personal"> entries looking for authors
    foreach ($modsXPath->query('//mods:name[@type="personal"]') as $node) {
    
        // get original values
        $originalGivenName = trim($modsXPath->query('mods:namePart[@type="given"]', $node)->item(0)->nodeValue);
        $originalFamilyName = trim($modsXPath->query('mods:namePart[@type="family"]', $node)->item(0)->nodeValue);
    
        // swap values
        $modsXPath->query('mods:namePart[@type="given"]', $node)->item(0)->nodeValue = $originalFamilyName;
        $modsXPath->query('mods:namePart[@type="family"]', $node)->item(0)->nodeValue = $originalGivenName;
    
    }
    
    // write the new updated info back into the datastream
    $modsDS->setContentFromString($modsDOMDoc->saveXML($modsDOMDoc->documentElement));
    
    # ingest edited datastream into the repository
    $object->ingestDatastream($modsDS);
    
    //drush_print("MODS record updated");
    /*************MODS RECORD COMPLETE*****************/
    
    
    /******************DUBLIN CORE ********************/
    //drush_print("Re-generating Dublin Core");
    // update the DC based on the MODS record
    $document = new DOMDocument();
    $document->loadXML($modsDS->content);
    $transform = 'mods_to_dc.xsl';
    
    // the magic call
    xml_form_builder_update_dc_datastream($object, $transform, $document);
    
    //drush_print("Dublin core regenerated");
    /*************DUBLIN CORE COMPLETE*****************/
    
}
drush_print("Main processing loop complete");

echo "\n\nAll operations complete\n";
