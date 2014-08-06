#!/usr/bin/env drush

#<?php

/**
 * This script is designed to transform the MODS records of a single object which contains:
 *
 * <subject>
 * <topic>Education</topic>
 * <topic>Health Education</topic>
 * <topic>Other Data</topic>
 * </subject>
 *
 * into a record that looks like this:
 *
 * <subject>
 * <topic>Education</topic>
 * </subject>
 * <subject>
 * <topic>Health Education</topic>
 * </subject>
 * <subject>
 * <topic>Other Data</topic>
 * </subject>
 *
 * This script also updates the DC record after the changes are made.
 *
 * @author Paul Church
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

// keep track of how many troublesome objects we had to skip
$skippedObjects = array();

// try to fetch PID from repo
try {
    // drush_print("Attempting to access $objectPID from repository");
    $object = $repository->getObject($objectPID);
} catch (Exception $e) {
    drush_print("\n\n**********#######  ERROR  #######*********");
    drush_print("***Could not get object $objectPID from repo***\n\n");
    $skippedObjects[] = $objectPID;
    continue;
}

// grab the MODS data stream
$modsDS = $object['MODS'];

/**
 * **************MODS RECORD*********************
 */
// drush_print("Editing MODS record");
$modsDOMDoc = new DOMDocument();
$modsDOMDoc->preserveWhiteSpace = false;
$modsDOMDoc->formatOutput = true;
$modsDOMDoc->loadXML($modsDS->content);
$modsXPath = new DOMXPath($modsDOMDoc);
$modsXPath->registerNameSpace('mods', 'http://www.loc.gov/mods/v3');

// flag to indicate if datastream reingest and DC regen is needed
$updateThisRecord = FALSE;

$domElemsToRemove = array();

// loop through all <subject> nodes
foreach ($modsXPath->query('//mods:subject') as $node) {
    
    // loop through all the <topic> nodes that are children of <subject> nodes
    // everytime we find a topic node, we create a new <subject><topic/></subject>
    // trio and insert it in the DOM before the original <subject> node
    foreach ($modsXPath->query('mods:topic', $node) as $topicNode) {
        
        $newSubjectNode = $modsDOMDoc->createElement('subject');
        $newTopicNode = $modsDOMDoc->createElement('topic', htmlspecialchars($topicNode->nodeValue));
        
        $newNode = $node->parentNode->insertBefore($newSubjectNode, $node);
        $newNode->appendChild($newTopicNode);
        
        $updateThisRecord = TRUE;
        
        // add this subject node to a list to be removed as it contains multiple topics
        $domElemsToRemove[] = $node;
    }
}

if (! empty($domElemsToRemove)) {
    // our array may have duplicate elements in it, let's remove those
    $domElemsToRemove = array_unique($domElemsToRemove);
    
    // remove all subject nodes that contained multiple topics
    foreach ($domElemsToRemove as $toBeRemoved) {
        $toBeRemoved->parentNode->removeChild($toBeRemoved);
    }
}

if ($updateThisRecord) {
    
    try {
        // write the new updated info back into the datastream
        $modsDS->setContentFromString($modsDOMDoc->saveXML($modsDOMDoc->documentElement));
        
        // ingest edited datastream into the repository
        $object->ingestDatastream($modsDS);
    } catch (Exception $e) {
        drush_print("\n\n**********#######  ERROR  #######*********");
        drush_print("***Could not set $objectPID MODS datastream content or ingest into repo ****\n\n");
        $skippedObjects[] = $objectPID;
        return;
    }
    
    // drush_print("MODS record updated for object pid: $objectPID\n");
    /**
     * ***********MODS RECORD COMPLETE****************
     */
    
    try {
        /**
         * ****************DUBLIN CORE *******************
         */
        // drush_print("Re-generating Dublin Core");
        // update the DC based on the MODS record
        $document = new DOMDocument();
        $document->loadXML($modsDS->content);
        $transform = 'mods_to_dc.xsl';
        
        // the magic call
        xml_form_builder_update_dc_datastream($object, $transform, $document);
        
        // drush_print("Dublin core regenerated");
    /**
     * ***********DUBLIN CORE COMPLETE****************
     */
    } catch (Exception $e) {
        drush_print("\n\n**********#######  ERROR  #######*********");
        drush_print("***Could not update $objectPID DC record ****\n\n");
        $skippedObjects[] = $objectPID;
        return;
    }
}

drush_print("Main processing loop complete");
if (! empty($skippedObjects)) {
    $skippedObjects = array_unique($skippedObjects);
    drush_print("The script had problems with the following PID's");
    foreach ($skippedObjects as $skipped) {
        drush_print($skipped);
    }
}
echo "\n\nAll operations complete\n";
