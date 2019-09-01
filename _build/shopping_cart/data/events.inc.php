<?php

$events = [];

$events[0] = $modx->newObject('modEvent');
$events[0]->fromArray([
    'name' => 'OnShoppingCartAddProduct',
    'service' => 7,
    'groupname' => 'ShoppingCart'
], '', true, true);

$events[1] = $modx->newObject('modEvent');
$events[1]->fromArray([
    'name' => 'OnShoppingCartCheckoutSave',
    'service' => 7,
    'groupname' => 'ShoppingCart'
], '', true, true);

return $events;
