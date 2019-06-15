<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

$scriptProperties['mongodb_url'] = $modx->getOption('shopkeeper4.mongodb_url');
$scriptProperties['mongodb_database'] = $modx->getOption('shopkeeper4.mongodb_database');
if (!isset($scriptProperties['cache_expires'])) {
    $scriptProperties['cache_expires'] = 0;
}
$cacheOptions = [];
if (!empty($scriptProperties['cacheKey'])) {
    $cacheOptions = [
        xPDO::OPT_CACHE_KEY => 'resource/shk4.' . $scriptProperties['cacheKey'],
        xPDO::OPT_CACHE_HANDLER =>  $modx->getOption('cache_resource_handler', null, 'xPDOFileCache'),
        xPDO::OPT_CACHE_EXPIRES => $scriptProperties['cache_expires']
    ];
    $cached = $modx->cacheManager->get('resource/shk4.' . $scriptProperties['cacheKey'], $cacheOptions);
    if(!empty($cached) && isset($cached['output'])){
        return $cached['output'];
    }
}

require_once $modx->getOption('core_path') . 'components/shopkeeper4/model/shopkeeper4/shopkeeper4.class.php';
$shopkeeper4 = new Shopkeeper4($modx, $scriptProperties);

$output = $shopkeeper4->getOutput();

// Cache output
if (!empty($scriptProperties['cacheKey'])) {
    $cached = [
        'output' => $output
    ];
    $modx->cacheManager->set(
        'resource/shk4.' . $scriptProperties['cacheKey'],
        $cached,
        $scriptProperties['cache_expires'],
        $cacheOptions
    );
}

return $output;