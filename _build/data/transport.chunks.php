<?php
$chunks = array();

$chunk = $modx->newObject('modChunk');

$chunk->set('id',1);
$chunk->set('name','cleantalkFormParams');
$chunk->set('description','Additional parameters. Sending this parameters will improve filtration.');
$chunk->setContent(file_get_contents($sources['source_core'].'/elements/chunks/cleantalkFormParams.chunk.tpl'));

$chunks[] = $chunk;
$_chunks = array(
    'cleantalkFormParams' => file_get_contents($sources['source_core'].'/elements/chunks/cleantalkFormParams.chunk.tpl')
);
return $chunks;