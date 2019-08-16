<?php

/*
 * Event: OnShoppingCartAddProduct
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



$modx->event->output($output);

return '';