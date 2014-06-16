#!/usr/bin/env drush

#<?php 

drush_print("Hello World");

drush_print();

require_once('/var/www/drupal/htdocs/sites/all/libraries/tuque/RepositoryConnection.php');
require_once('/var/www/drupal/htdocs/sites/all/libraries/tuque/FedoraApi.php');

module_load_include('inc', 'islandora', 'includes/tuque');
module_load_include('inc', 'islandora', 'includes/tuque_wrapper');
module_load_include('inc', 'islandora', 'includes/utilities');
module_load_include('inc', 'islandora', 'includes/derivatives');
module_load_include('inc', 'islandora', 'includes/regenerate_derivatives.form');
module_load_include('inc', 'islandora', 'includes/add_datastream.form');
module_load_include('inc', 'islandora', 'includes/datastream');
module_load_include('inc', 'islandora', 'includes/metadata');
module_load_include('inc', 'islandora', 'includes/solution_packs');
module_load_include('inc', 'islandora', 'includes/object_properties.form');

$connection = islandora_get_tuque_connection();
$connection->connection->username = 'fedoraAdmin';
$connection->connection->password = 'fedoraAdmin';

$api = new FedoraApi($connection);
$cache = new SimpleCache();
$repository = new FedoraRepository($api, $cache);

$pid = 'islandora:3';


$object = islandora_object_load($pid);
if ($object) {
	$repository = $object->repository;
}
else {
	drush_print('****************Error****************');
}

$modsDatastream = islandora_datastream_load('MODS', $object);
$modsxml = $modsDatastream->content;

drush_print('******OLD XML*******');
drush_print();
drush_print($modsxml);
drush_print('*****END OLD XML*******');
drush_print();


$mods = simplexml_load_string($modsxml);
drush_print($mods->name->namePart);
$mods->name->namePart = "Test Name";


$modsEdit = $mods->asXML();
drush_print();
drush_print('******NEW XML*******');
drush_print();
drush_print($modsEdit);
drush_print('*****END NEW XML*******');
drush_print();

# ingest new datastream into the repo
$modsDatastream->setContentFromString($modsEdit);
$object->ingestDatastream($modsDatastream);


$DC = $api->m->getDatastream($pid, 'DC');
print $DC;

//$newDC = new DublinCore($modsEdit);
//$ds = $object->constructDatastream('DC');
//$ds->content = $newDC->asXML();
//$object->ingestDatastream($ds);

$document = new DOMDocument();
$document->loadXml($modsEdit);

//drush_print(xml_form_builder_update_dc_datastream($object, $association['transform'], $document));

//$xmlfd = new XMLFormDefinition(0);
