#!/usr/bin/env drush

#<?php

# include all php files necessary for Tuque
foreach ( glob("/var/www/drupal/htdocs/sites/all/libraries/tuque/*.php") as $filename) {
	require_once($filename);
}

$url = 'localhost:8080/fedora';
$username = 'fedoraAdmin';
$password = 'fedoraAdmin';

# set up connection and repository variables
$connection = new RepositoryConnection($url, $username, $password);
$api = new FedoraApi($connection);
$repository = new FedoraRepository($api, new SimpleCache());


# what is the pid you want?
$pid = 'islandora:3';

# try to fetch PID from repo
try {
	$object = $repository->getObject($pid);
}
catch (Exception $e) {
	drush_print('****************ERROR***************');
	drush_print('***Could not get object from repo***');
	exit;
}

$modsDS = $object['MODS'];
//$modsSimpleXML = simplexml_load_string($modsDC);
/*
echo $modsDS->content;

$modsXML = simplexml_load_string($modsDS->content);
$modsXML->name->namePart = 'Steve Buschemi';
$modsDS->setContentFromString($modsXML->asXML());


$object->ingestDatastream($modsDS);

echo $object['MODS']->content;
*/
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
//function xml_form_builder_update_dc_datastream(AbstractObject $object, $transform, DOMDocument $document) {

$document = new DOMDocument();
$modsDatastream = $object['MODS'];
$document->loadXML($modsDatastream->content);

$transform = 'mods_to_dc.xsl';

xml_form_builder_update_dc_datastream($object, $transform, $document);

/*
$transform_path = 'mods_to_dc.xsl';
$source_document = new DOMDocument;
$source_document->load 
simple_xml_load_string($object['MODS']->getContent())->asXML();
$xsl = new DOMDocument();
$xsl->load($transform_path);
$xslt = new XSLTProcessor();
$xslt->importStyleSheet($xsl);
$document = $xslt->transformToDoc($source_document);
echo $document->saveXML;
*/