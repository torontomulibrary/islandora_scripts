#!/usr/bin/env drush

#<?php

/**
 * This script is designed to transform MODS records which contain a specific topic subject->topic entry
 * that when originally edited contained an unescaped ampersand "&"
 *  
 * This is a one-off script designed to fix some errors introduced into very few
 * objects MODS records due to ampersands being in the data and requires hand-editing
 * the value to replace and the value to replace with
 * 
 * <subject>
 *   <topic>Education</topic>
 * </subject>
 * <subject>
 *   <topic>Research and Development (R</topic>
 * </subject>
 * 
 * into records that look like this:
 * 
 * <subject>
 *   <topic>Education</topic>
 * </subject>
 * <subject>
 *   <topic>Research and Development (R&amp;D)</topic>
 * </subject>
 * 
 * This script also updates the DC record after the changes are made.
 * 
 * @author Paul Church
 * @date July 2014
 */

/**
This is the topic you want to replace, most likely this contains incomplete information
such as containing 
"research and development (R"
instead of
"research and development (R&D)"
 */
$topicSearchString = "returns to college";

/**
This is the value to be inserted to replace the value above. This is where you put
the complete information of the topic. It will be escaped using 'htmlspecialchars'
 */
$topicReplaceString = "&&& returns to college &&&";


# grab the first user supplied parameter as the PID of the object
$objectPID = drush_shift();

if (!$objectPID) {
    drush_print("***Error: please provide the name of the PID as the first argument");
    drush_print("Example: drush php-script editMODSTopicSingleEntry.php RULA:107");
    return;
}

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

       
# try to fetch PID from repo
try {
    drush_print("Attempting to access $objectPID from repository");
    $object = $repository->getObject($objectPID);
}
catch (Exception $e) {
    drush_print("\n\n**********#######  ERROR  #######*********");
    drush_print("***Could not get object $objectPID from repo***\n\n");
    $skippedObjects[] = $objectPID;
    continue;
}

# grab the MODS data stream
$modsDS = $object['MODS'];

/****************MODS RECORD**********************/
drush_print("Editing MODS record");
$modsDOMDoc = new DOMDocument();
$modsDOMDoc->preserveWhiteSpace = false;
$modsDOMDoc->formatOutput = true;
$modsDOMDoc->loadXML($modsDS->content);
$modsXPath = new DOMXPath($modsDOMDoc);
$modsXPath->registerNameSpace('mods', 'http://www.loc.gov/mods/v3');

// flag to indicate if datastream reingest and DC regen is needed
$updateThisRecord = FALSE;

// loop through all <subject> nodes
foreach ($modsXPath->query('//mods:subject') as $node) {
    
    foreach ($modsXPath->query('mods:topic', $node) as $topicNode) {
        
        if ($topicNode->nodeValue === $topicSearchString) {
            $topicNode->nodeValue = htmlspecialchars($topicReplaceString);
        }
        
        $updateThisRecord = TRUE;
    }
}
   
if ($updateThisRecord) {
    
    try {
        // write the new updated info back into the datastream
        $modsDS->setContentFromString($modsDOMDoc->saveXML($modsDOMDoc->documentElement));
        
        # ingest edited datastream into the repository
        $object->ingestDatastream($modsDS);
    }
    catch (Exception $e) {
        drush_print("\n\n**********#######  ERROR  #######*********");
        drush_print("***Could not set $objectPID MODS datastream content or ingest into repo ****\n\n");
        return;
    }
        
        drush_print("MODS record updated for object pid: $objectPID\n");
        /*************MODS RECORD COMPLETE*****************/
        
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
        
        // keep track of how many objects we edited
        $objectsChanged++;
    }
    catch (Exception $e) {
        drush_print("\n\n**********#######  ERROR  #######*********");
        drush_print("***Could not update $objectPID DC record ****\n\n");
        return;
    }
    
}

drush_print("Main processing loop complete");
echo "\n\nAll operations complete\n";
