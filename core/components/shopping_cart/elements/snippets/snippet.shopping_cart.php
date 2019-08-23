<?php

/*
 * array $scriptProperties
 * @var FormIt $formit
 * @var fiHooks $hook
 */

$shoppingCart = $modx->getService(
    'shopping_cart',
    'ShoppingCart',
    $modx->getOption('shopping_cart.core_path',null,$modx->getOption('core_path') . 'components/shopping_cart/') . 'model/shopping_cart/',
    $scriptProperties
);
if (!($shoppingCart instanceof ShoppingCart)) return '';


if (isset($formit) && $formit instanceof FormIt) {

    //var_dump($formit->config); exit;
    // TODO: Create orderOutputData option for FromIt
    // $hook->setValues($orderOutputData);

    return true;
}

$shoppingCart->updateConfig($scriptProperties);
$output = $shoppingCart->actionResponse();

return $output;
