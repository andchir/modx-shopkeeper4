<?php

require_once dirname(__FILE__) . '/build.config.php';
include_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx= new modX();

$modx->initialize('mgr');
$modx->loadClass('transport.modPackageBuilder','',false, true);
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

$corePath = $modx->getOption('core_path') . 'components/shopping_cart/';
$sources = [
    'model' => $corePath . 'model/',
    'schema_file' => $corePath . 'model/schema/shopping_cart.mysql.schema.xml'
];

$manager= $modx->getManager();
$generator= $manager->getGenerator();

if (!is_dir($sources['model'])) {
    $modx->log(modX::LOG_LEVEL_ERROR,'Model directory not found!');
    die();
}
if (!file_exists($sources['schema_file'])) {
    $modx->log(modX::LOG_LEVEL_ERROR,'Schema file not found!');
    die();
}

$generator->parseSchema($sources['schema_file'], $sources['model']);
$modx->addPackage('shopping_cart', $sources['model']);
$manager->createObjectContainer('ShoppingCart');

$modx->log(modX::LOG_LEVEL_INFO, 'Done!');
