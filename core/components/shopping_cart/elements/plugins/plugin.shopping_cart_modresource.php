<?php

/*
 * Events: OnShoppingCartAddProduct, OnShoppingCartCheckoutSave
 * */

/** @var array $scriptProperties */

if($modx->context->get('key') == 'mgr') return '';

$tvNamePrice = $modx->getOption('tvNamePrice', $scriptProperties, 'price');

$output = [];

$eventName = $modx->event->name;
switch($eventName) {
    case 'OnShoppingCartAddProduct':

        if (empty($scriptProperties['data']) || empty($scriptProperties['data']['item_id'])) {
            $modx->log(modX::LOG_LEVEL_ERROR, 'Product data is empty.');
            $modx->event->output($output);
            return '';
        }

        $itemId = $scriptProperties['data']['item_id'] ? (int) $scriptProperties['data']['item_id'] : 0;
        $itemCount = $scriptProperties['data']['count'] ? (float) $scriptProperties['data']['count'] : 1;

        /** @var modResource $contentObject */
        $contentObject = $modx->getObject('modResource', [
            'id' => $itemId,
            'published' => true
        ]);

        if (!$contentObject) {
            $modx->log(modX::LOG_LEVEL_ERROR, "Catalog item with ID \"{$itemId}\" not found.");
            $modx->event->output($output);
            return '';
        }

        $price = (float) $contentObject->getTVValue($tvNamePrice);

        $output = [
            'id' => $contentObject->get('id'),
            'title' => $contentObject->get('pagetitle'),
            'name' => $contentObject->get('alias'),
            'price' => $price,
            'count' => $itemCount,
            'uri' => $contentObject->get('uri'),
            'options' => []
        ];

        break;
    case 'OnShoppingCartCheckoutSave':



        break;
}

$modx->event->_output = '';// Clear current event output (bugfix)
$modx->event->output($output);

return '';
