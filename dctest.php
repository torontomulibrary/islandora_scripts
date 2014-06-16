#!/usr/bin/env drush

#<?php

# include all php files necessary for Tuque
foreach ( glob("/var/www/drupal/htdocs/sites/all/libraries/tuque/*.php") as $filename) {
	require_once($filename);
}

// load file
if (file_exists('/root/jpdcrecord.xml')) {
    $dcRecord = simplexml_load_file('/root/jpdcrecord.xml');
}
else {
    echo "\n**********ERROR LOADING FILE********\n";
    return;
}


$dcDOMDoc = new DOMDocument();
$dcDOMDoc->loadXML($dcRecord->asXML());
$dcXPath = new DOMXPath($dcDOMDoc);
$dcXPath->registerNameSpace('dc', 'http://purl.org/dc/elements/1.1/');

$xml_out = $dcDOMDoc->saveXML($dcDOMDoc->documentElement);
echo $xml_out . "\n\n";

foreach ($dcXPath->query('dc:contributor') as $node) {
    echo $node->nodeValue;
}


//xml_form_builder_transform_document($transform, $source_document)
xml_form_builder_transform_document($transform, $dcDOMDoc);

/**
 * Updates the DC datastream by applying the given transform.
 *
 * @param AbstractObject $object
 *   The object whose DC will be updated.
 * @param string $transform
 *   The transform to apply, as defined by the forms association.
 * @param DOMDocument $document
 *   The document to transform.
 */
//xml_form_builder_update_dc_datastream(AbstractObject $object, $transform, DOMDocument $document);
xml_form_builder_update_dc_datastream($object, $transform, $dcDOMDoc);