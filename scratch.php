#!/usr/bin/env drush

#<?php 

# include all Tuque php files
$tuquePath = libraries_get_path('tuque') . '/*.php';
foreach ( glob($tuquePath) as $filename) {
    require_once($filename);
}

# repository connection parameters
$url      = 'localhost:8080/fedora';
$username = 'fedoraAdmin';
$password = 'fedoraAdmin';

# set up connection and repository variables
$connection = new RepositoryConnection($url, $username, $password);
$api        = new FedoraApi($connection);
$repository = new FedoraRepository($api, new SimpleCache());

$pid = 'islandora:3';

try {
    $object = $repository->getObject($pid);
}
catch (Exception $e) {
    drush_print("\n\n**********#######  ERROR  #######*********");
    drush_print("***Could not get object $pid from repo***\n\n");
    return;
}

$modsDatastream = islandora_datastream_load('MODS', $object);
// equivalent
$modsDatastream = $object['MODS'];


$modsxml = $modsDatastream->content;
// equivalent
$modsxml = $modsDatastream->getContent();


drush_print("******OLD XML*******\n");
drush_print($modsxml);
drush_print('*****END OLD XML*******');

return;

$mods = simplexml_load_string($modsxml);
drush_print($mods->name->namePart);
$mods->name->namePart = "Test Name";


$modsEdit = $mods->asXML();
drush_print("\n\n******NEW XML*******\n");
drush_print($modsEdit);
drush_print("*****END NEW XML*******\n");

# ingest new datastream into the repo
$modsDatastream->setContentFromString($modsEdit);
$object->ingestDatastream($modsDatastream);

