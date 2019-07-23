<?php

$plugins = [];
/* plugin */
$plugins[0]= $modx->newObject('modPlugin');
$plugins[0]->fromArray([
    'id' => 1,
    'name' => 'shopkeeper4',
    'description' => 'Shopkeeper4 plugin',
    'plugincode' => getSnippetContent($sources['source_core'].'elements/plugins/plugin.shopkeeper4.php'),
    'static' => 0,
    'source' => 1
], '', true, true);
$events = [];

$events['OnHandleRequest'] = $modx->newObject('modPluginEvent');
$events['OnHandleRequest']->fromArray([
    'event' => 'OnHandleRequest',
    'priority' => 1,
    'propertyset' => 0,
], '', true, true);

$events['OnPageNotFound'] = $modx->newObject('modPluginEvent');
$events['OnPageNotFound']->fromArray([
    'event' => 'OnPageNotFound',
    'priority' => 2,
    'propertyset' => 0,
], '', true, true);

$plugins[0]->addMany($events);

$properties = [];

$plugins[0]->setProperties($properties);
unset($events,$properties);

foreach ($plugins as $plugin) {
    $category->addMany($plugin);
}

return $plugins;
