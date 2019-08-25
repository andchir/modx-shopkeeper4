<?php

/*
 * array $scriptProperties
 * @var modX $modx
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

    $shoppingCartObject = $shoppingCart->getShoppingCart($shoppingCart->getUserId(), $shoppingCart->getSessionId());
    if (empty($shoppingCartObject) || !count($shoppingCartObject->getMany('Content'))) {
        $hook->addError( 'error_message', $modx->lexicon('shopping_cart.order_empty') );
        return false;
    }
    $orderData = $modx->invokeEvent( ShoppingCart::EVENT_OnShoppingCartCheckoutSave, ['object' => $shoppingCartObject]);

    $shoppingCart->updateConfig([
        'rowTpl' => $modx->getOpton('shopping_cart.mail_order_data_outer_tpl', null, ''),
        'outerTpl' => $modx->getOpton('shopping_cart.mail_order_data_row_tpl', null, '')
    ]);
    $orderOutputData = $shoppingCart->renderOutput($orderData);

    //var_dump($formit->config); exit;
    // TODO: Create orderOutputData option for FromIt
    // $hook->setValues($orderOutputData);

    return true;
}

$shoppingCart->updateConfig($scriptProperties);
$output = $shoppingCart->actionResponse();

return $output;
