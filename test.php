#!/usr/bin/env drush

#<?php

# include all Tuque php files
$tuquePath = libraries_get_path('tuque') . '/*.php';
foreach ( glob($tuquePath) as $filename) {
    require_once($filename);
}

# repository connection parameters
$url        = 'localhost:8080/fedora';
$username   = 'fedoraAdmin';
$password   = 'fedoraAdmin';

# set up connection and repository variables
$connection = new RepositoryConnection($url, $username, $password);
$api        = new FedoraApi($connection);
$repository = new FedoraRepository($api, new SimpleCache());


$pid = "islandora:3";

try {
    //drush_print("Attempting to access $objectPID from repository");
    $object = $repository->getObject($pid);
}
catch (Exception $e) {
    drush_print("\n\n**********#######  ERROR  #######*********");
    drush_print("***Could not get object $pid from repo***\n\n");
    return;
}

$modsDatastream = islandora_datastream_load('MODS', $object);
$modsxml = $modsDatastream->content;

$domdoc = new DOMDocument();
$domdoc->preserveWhiteSpace = false;
$domdoc->formatOutput = true;
$domdoc->loadXML($modsDatastream->content);
$xpath = new DOMXPath($domdoc);
$xpath->registerNameSpace('mods', 'http://www.loc.gov/mods/v3');

$xml_out = $domdoc->saveXML($domdoc->documentElement);
echo $xml_out . "\n\n";

$topicArray = array();
$domElemsToRemove = array();

// loop through all <subject> nodess
foreach ($xpath->query('//mods:subject') as $node) {
    
    // find all the <topic> nodes which are children of each <subject> node
    foreach($xpath->query('mods:topic', $node) as $topicNode) {
        print "$topicNode->nodeValue\n";
        $topicArray[]  = $topicNode->nodeValue;
        
        $newSubjectNode = $domdoc->createElement('subject');
        $newTopicNode = $domdoc->createElement('topic', $topicNode->nodeValue);
        
        $newNode = $node->parentNode->insertBefore($newSubjectNode, $node);
        $newNode->appendChild($newTopicNode);
        
        $domElemsToRemove[] = $node;
    }
    
    
}

foreach( $domElemsToRemove as $toBeRemoved) {
    $toBeRemoved->parentNode->removeChild($toBeRemoved);
}


//     if (trim($xpath->query('mods:role', $node)->item(0)->nodeValue) == "Thesis advisor") {
//         $roleNode = $xpath->query('mods:role', $node)->item(0);
//         $namePartNode = $xpath->query('mods:namePart', $node)->item(0);
        
//         $fullname = $xpath->query('mods:namePart', $node)->item(0)->nodeValue . "\n";
        
//         $fullNameArray = explode(' ', $fullname);
        
//         $givenName = '';
//         for ($i=0;$i < count($fullNameArray)-1;$i++) {
//             $givenName .= trim($fullNameArray[$i]) . " ";
//         }
        
//         $givenName = trim($givenName);        
//         $familyName = trim($fullNameArray[count($fullNameArray)-1]);
        
//         echo "Thesis advisor is ";
//         echo "$givenName $familyName\n";
        
//         $newNodeGivenName = $domdoc->createElement('namePart', $givenName);
//         $newNodeGivenName->setAttribute('type', 'given');
        
//         $newNodeFamilyName = $domdoc->createElement('namePart', $familyName);
//         $newNodeFamilyName->setAttribute('type', 'family');
        
//         $node->insertBefore($newNodeGivenName, $roleNode);
//         $node->insertBefore($newNodeFamilyName, $roleNode);
        
//         $mods = $domdoc->documentElement;
//         $namePartNode->parentNode->removeChild($namePartNode);
        
//     }
    
    
    // get original values
    //$originalGivenName = trim($xpath->query('mods:namePart[@type="given"]', $node)->item(0)->nodeValue);
    //$originalFamilyName = trim($xpath->query('mods:namePart[@type="family"]', $node)->item(0)->nodeValue);

    // swap values
//     $xpath->query('mods:namePart[@type="given"]', $node)->item(0)->nodeValue = $originalFamilyName;
//     $xpath->query('mods:namePart[@type="family"]', $node)->item(0)->nodeValue = $originalGivenName;
    
// }


// foreach ($xpath->query('//mods:name[@type="personal"]') as $node) {
    
//     foreach($other_nodes as $fooNode) {
//         echo $fooNode->nodeValue;
//     }
    
//     foreach ($node->childNodes as $nodeChild) {
        
//         if ((string)$nodeChild->localName == 'namePart') {
            
//             $aNamePart = $nodeChild->attributes->item(0)->nodeValue;
//             if ($aNamePart == 'given') {
//                 $nodeChild->attributes->item(0)->nodeValue == 'family';
//                 //$givenNameOriginal = $nodeChild->attributes->item(0)->nodeValue;
//             }
//             else if ($aNamePart == 'family') {
//                 $nodeChild->attributes->item(0)->nodeValue == 'given';
//                 //$familyNameOriginal = $nodeChild->attributes->item(0)->nodeValue;
//             }
//         }
        
        
//         if ($nodeChild->localName == 'namePart') {
//             foreach($nodeChild->attributes as $attr) {
//                 echo $attr->nodeName; // type
//                 echo $attr->nodeValue; // given/family
//                 if ($attr->nodeName == 'type' && $attr->nodeValue=='given') {
//                     //$attr->nodeValue = !FamilyName!;
//                     echo "found given name\n";
//                     $originalGivenName = $attr->nodeValue;
//                 }
//                 else if ($attr->nodeName == 'type' && $attr->nodeValue=='family') {
//                     //$attr->nodeValue = !givenName!;
//                     echo "found family name\n";
//                     $originalFamilyName = $attr->nodeValue;
//                 }
//             }
//         }
        //echo (string)$nodePart->localName;
//     }
// }


// foreach($xpath->query('//fakeprefix:namePart[@type="given"]') as $rowNode) {
//     echo $rowNode->nodeValue . "\n";
// }
// $givenName = $xpath->query('//mods:namePart[@type="given"]')->item(0)->nodeValue;
// $familyName = $xpath->query('//mods:namePart[@type="family"]')->item(0)->nodeValue;
// echo "\nValues from XML:\n";
// echo "Given name: $givenName\n";
// echo "Family name: $familyName\n";
// echo "\n";

// // switch the values of given name and family name
// $xpath->query('//mods:namePart[@type="given"]')->item(0)->nodeValue = $familyName;
// $xpath->query('//mods:namePart[@type="family"]')->item(0)->nodeValue = $givenName;


// echo "\n\n" . $domdoc->saveXML($domdoc->documentElement);



// $nodeList = $domdoc->getElementsByTagName('namePart');

// foreach ($domdoc->getElementsByTagName('namePart') as $entry) 
// {
//     $entryType = $entry->getAttribute('type');
    
//     print $entry->nodeValue . " " . $entry->getAttribute('type') . "\n";
    
// }

print $domdoc->saveXML($domdoc->documentElement);


// $modsDatastream->setContentFromString($domdoc);

return;




$url = 'localhost:8080/fedora';
$username = 'fedoraAdmin';
$password = 'fedoraAdmin';

# set up connection and repository variables
$connection = new RepositoryConnection($url, $username, $password);
$api = new FedoraApi($connection);
$repository = new FedoraRepository($api, new SimpleCache());


# what is the pid you want?
$pid = 'islandora:3';

# what to change the name to?
$givenName = "Mickey";
$familyName = "Mouse";
$wholeName = $givenName . ' ' . $familyName;

# try to fetch PID from repo
try {
	$object = $repository->getObject($pid);
}
catch (Exception $e) {
	drush_print('****************ERROR***************');
	exit;
}

# grab the data streams
$dublinCoreDS = $object['DC'];
$modsDS = $object['MODS'];

// drush_print('******Dublin Core********');
// drush_print(@$dublinCoreDS->getContent());
// drush_print();

# parse the datastreams into simplexml objects
$modsXML = simplexml_load_string(@$modsDS->getContent());
$dcXML = simplexml_load_string(@$dublinCoreDS->getContent());

//print_r($dcXML->getDocNamespaces());

// foreach ($dcXML->children("dc", TRUE) as $entry)
// {
// 	if ($entry->contributor)
// 	{
// 		$entry->contributor = 'Mickey Mouse';	
// 	} 
// 	//drush_print($entry);
// }

# change the MODS record
$swapVariable = $mods->name['personal']->namePart['given'];
$mods->name['personal']->namePart['given'] = $mods->name['personal']->namePart['family'];
$mods->name['personal']->namePart['given'] = $swapVariable;

# change the contributor in the Dublin Core datastream
//$dcXML->children("dc", TRUE)->contributor[0] = $wholeName . ' (Author)';

// drush_print();
// drush_print('******EDITED Dublin Core********');
// drush_print($dcXML->asXML());
// drush_print();

# set content of datastreams to newly edited content
$modsDS->setContentFromString($mods);
//$dublinCoreDS->setContentFromString($dcXML->asXML());

# ingest datastream into repo
$object->ingestDatastream($modsDS);
//$object->ingestDatastream($dublinCoreDS);


// $dd = new DOMDocument;
// $dd->loadXML($dcXML->asXML());

// foreach ($dd->getElementsByTagNameNS('http://purl.org/dc/elements/1.1/', '*') as $element) {
//   echo $element->localName . ', ' . $element->nodeValue . "\n";
// }
