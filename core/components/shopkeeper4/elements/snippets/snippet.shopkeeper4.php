<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once $modx->getOption('core_path') . 'components/shopkeeper4/model/shopkeeper4/shopkeeper4.class.php';

$scriptProperties['mongodb_url'] = $modx->getOption('shopkeeper4.mongodb_url');
$scriptProperties['mongodb_database'] = $modx->getOption('shopkeeper4.mongodb_database');
$scriptProperties['action'] = $modx->getOption('action', $scriptProperties, 'products');
if (!isset($scriptProperties['cache_expires'])) {
    $scriptProperties['cache_expires'] = 0;
}
$cacheOptions = [];
$output = null;
if (!empty($scriptProperties['cacheKey'])) {
    $cacheOptions = [
        xPDO::OPT_CACHE_KEY => 'resource/shk4.' . $scriptProperties['cacheKey'],
        xPDO::OPT_CACHE_HANDLER =>  $modx->getOption('cache_resource_handler', null, 'xPDOFileCache'),
        xPDO::OPT_CACHE_EXPIRES => $scriptProperties['cache_expires']
    ];
    $cached = $modx->cacheManager->get('resource/shk4.' . $scriptProperties['cacheKey'], $cacheOptions);
    if(!empty($cached) && isset($cached['output'])){
        $output = $cached['output'];
    }
}

if (!$output) {
    switch ($scriptProperties['action']) {
        case 'render_placeholder_array':
            $placeholderName = $modx->getOption('placeholderName', $scriptProperties);
            $chunkName = $modx->getOption('tpl', $scriptProperties);
            if ($chunkName
                && $placeholderName
                && !empty($modx->placeholders[$placeholderName])) {

                $chunk = $modx->getObjectGraph('modChunk', ['Source' => []], ['name' => $chunkName]);
                $chunkContent = $chunk ? $chunk->getContent() : '';
                $output = Shopkeeper4::renderPlaceholderArray($modx->placeholders[$placeholderName], $chunkContent);
            }
            break;
        default:
            $shopkeeper4 = new Shopkeeper4($modx, $scriptProperties);
            $output = $shopkeeper4->getOutput();
    }
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
}

// Processing before output
switch ($scriptProperties['action']) {
    case 'categories':
        $activeCategoriesIds = $modx->getPlaceholder('shk4.activeCategoriesIds');
        $activeClassName = $modx->getOption('activeClassName', $scriptProperties, 'active');
        if (!empty($activeCategoriesIds)) {
            $output = str_replace(
                array_map(function($id) {return "active{$id}-"; }, $activeCategoriesIds),
                $activeClassName,
                $output
            );
        }
        break;
}

return $output;