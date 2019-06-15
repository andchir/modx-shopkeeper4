<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$scriptProperties['mongodb_url'] = $modx->getOption('shopkeeper4.mongodb_url');
$scriptProperties['mongodb_database'] = $modx->getOption('shopkeeper4.mongodb_database');

$shopkeeper4 = $modx->getService(
    'shopkeeper4',
    'Shopkeeper4',
    $modx->getOption('shopkeeper4.core_path',null,$modx->getOption('core_path').'components/shopkeeper4/')
    . 'model/shopkeeper4/',
    $scriptProperties
);
if (!($shopkeeper4 instanceof Shopkeeper4)) return '';

return $shopkeeper4->getOutput();