<?php
/** @var array $scriptProperties */

if (!empty($scriptProperties['debug'])) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

require_once $modx->getOption('core_path') . 'components/shopkeeper4/model/shopkeeper4/shopkeeper4.class.php';

$scriptProperties['mongodb_url'] = $modx->getOption('shopkeeper4.mongodb_url');
$scriptProperties['mongodb_database'] = $modx->getOption('shopkeeper4.mongodb_database');

$scriptProperties['locale'] = $modx->getOption('cultureKey');
$scriptProperties['localeDefault'] = $modx->getOption('shopkeeper4.locale_default', null, $scriptProperties['locale']);
$scriptProperties['toPlaceholder'] = Shopkeeper4::getOption('toPlaceholder', $scriptProperties);
$scriptProperties['action'] = Shopkeeper4::getOption('action', $scriptProperties);
$actions = $scriptProperties['action'] ? explode(',', $scriptProperties['action']) : [];
$actions = array_map('trim', $actions);
if (count($actions) > 1) {
    $scriptProperties['toPlaceholder'] = 'shk4.ACTION_NAME';
}

$shopkeeper4 = null;
$output = null;
foreach ($actions as $action) {
    
    $cacheOptions = [];
    $scriptProperties['action'] = $action;
    $toPlaceholder = Shopkeeper4::getOption('toPlaceholder', $scriptProperties);
    $toPlaceholder = str_replace('ACTION_NAME', $action, $toPlaceholder);

    $cacheKey = Shopkeeper4::getOption('cacheKey', $scriptProperties);
    if ($cacheKey) {
        $cacheOptions = [
            xPDO::OPT_CACHE_KEY => 'resource/shk4.' . $cacheKey,
            xPDO::OPT_CACHE_HANDLER =>  Shopkeeper4::getOption('cache_resource_handler', $scriptProperties),
            xPDO::OPT_CACHE_EXPIRES => Shopkeeper4::getOption('cache_expires', $scriptProperties)
        ];
        $cached = $modx->cacheManager->get('resource/shk4.' . $cacheKey, $cacheOptions);
        if(!empty($cached) && isset($cached['output'])){
            $output = $cached['output'];
        }
    }

    if (!$output) {
        if (!($shopkeeper4 instanceof Shopkeeper4)) {
            $shopkeeper4 = new Shopkeeper4($modx, $scriptProperties);
        }
        $shopkeeper4->updateConfig('action', $action);
        $output = $shopkeeper4->getOutput();
        // Cache output
        if ($cacheKey) {
            $cached = [
                'output' => $output
            ];
            $modx->cacheManager->set(
                'resource/shk4.' . $cacheKey,
                $cached,
                Shopkeeper4::getOption('cache_expires', $scriptProperties),
                $cacheOptions
            );
        }
    }

    if ($cacheKey) {
        // Processing before output
        switch ($action) {
            case 'categories':
                $activeCategoriesIds = $modx->getPlaceholder('shk4.activeCategoriesIds');
                $activeClassName = Shopkeeper4::getOption('activeClassName', $scriptProperties);
                if (!empty($activeCategoriesIds)) {
                    $output = str_replace(
                        array_map(function($id) {return "active{$id}-"; }, $activeCategoriesIds),
                        $activeClassName,
                        $output
                    );
                }
                break;
        }
    }
    if ($toPlaceholder) {
        $modx->setPlaceholder($toPlaceholder, $output);
        $output = '';
    }
}

if ($shopkeeper4 instanceof Shopkeeper4) {
    $modx->setPlaceholder('shk4.queryCount', $shopkeeper4->getMongoQueryCount());
}

return $output;