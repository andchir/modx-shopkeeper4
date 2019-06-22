<?php
/*
 * Events: OnHandleRequest, OnPageNotFound
 */

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

if($modx->context->get('key') == 'mgr') return '';

$parentId = $modx->getOption('catalog_id', null, 2);

$properties = [
    'mongodb_url' => $modx->getOption('shopkeeper4.mongodb_url'),
    'mongodb_database' => $modx->getOption('shopkeeper4.mongodb_database'),
    'debug' => $modx->getOption('shopkeeper4.debug')
];

require_once $modx->getOption('core_path') . 'components/shopkeeper4/model/shopkeeper4/shopkeeper4.class.php';
$shopkeeper4 = new Shopkeeper4($modx, $properties);

$request_param_alias = $modx->getOption('request_param_alias',null,'q');
$request_param_id = $modx->getOption('request_param_id',null,'id');

switch($modx->event->name) {

    case 'OnHandleRequest':

        $uri = isset($_GET[$request_param_alias]) ? $_GET[$request_param_alias] : '';
        list($pageAlias, $categoryUri, $levelNum) = Shopkeeper4::parseUri($uri);
        $locale = 'ru';

        $breadcrumbs = $shopkeeper4->getBreadcrumbs($categoryUri, false, $locale);
        $activeCategoriesIds = array_map(function($item) {
            return $item['_id'];
        }, $breadcrumbs);
        array_pop($breadcrumbs);

        $modx->setPlaceholder('shk4.breadcrumbs', $breadcrumbs);
        $modx->setPlaceholder('shk4.activeCategoriesIds', $activeCategoriesIds);
        $modx->setPlaceholder('shk4.queryCount', 0);

        break;
    case 'OnPageNotFound':

        $resource = $modx->getObject('modResource', ['id' => $parentId]);
        if (!$resource) {
            return '';
        }
        $uri = isset($_GET[$request_param_alias]) ? $_GET[$request_param_alias] : '';
        list($pageAlias, $categoryUri, $levelNum) = Shopkeeper4::parseUri($uri);
        $locale = 'ru';
        $isCategory = substr($uri, -1) === '/';

        list($pageAlias, $categoryUri, $levelNum) = Shopkeeper4::parseUri($uri);

        $category = $shopkeeper4->getCategory($categoryUri);
        if (!$category || ($isCategory && !$category->isActive)) {
            return '';
        }

        $pageData = [
            'pagetitle' => $isCategory ? $category->title : ''
        ];

        $modx->resource = $modx->newObject('modResource');
        $modx->resource->fromArray(array_merge($resource->toArray(), $pageData));
        $modx->resource->set('id', $parentId);
        $modx->resource->set('cacheable', false);
        $modx->resource->set('class_key', 'modResource');
        $modx->resource->_content = '';
        $modx->resource->_output = '';
        $modx->resource->_isForward = true;

        $modx->resourceIdentifier = $modx->resource->get('id');
        $modx->resourceMethod = 'id';

        if ($shopkeeper4->getConfigValue('debug')) {
            $modx->setPlaceholder('shk4.queryCount', $shopkeeper4->getMongoQueryCount());
        }

        $modx->request->prepareResponse();

        break;
}

return '';