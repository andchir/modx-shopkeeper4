<?php

/*
 * Delete expired shopping cart data from database.
 *
 * Event: OnCacheUpdate
 * */

$basePath = $modx->getOption('shopping_cart.core_path', null, $modx->getOption('core_path') . 'components/shopping_cart/');
$modelPath = $basePath . 'model/';
$modx->addPackage('shopping_cart', $modelPath);

$count = 0;

$query = $modx->newQuery('ShoppingCartItem');
$query->where(array('expireson:!=' => null));
$query->andCondition(array('expireson:<' => strftime('%Y-%m-%d %H:%M:%S')));
$shoppingCartItems = $modx->getCollection('ShoppingCartItem', $query);

/** @var xPDOObject $shoppingCart */
foreach ($shoppingCartItems as $shoppingCart) {
    $shoppingCartContent = $shoppingCart->getMany('Content');
    /** @var xPDOObject $content */
    foreach ($shoppingCartContent as $content) {
        $content->remove();
    }
    $shoppingCart->remove();
    $count++;
}

return $count ? " ShoppingCart - Expired items removed: {$count}" : '';
