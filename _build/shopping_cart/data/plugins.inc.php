<?php

$plugins = [];

// -------------------------------
// shoppingCart
// -------------------------------
$plugins[0]= $modx->newObject('modPlugin');
$plugins[0]->fromArray([
    'id' => 1,
    'name' => 'shoppingCart',
    'description' => 'shoppingCart plugin',
    'plugincode' => getSnippetContent($sources['source_core'].'elements/plugins/plugin.shopping_cart.php'),
    'static' => 0,
    'source' => 1
], '', true, true);

$events = [];
$events['OnCacheUpdate'] = $modx->newObject('modPluginEvent');
$events['OnCacheUpdate']->fromArray([
    'event' => 'OnCacheUpdate',
    'priority' => 1,
    'propertyset' => 0,
], '', true, true);

$events['OnWebLogin'] = $modx->newObject('modPluginEvent');
$events['OnWebLogin']->fromArray([
    'event' => 'OnWebLogin',
    'priority' => 2,
    'propertyset' => 0,
], '', true, true);

$plugins[0]->addMany($events);
$properties = [];
$plugins[0]->setProperties($properties);

// -------------------------------
// shoppingCartModResource
// -------------------------------
$plugins[1]= $modx->newObject('modPlugin');
$plugins[1]->fromArray([
    'id' => 1,
    'name' => 'shoppingCartModResource',
    'description' => 'Add products to shopping cart from modResource.',
    'plugincode' => getSnippetContent($sources['source_core'].'elements/plugins/plugin.shopping_cart_modresource.php'),
    'static' => 0,
    'source' => 1,
    'disabled' => 1
], '', true, true);

$events = [];
$events['OnShoppingCartAddProduct'] = $modx->newObject('modPluginEvent');
$events['OnShoppingCartAddProduct']->fromArray([
    'event' => 'OnShoppingCartAddProduct',
    'priority' => 2,
    'propertyset' => 0,
], '', true, true);

$events['OnShoppingCartCheckoutSave'] = $modx->newObject('modPluginEvent');
$events['OnShoppingCartCheckoutSave']->fromArray([
    'event' => 'OnShoppingCartCheckoutSave',
    'priority' => 2,
    'propertyset' => 0,
], '', true, true);

$plugins[1]->addMany($events);
$properties = [
    'tvNamePrice' => 'price'
];
$plugins[1]->setProperties($properties);

// -------------------------------
// shoppingCartShopkeeper4
// -------------------------------
$plugins[2]= $modx->newObject('modPlugin');
$plugins[2]->fromArray([
    'id' => 1,
    'name' => 'shoppingCartShopkeeper4',
    'description' => 'Add products to shopping cart from Shopkeeper4 MongoDB database.',
    'plugincode' => getSnippetContent($sources['source_core'].'elements/plugins/plugin.shopping_cart_shopkeeper4.php'),
    'static' => 0,
    'source' => 1,
    'disabled' => 1
], '', true, true);

$events = [];
$events['OnShoppingCartAddProduct'] = $modx->newObject('modPluginEvent');
$events['OnShoppingCartAddProduct']->fromArray([
    'event' => 'OnShoppingCartAddProduct',
    'priority' => 2,
    'propertyset' => 0,
], '', true, true);

$events['OnShoppingCartCheckoutSave'] = $modx->newObject('modPluginEvent');
$events['OnShoppingCartCheckoutSave']->fromArray([
    'event' => 'OnShoppingCartCheckoutSave',
    'priority' => 2,
    'propertyset' => 0,
], '', true, true);

$plugins[2]->addMany($events);
$properties = [];
$plugins[2]->setProperties($properties);

foreach ($plugins as $plugin) {
    $category->addMany($plugin);
}

return $plugins;
