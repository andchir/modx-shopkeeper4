<?php

/*
 * Delete expired shopping cart data from database.
 *
 * Events: OnCacheUpdate, OnWebLogin
 *
 * */

$basePath = $modx->getOption('shopping_cart.core_path', null, $modx->getOption('core_path') . 'components/shopping_cart/');
$modelPath = $basePath . 'model/';
$modx->addPackage('shopping_cart', $modelPath);

$output = '';

$eventName = $modx->event->name;
switch($eventName) {
    case 'OnCacheUpdate':

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

        $output = $count ? " ShoppingCart - Expired items removed: {$count}" : '';

        break;
    case 'OnWebLogin':

        $user = $scriptProperties['user'] ?? null;
        if ($user) {

            require_once $modelPath . 'shopping_cart/shoppingcart.class.php';
            $shopCart = new ShoppingCart($modx);

            $shoppingCarts = $shopCart->getShoppingCartsBySession();
            foreach ($shoppingCarts as $shoppingCart) {
                $shoppingCart->addOne($user);
                $shoppingCart->save();
            }
        }

        break;
}

return $output;
