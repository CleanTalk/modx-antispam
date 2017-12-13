<?php
$chunks = array();

$chunks[0]= $modx->newObject('modChunk');
$chunks[0]->set('id',1);
$chunks[0]->set('name','cleantalkFormParams');
$chunks[0]->set('description','Additional parameters. Sending this parameters will improve filtration.');
$chunks[0]->set('snippet',getSnippetContent($sources['source_core'].'/elements/chunks/cleantalkFormParams.chunk.tpl'));


return $chunks;