<?php

$settings = [];

/*
$settings['shopkeeper4.installation_url'] = $modx->newObject('modSystemSetting');
$settings['shopkeeper4.installation_url']->fromArray([
    'key' => 'shopkeeper4.installation_url',
    'value' => '/shopkeeper4/',
    'xtype' => 'textfield',
    'namespace' => 'shopkeeper4',
    'area' => '',
    'editedon' => null,
], '', true, true);

$settings['shopkeeper4.mongodb_database'] = $modx->newObject('modSystemSetting');
$settings['shopkeeper4.mongodb_database']->fromArray([
    'key' => 'shopkeeper4.mongodb_database',
    'value' => '',
    'xtype' => 'textfield',
    'namespace' => 'shopkeeper4',
    'area' => '',
    'editedon' => null,
], '', true, true);

$settings['shopkeeper4.mongodb_url'] = $modx->newObject('modSystemSetting');
$settings['shopkeeper4.mongodb_url']->fromArray([
    'key' => 'shopkeeper4.mongodb_url',
    'value' => 'mongodb://dbuser:password@127.0.0.1:27017',
    'xtype' => 'textfield',
    'namespace' => 'shopkeeper4',
    'area' => '',
    'editedon' => null,
], '', true, true);
*/

$settings['shopkeeper4.catalog_default_order_by'] = $modx->newObject('modSystemSetting');
$settings['shopkeeper4.catalog_default_order_by']->fromArray([
    'key' => 'shopkeeper4.catalog_default_order_by',
    'value' => 'title_asc',
    'xtype' => 'textfield',
    'namespace' => 'shopkeeper4',
    'area' => '',
    'editedon' => null,
], '', true, true);

$settings['shopkeeper4.catalog_page_size'] = $modx->newObject('modSystemSetting');
$settings['shopkeeper4.catalog_page_size']->fromArray([
    'key' => 'shopkeeper4.catalog_page_size',
    'value' => '12,24,60',
    'xtype' => 'textfield',
    'namespace' => 'shopkeeper4',
    'area' => '',
    'editedon' => null,
], '', true, true);

$settings['shopkeeper4.debug'] = $modx->newObject('modSystemSetting');
$settings['shopkeeper4.debug']->fromArray([
    'key' => 'shopkeeper4.debug',
    'value' => '0',
    'xtype' => 'textfield',
    'namespace' => 'shopkeeper4',
    'area' => '',
    'editedon' => null,
], '', true, true);

$settings['shopkeeper4.locale_default'] = $modx->newObject('modSystemSetting');
$settings['shopkeeper4.locale_default']->fromArray([
    'key' => 'shopkeeper4.locale_default',
    'value' => 'ru',
    'xtype' => 'textfield',
    'namespace' => 'shopkeeper4',
    'area' => '',
    'editedon' => null,
], '', true, true);

$settings['shopkeeper4.product_image_default'] = $modx->newObject('modSystemSetting');
$settings['shopkeeper4.product_image_default']->fromArray([
    'key' => 'shopkeeper4.product_image_default',
    'value' => '/assets/components/shopkeeper4/img/nofoto.png',
    'xtype' => 'textfield',
    'namespace' => 'shopkeeper4',
    'area' => '',
    'editedon' => null,
], '', true, true);

return $settings;
