<?php
$snippets = array();

$snippets[0] = $modx->newObject('modSnippet');
$snippets[0]->set('id',1);
$snippets[0]->set('name','cleantalkCheck');
$snippets[0]->set('description','Calls the Cleantalk API to check content for spam. Requires Cleantalk API key.');
$snippets[0]->set('snippet', getSnippetContent($sources['snippets'] . 'cleantalkCheck.snippet.php'));

return $snippets;