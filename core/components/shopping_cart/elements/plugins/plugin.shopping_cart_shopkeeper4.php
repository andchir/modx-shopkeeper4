<?php

/*
 * Events: OnShoppingCartAddProduct, OnShoppingCartCheckoutSave
 * */

/** @var array $scriptProperties */

if($modx->context->get('key') == 'mgr') return '';

$properties = [
    'mongodb_url' => $modx->getOption('shopkeeper4.mongodb_url'),
    'mongodb_database' => $modx->getOption('shopkeeper4.mongodb_database'),
    'debug' => $modx->getOption('shopkeeper4.debug'),
    'locale' => $modx->getOption('cultureKey'),
    'localeDefault' => $modx->getOption('shopkeeper4.locale_default')
];
if ($properties['debug']) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

$output = [];

$snippetClassPath = $modx->getOption('core_path') . 'components/shopkeeper4/model/shopkeeper4/shopkeeper4.class.php';
if (!class_exists('Shopkeeper4') && !file_exists($snippetClassPath)) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Class Shopkeeper4 not found.');
    $modx->event->output($output);
    return '';
}
require_once $snippetClassPath;
$shopkeeper4 = new Shopkeeper4($modx, $properties);

$eventName = $modx->event->name;
switch($eventName) {
    case 'OnShoppingCartAddProduct':

        if (empty($scriptProperties['data']) || empty($scriptProperties['data']['item_id'])) {
            $modx->log(modX::LOG_LEVEL_ERROR, 'Product data is empty.');
            $modx->event->output($output);
            return '';
        }

        $categoryId = $scriptProperties['data']['category_id'] ? (int) $scriptProperties['data']['category_id'] : 0;
        $itemId = $scriptProperties['data']['item_id'] ? (int) $scriptProperties['data']['item_id'] : 0;
        $itemCount = $scriptProperties['data']['count'] ? (float) $scriptProperties['data']['count'] : 1;

        // Find category
        $category = $shopkeeper4->getCategory('', $categoryId);
        if (empty($scriptProperties['data']) || empty($scriptProperties['data']['item_id'])) {
            if (!$shopkeeper4->getIsError()) {
                $modx->log(modX::LOG_LEVEL_ERROR, $shopkeeper4->getErrorMessage());
            } else {
                $modx->log(modX::LOG_LEVEL_ERROR, "Category with ID \"{$categoryId}\" not found.");
            }
            $modx->event->output($output);
            return '';
        }
        $contentType = $shopkeeper4->getContentType($category);

        // Find catalog item
        $contentObject = $shopkeeper4->getCatalogItem($category->_id, $contentType->collection, '', $itemId);

        if (!$contentObject) {
            if (!$shopkeeper4->getIsError()) {
                $modx->log(modX::LOG_LEVEL_ERROR, $shopkeeper4->getErrorMessage());
            } else {
                $modx->log(modX::LOG_LEVEL_ERROR, "Catalog item with ID \"{$itemId}\" not found.");
            }
            $modx->event->output($output);
            return '';
        }

        $priceFieldName = $shopkeeper4->getFieldByChunkName($contentType->fields, 'price', 'price');
        $aliasFieldName = $shopkeeper4->getSystemNameField($contentType->fields);
        $titleFieldName = $shopkeeper4->getFieldByChunkName($contentType->fields, 'header', 'title');;

        $output = [
            'id' => $contentObject['_id'],
            'title' => $contentObject[$titleFieldName] ?? '',
            'name' => $contentObject[$aliasFieldName],
            'price' => $contentObject[$priceFieldName] ?? 0,
            'count' => $itemCount,
            'uri' => $category->uri . ($contentObject[$aliasFieldName] ?? ''),
            'options' => []
        ];

        break;
    case 'OnShoppingCartCheckoutSave':

        require_once $modx->getOption('core_path') . 'components/shopping_cart/model/shopping_cart/shoppingcart.class.php';
        $shopCart = new ShoppingCart($modx);
        $shopCartSettings = $shopkeeper4->getSettings('SETTINGS_ORDER_STATUSES');

        if (!$shopCartSettings) {
            if ($shopkeeper4->getIsError()) {
                $modx->log(modX::LOG_LEVEL_ERROR, $shopkeeper4->getErrorMessage());
            }
            $modx->event->output($output);
            return '';
        }
        $orderCollection = $shopkeeper4->getCollection('order');

        if (empty($object)
            || !$orderCollection
            || !($shoppingCartContent = $object->getMany('Content'))) {
                if ($shopkeeper4->getIsError()) {
                    $modx->log(modX::LOG_LEVEL_ERROR, $shopkeeper4->getErrorMessage());
                }
                $modx->event->output($output);
                return '';
        }

        $user = $object->getMany('Owner');

        // Order data
        $order = [
            'email' => $_POST['email'] ?? '',
            'fullName' => $_POST['fullname'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'createdDate' => new MongoDB\BSON\UTCDateTime(),
            'updatedDate' => new MongoDB\BSON\UTCDateTime(),
            'status' => !empty($shopCartSettings) ? $shopCartSettings[0]->name : '',
            'deliveryName' => $_POST['delivery'] ?? '',
            'deliveryPrice' => 0,
            'paymentName' => $_POST['payment'] ?? '',
            'paymentValue' => '',
            'userId' => $user ? $user->get('id') : 0,
            'price' => $shopCart->getTotalPrice($shoppingCartContent),
            'currency' => $object->get('currency'),
            'options' => [],
            'content' => []
        ];

        // Contacts data
        foreach ($_POST as $key => $value) {
            if (in_array($key, ['address', 'state', 'zip', 'city', 'street', 'house', 'apartment'])) {
                $order['options'][] = [
                    'name' => $key,
                    'title' => $modx->lexicon('shopping_cart.' . $key),
                    'value' => $value
                ];
            }
        }

        // Purchased products
        foreach ($shoppingCartContent as $content) {
            $options = $content->get('options') ?: [];
            $order['content'][] = [
                '_id' => new MongoDB\BSON\ObjectID(),
                'id' => $content->get('item_id'),
                'title' => $content->get('title'),
                'price' => $content->get('price'),
                'count' => $content->get('count'),
                'contentTypeName' => $options['contentTypeName'] ?? '',
                'uri' => $content->get('uri'),
                'image' => $options['image'] ?? '',
                'parameters' => array_unique(['image' => '', 'contentTypeName' => ''], $options)
            ];
        }

        $order['_id'] = $shopkeeper4->getNextId('order');
        $result = $orderCollection->insertOne($order);

        $output = [
            'orderId' => $result->getInsertedCount() ? $result->getInsertedId() : 0
        ];

        break;
}

$modx->event->output($output);

return '';