<?php

/*
 * @var array $scriptProperties
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

// Save order data for email
if (isset($formit) && $formit instanceof FormIt) {

    $shoppingCart->updateConfig([
        'rowTpl' => $modx->getOption('shoppingCartMailRowTpl', $formit->config, 'shoppingCart_mailOrderRowTpl'),
        'outerTpl' => $modx->getOption('shoppingCartMailOuterTpl', $formit->config, 'shoppingCart_mailOrderOuterTpl'),
        'contentType' => $modx->getOption('shoppingCartMailContentType', $formit->config, 'shop')
    ]);
    $shoppingCartObject = $shoppingCart->getShoppingCart($shoppingCart->getUserId(), $shoppingCart->getSessionId());
    if (empty($shoppingCartObject) || !count($shoppingCartObject->getMany('Content'))) {
        $hook->addError('error_message', $modx->lexicon('shopping_cart.order_empty'));
        return false;
    }
    $orderData = $modx->invokeEvent( ShoppingCart::EVENT_OnShoppingCartCheckoutSave, ['object' => $shoppingCartObject]);
    if ($orderData) {
        $orderData = current($orderData);
    }
    if (empty($orderData)) {
        $orderData = [];
    }
    $orderOutputData = $shoppingCart->renderOutput($orderData);

    $hook->setValues([
        'orderOutputData' => $orderOutputData,
        'orderId' => $orderData['orderId'] ?? '',
        'orderDate' => '',
        'orderCurrency' => $shoppingCartObject->get('currency')
    ]);
    if ($modx->getOption('shoppingCartClean', $formit->config, true)) {
        $shoppingCart->clean();
    }

    return true;
}

$shoppingCart->updateConfig($scriptProperties);
$output = $shoppingCart->actionResponse();

return $output;
