<?php

/**
 * Shopping Cart frontend connector
 *
 * @package shopping_cart
 */

require dirname(dirname(dirname(dirname(__FILE__)))) . "/config.core.php";
if(!defined('MODX_CORE_PATH')) require_once '../../../config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
$modx = new modX();
$modx->initialize( 'web' );
$modx->invokeEvent("OnLoadWebDocument");

if ($modx->getOption('shopping_cart.debug', null, false)) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

$manager_language = $modx->config['manager_language'];
$charset = $modx->config['modx_charset'];
header('Content-Type: text/html; charset={$charset}');

$propertySetName = $_POST['propertySetName'] ?? 'default';

require_once MODX_CORE_PATH . 'components/shopping_cart/model/shopping_cart/shoppingcart.class.php';

$snippet = $modx->getObject('modSnippet', ['name' => 'shoppingCart']);
$properties = $snippet ? $snippet->getProperties() : [];
if ($snippet && $propertySetName != 'default') {
    $propSet = $modx->getObject('modPropertySet', ['name' => $propertySetName]);
    $propSetProperties = $propSet ? $propSet->getProperties() : [];
    $properties = array_merge($properties, $propSetProperties);
}

$shopCart = new ShoppingCart($modx, $properties);
$response = $shopCart->actionResponse(true);

if (!$response || !$response['result'] || $shopCart->getIsError()) {
    http_response_code(422);
    $output = [
        'success' => false,
        'message' => $shopCart->getErrorMessage()
    ];
} else {

    $shoppingCart = $shopCart->getShoppingCart($shopCart->getUserId(), $shopCart->getSessionId());
    $shoppingCartContent = $shoppingCart ? $shoppingCart->getMany('Content') : [];
    $output = [
        'success' => true,
        'price_total' => $shopCart->getTotalPrice($shoppingCartContent),
        'items_total' => $shopCart->getTotalCount($shoppingCartContent),
        'items_unique_total' => count($shoppingCartContent),
        'delivery_price' => 0,
        'delivery_name' => 0,
        'ids' => [],
        'html' => $shopCart->renderOutput()
    ];
}

echo json_encode($output);
