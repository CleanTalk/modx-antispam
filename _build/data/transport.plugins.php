<?php
$plugins = array();
/* create the plugin objects */

// lighten
$plugins[0] = $modx->newObject('modPlugin');
$plugins[0]->set('id',1);
$plugins[0]->set('name','cleantalkCheck');
$plugins[0]->set('description','Calls the Cleantalk API to check content for spam. Requires Cleantalk API key.');
$plugins[0]->set('plugincode', getSnippetContent($sources['plugins'] . 'cleantalkCheck.plugin.php'));

$events = array();
$events['OnLoadWebDocument']= $modx->newObject('modPluginEvent');
$events['OnLoadWebDocument']->fromArray(array(
	'event' => 'OnLoadWebDocument',
	'priority' => 0,
	'propertyset' => 0,
),'',true,true);
if (is_array($events) && !empty($events)) {
	$plugins[0]->addMany($events);
	$modx->log(xPDO::LOG_LEVEL_INFO,'Packaged in '.count($events).' Plugin Events for Cleantalk.'); flush();
} else {
	$modx->log(xPDO::LOG_LEVEL_ERROR,'Could not find plugin events for Cleantalk!');
}
unset($events);

return $plugins;
