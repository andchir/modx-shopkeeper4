<?php

$shoppingCart = $modx->getService(
    'shopping_cart',
    'ShoppingCart',
    $modx->getOption('shopping_cart.core_path',null,$modx->getOption('core_path') . 'components/shopping_cart/') . 'model/shopping_cart/',
    $scriptProperties
);
if (!($shoppingCart instanceof ShoppingCart)) return '';

$output = $shoppingCart->actionResponse();

return $output;
