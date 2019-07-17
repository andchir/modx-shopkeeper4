<?php
/*
 * Events: OnHandleRequest, OnPageNotFound
 */

/** @var array $scriptProperties */

if($modx->context->get('key') == 'mgr') return '';

$catalogTemplateId = $modx->getOption('catalogTemplateId', $scriptProperties, 2);
$catalogRootTemplateId = $modx->getOption('catalogRootTemplateId', $scriptProperties, $catalogTemplateId);
$contentPageTemplateId = $modx->getOption('contentPageTemplateId', $scriptProperties, 3);

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

require_once $modx->getOption('core_path') . 'components/shopkeeper4/model/shopkeeper4/shopkeeper4.class.php';
$shopkeeper4 = new Shopkeeper4($modx, $properties);

$request_param_alias = $modx->getOption('request_param_alias',null,'q');
$request_param_id = $modx->getOption('request_param_id',null,'id');

switch($modx->event->name) {

    case 'OnHandleRequest':

        $uri = isset($_GET[$request_param_alias]) ? $_GET[$request_param_alias] : '';
        list($pageAlias, $categoryUri, $levelNum) = Shopkeeper4::parseUri($uri);
        $locale = 'ru';
        $isCategory = substr($uri, -1) === '/';

        $breadcrumbs = $shopkeeper4->getBreadcrumbs($categoryUri, false, $locale);
        $activeCategoriesIds = array_map(function($item) {
            return $item['_id'];
        }, $breadcrumbs);
        if ($isCategory) {
            array_pop($breadcrumbs);
        }

        $modx->setPlaceholder('shk4.dataBreadcrumbs', $breadcrumbs);
        $modx->setPlaceholder('shk4.activeCategoriesIds', $activeCategoriesIds);
        $modx->setPlaceholder('shk4.queryCount', 0);
        $modx->setPlaceholder('shk4.uri', $uri);
        $modx->setPlaceholder('shk4.categoryUri',  $categoryUri);

        $isAdmin = $modx->user && $modx->user->isAuthenticated('mgr') && $modx->user->hasSessionContext('mgr');
        $modx->setPlaceholder('shk4.isAdmin', $isAdmin ? 1 : 0);

        break;
    case 'OnPageNotFound':

        $uri = isset($_GET[$request_param_alias]) ? $_GET[$request_param_alias] : '';
        list($pageAlias, $categoryUri, $levelNum) = Shopkeeper4::parseUri($uri);
        $locale = 'ru';
        $isCategory = substr($uri, -1) === '/';

        $category = $shopkeeper4->getCategory($categoryUri);
        if (!$category || ($isCategory && !$category->isActive)) {
            return '';
        }
        if (!$category->parentId) {// Root category
            $catalogTemplateId = $catalogRootTemplateId;
        }

        $modx->setPlaceholder('shk4.category', $category);
        $contentType = $shopkeeper4->getContentType($category);
        if ($contentType) {
            $modx->setPlaceholder('shk4.contentType', $contentType);
        }
        $contentTypeFields = $contentType ? $contentType->fields : [];
        $catalogNavSettingsDefaults = [
            'pageSizeArr' => Shopkeeper4::stringToArray($modx->getOption('shopkeeper4.catalog_page_size', null, '12,24,60')),
            'orderBy' => $modx->getOption('shopkeeper4.catalog_default_order_by', null, 'title_asc')
        ];
        $queryOptions = Shopkeeper4::getQueryOptions($uri, $contentTypeFields, $catalogNavSettingsDefaults);
        $filtersQueryData = $queryOptions['filter'] ?? [];
        $modx->setPlaceholder('shk4.queryOptions', $queryOptions);
        $modx->setPlaceholder('shk4.filtersCount', count($filtersQueryData));

        if ($isCategory) {
            $pageData = $category ? json_decode(json_encode($category), true) : [];
        } else {
            $contentObject = $shopkeeper4->getContent($category->_id, $pageAlias, $contentType->collection);
            $pageData = $contentObject ? json_decode(json_encode($contentObject), true) : [];
        }
        $pageData['id'] = $pageData['_id'] ?? 0;
        if (!isset($pageData['pagetitle'])) {
            $pageData['pagetitle'] = $pageData['title'] ?? '';
        }

        $modx->resource = $modx->newObject('modResource');
        $modx->resource->fromArray($pageData);
        $modx->resource->set('template', $isCategory ? $catalogTemplateId : $contentPageTemplateId);
        $modx->resource->set('id', $pageData['id']);
        $modx->resource->set('cacheable', false);
        $modx->resource->set('class_key', 'modResource');
        $modx->resource->_content = '';
        $modx->resource->_output = '';
        $modx->resource->_isForward = true;

        $modx->resourceIdentifier = $modx->resource->get('id');
        $modx->resourceMethod = 'id';

        if ($properties['debug']) {
            $modx->setPlaceholder('shk4.queryCount', $shopkeeper4->getMongoQueryCount());
        }

        $modx->request->prepareResponse();

        break;
}

return '';