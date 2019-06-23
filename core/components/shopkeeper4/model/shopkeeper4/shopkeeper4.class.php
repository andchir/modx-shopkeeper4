<?php

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/Connection/MongoDBConnection.php';

class Shopkeeper4 {

    public $modx;
    private $mongodbConnection;
    private $errorMessage = null;
    private $isError = false;
    public $config = [];

    public function __construct(modX &$modx, array $config = []) {
        $this->modx =& $modx;
        $basePath = $this->modx->getOption('shopkeeper4.core_path', $config,$this->modx->getOption('core_path') . 'components/shopkeeper4/');
        $assetsUrl = $this->modx->getOption('shopkeeper4.assets_url', $config,$this->modx->getOption('assets_url') . 'components/shopkeeper4/');
        $this->config = array_merge([
            'basePath' => $basePath,
            'corePath' => $basePath,
            'modelPath' => $basePath . 'model/',
            'processorsPath' => $basePath . 'processors/',
            'templatesPath' => $basePath . 'templates/',
            'chunksPath' => $basePath . 'elements/chunks/',
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'assetsUrl' => $assetsUrl,
            'connectorUrl' => $assetsUrl . 'connector.php'
        ], $config);

        $this->mongodbConnection = \Andchir\Shopkeeper4\Connection\MongoDBConnection::getInstance($this->config['mongodb_url']);
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function updateConfig($key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     * @param string $errorMessage
     * @param int $line
     */
    public function setErrorMessage($errorMessage, $line = 0)
    {
        $this->isError = true;
        $this->errorMessage = $errorMessage;
        $this->modx->log(modX::LOG_LEVEL_ERROR, $errorMessage . ($line ? " LINE: {$line}" : ''));
    }

    /**
     * @return string|null
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @return bool
     */
    public function getIsError()
    {
        return $this->isError;
    }

    /**
     * @param string $collectionName
     * @return \MongoDB\Collection
     */
    public function getCollection($collectionName)
    {
        try {
            $dbs = $this->mongodbConnection->getClient()->listDatabases();
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage(), __LINE__);
            return null;
        }
        try {
            $collection = $this->mongodbConnection->getClient()->selectCollection($this->config['mongodb_database'], $collectionName);
            return $collection;
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage(), __LINE__);
            return null;
        }
    }

    /**
     * @param string $categoryUri
     * @return array|null|object
     */
    public function getCategory($categoryUri)
    {
        $categoryCollection = $this->getCollection('category');
        if (!$categoryCollection) {
            return null;
        }
        $this->mongodbConnection->queryCountIncrement();
        return $categoryCollection->findOne([
            'uri' => $categoryUri
        ]);
    }

    /**
     * @param $category
     * @return null|object
     */
    public function getContentType($category)
    {
        if (!$category) {
            return null;
        }
        $contentTypeCollection = $this->getCollection('content_type');
        if (!$contentTypeCollection) {
            return null;
        }
        $contentTypeName = $category->contentTypeName;
        $this->mongodbConnection->queryCountIncrement();
        return $contentTypeCollection->findOne([
            'name' => $contentTypeName
        ]);
    }

    /**
     * @param string $action
     * @return string
     */
    public function getOutput($action = '')
    {
        if (!$action) {
            $action = self::getOption('action', $this->config);
        }
        $output = '';
        switch ($action) {
            case 'render_placeholder_array':

                $placeholderName = self::getOption('placeholderName', $this->config);
                $chunkName = self::getOption('tpl', $this->config);
                if ($chunkName
                    && $placeholderName
                    && !empty($this->modx->placeholders[$placeholderName])) {

                    $chunk = $this->modx->getObjectGraph('modChunk', ['Source' => []], ['name' => $chunkName]);
                    $chunkContent = $chunk ? $chunk->getContent() : '';
                    $output = Shopkeeper4::renderPlaceholderArray($this->modx->placeholders[$placeholderName], $chunkContent);
                }

                break;
            case 'categories':
                $output = $this->renderCategories();
                break;
            case 'products':
                $output = $this->renderProducts();
                break;
        }
        if (self::getOption('debug', $this->config)) {
            if ($this->getIsError()) {
                return sprintf('<div style="padding:5px 10px; background-color:#f4c8b3; color:#a72323;">ERROR: %s</div>', $this->getErrorMessage());
            }
            $this->modx->setPlaceholder('shk4.queryCount', $this->getMongoQueryCount());
        }
        return $output;
    }

    /**
     * @return string
     */
    public function renderProducts()
    {
        $products = $this->getListProducts();
        $output = '';

        foreach ($products as $product){
            $properties = is_object($product)
                ? iterator_to_array($product)
                : $product;
            $properties['id'] = $properties['_id'];
            $output .= $this->modx->getChunk(self::getOption('rowTpl', $this->config), $properties);
        }

        return $output;
    }

    /**
     * @return string
     */
    public function renderCategories()
    {
        $categories = $this->getListCategories();
        $output = '';
        foreach ($categories as $category) {
            $properties = is_object($category)
                ? iterator_to_array($category)
                : $category;
            $properties['id'] = $properties['_id'];
            $output .= $this->modx->getChunk(self::getOption('rowTpl', $this->config), $properties);
        }
        unset($properties);
        if ($output && self::getOption('outerTpl', $this->config)) {
            $properties = [
                'wrapper' => $output
            ];
            $output = $this->modx->getChunk(self::getOption('outerTpl', $this->config), $properties);
        }
        return $output;
    }

    /**
     * @return array
     */
    public function getListProducts()
    {
        $contentType = $this->modx->getPlaceholder('shk4.contentType');
        $uri = $this->modx->getPlaceholder('shk4.uri');
        if (!$contentType || !is_array($contentType)) {
            $this->setErrorMessage('Content type on found.');
            return [];
        }
        $productsCollection = $this->getCollection($contentType['collection']);
        if (!$productsCollection) {
            return [];
        }
        $currentCategory = $this->modx->getPlaceholder('shk4.category');
        $contentTypeFields = [];
        $aggregateFields = $this->getAggregationFields(
            self::getOption('locale', $this->config),
            self::getOption('localeDefault', $this->config),
            true
        );
        $criteria = [
            'isActive' => true
        ];
        $this->applyCategoryFilter($currentCategory, $contentTypeFields, $criteria);
        $total = $productsCollection->countDocuments($criteria);

        $queryOptions = self::getQueryOptions($uri, $contentTypeFields);
        $pagesOptions = self::getPagesOptions($queryOptions, $total);

        $pipeline = $this->createAggregatePipeline(
            $criteria,
            $aggregateFields,
            $queryOptions['limit'],
            $queryOptions['sortOptionsAggregation'],
            $pagesOptions['skip']
        );

        return $productsCollection->aggregate($pipeline, [
            'cursor' => []
        ])->toArray();
    }

    /**
     * @param string $locale
     * @param string $localeDefault
     * @param bool $addFieldsOnly
     * @return array
     */
    public function getAggregationFields($locale, $localeDefault, $addFieldsOnly = false)
    {
        $contentType = $this->modx->getPlaceholder('shk4.contentType');
        if (!$contentType || !is_array($contentType)) {
            $this->setErrorMessage('Content type on found.');
            return [];
        }
        $aggregateFields = [];
        if ($locale === $localeDefault && $addFieldsOnly) {
            return [];
        }
        foreach ($contentType['fields'] as $contentTypeField) {
            if ($locale !== $localeDefault
                && in_array($contentTypeField['inputType'], ['text', 'textarea', 'rich_text'])) {
                $aggregateFields[$contentTypeField['name']] = "\$translations.{$contentTypeField['name']}.{$locale}";
            } else if (!$addFieldsOnly) {
                $aggregateFields[$contentTypeField['name']] = 1;
            }
        }
        if (!$addFieldsOnly) {
            $aggregateFields['parentId'] = 1;
        }
        return $aggregateFields;
    }

    /**
     * @param array $currentCategory
     * @param array $contentTypeFields
     * @param array $criteria
     */
    public function applyCategoryFilter($currentCategory, $contentTypeFields, &$criteria)
    {
        $categoriesField = array_filter($contentTypeFields, function($field){
            return $field['inputType'] == 'categories';
        });
        $categoriesField = current($categoriesField);

        if (!empty($categoriesField)) {
            $orCriteria = [
                '$or' => [
                    ['parentId' => $currentCategory['_id']]
                ]
            ];
            $orCriteria['$or'][] = ["{$categoriesField['name']}" => [
                '$elemMatch' => ['$in' => [$currentCategory['_id']]]
            ]];
            $criteria = ['$and' => [$criteria, $orCriteria]];

        } else {
            $criteria['parentId'] = $currentCategory['_id'];
        }
    }

    /**
     * @param $criteria
     * @param $aggregateFields
     * @param $limit
     * @param $sort
     * @param $skip
     * @return array
     */
    public function createAggregatePipeline($criteria, $aggregateFields, $limit = 1, $sort = [], $skip = 0)
    {
        $pipeline = [['$match' => $criteria]];
        if (!empty($aggregateFields)) {
            $pipeline[] = ['$addFields' => $aggregateFields];
        }
        if (!empty($sort)) {
            $pipeline[] = ['$sort' => $sort];
        }
        if (!empty($skip)) {
            $pipeline[] = ['$skip' => $skip];
        }
        if (!empty($limit)) {
            $pipeline[] = ['$limit' => $limit];
        }
        return $pipeline;
    }

    /**
     * @param bool $saveToPlaceholders
     * @return array
     */
    public function getListCategories($saveToPlaceholders = true)
    {
        $parentId = (int) self::getOption('parent', $this->config);
        if (isset($this->modx->placeholders["shk4.categories{$parentId}"])) {
            return $this->modx->placeholders["shk4.categories{$parentId}"];
        } else {
            $categoryCollection = $this->getCollection('category');
            if (!$categoryCollection) {
                return [];
            }
            $where = [
                'isActive' => true,
                'name' => [
                    '$ne' => 'root'
                ]
            ];
            if (!$saveToPlaceholders) {
                $where['parentId'] = $parentId;
            }
            try {
                $categories = $categoryCollection->find($where, [
                    'sort' => ['menuIndex' => 1, '_id' => 1]
                ])->toArray();
            } catch (\Exception $e) {
                $this->setErrorMessage($e->getMessage(), __LINE__);
                return [];
            }
            $this->mongodbConnection->queryCountIncrement();
            if ($saveToPlaceholders) {
                $data = [];
                foreach ($categories as $category) {
                    if (!isset($data[$category['parentId']])) {
                        $data[$category['parentId']] = [];
                    }
                    $data[$category->parentId][] = iterator_to_array($category);
                    if (!$category->isFolder) {
                        $this->modx->setPlaceholder("shk4.categories{$category->_id}", []);
                    }
                }
                foreach ($data as $key => $val) {
                    $this->modx->setPlaceholder("shk4.categories{$key}", $val);
                }
                return $data[$parentId] ?? [];
            }
            return $categories;
        }
    }

    /**
     * @param string $categoryUri
     * @param bool $pop
     * @param string $locale
     * @return array
     */
    public function getBreadcrumbs($categoryUri, $pop = true, $locale = '')
    {
        if (empty($categoryUri)) {
            return [];
        }
        $categoryCollection = $this->getCollection('category');
        if (!$categoryCollection) {
            return [];
        }
        $breadcrumbs = [];
        $categoryUri = trim($categoryUri, '/');
        $categoryUriArr = explode('/', $categoryUri);

        $categories = $categoryCollection->find([
            'name' => ['$in' => $categoryUriArr, '$ne' => 'root'],
            'isActive' => true
        ], [
            'sort' => ['title' => 1]
        ])->toArray();
        $this->mongodbConnection->queryCountIncrement();

        $crumb = self::findOneFromArray($categories, 'parentId', 0);
        while (!empty($crumb)) {
            $breadcrumbs[] = $crumb;
            $crumb = self::findOneFromArray($categories, 'parentId', $crumb['_id']);
        }
        if (!empty($breadcrumbs) && $pop) {
            array_pop($breadcrumbs);
        }

        return $breadcrumbs;
    }

    /**
     * @return int
     */
    public function getMongoQueryCount()
    {
        return $this->mongodbConnection->getQueryCount();
    }

    /**
     * @param array $items
     * @param string $key
     * @param string $value
     * @return null | mixed
     */
    public static function findOneFromArray($items, $key, $value)
    {
        $index = array_search($value, array_column($items, $key));
        return $index !== false ? $items[$index] : null;
    }

    /**
     * @param string $currentUri
     * @param array $contentTypeFields
     * @param array $catalogNavSettingsDefaults
     * @param array $options
     * @return array
     * @internal param array $pageSizeArr
     */
    public static function getQueryOptions($currentUri, $contentTypeFields = [], $catalogNavSettingsDefaults = [], $options = [])
    {
        $queryString = $queryString = self::normalizeQueryString($_SERVER['QUERY_STRING']);
        $queryOptionsDefault = [
            'uri' => '',
            'page' => 1,
            'limit' => isset($catalogNavSettingsDefaults['pageSizeArr'])
                ? $catalogNavSettingsDefaults['pageSizeArr'][0]
                : 12,
            'limit_max' => 100,
            'sort_by' => 'id',
            'sort_dir' => 'desc',
            'order_by' => isset($catalogNavSettingsDefaults['orderBy'])
                ? $catalogNavSettingsDefaults['orderBy']
                : 'id_desc',
            'full' => 1,
            'only_active' => 1,
            'filter' => [],
            'filterStr' => ''
        ];

        parse_str($queryString, $queryOptions);
        unset($queryOptions['q']);

        if (!empty($options['pageVar'])) {
            $queryOptions['page'] = isset($queryOptions[$options['pageVar']])
                ? $queryOptions[$options['pageVar']]
                : $queryOptionsDefault['page'];
        }
        if (!empty($options['limitVar'])) {
            $queryOptions['limit'] = isset($queryOptions[$options['limitVar']])
                ? $queryOptions[$options['limitVar']]
                : $queryOptionsDefault['limit'];
        }

        $queryOptions['uri'] = $currentUri;

        // Sorting
        if (empty($queryOptions['order_by'])) {
            $queryOptions['order_by'] = $queryOptionsDefault['order_by'];
        }
        if (empty($queryOptions['sort_by']) && strpos($queryOptions['order_by'], '_') !== false) {
            $pos = strrpos($queryOptions['order_by'], '_');
            $queryOptions['sort_by'] = substr($queryOptions['order_by'], 0, $pos);
            $queryOptions['sort_dir'] = substr($queryOptions['order_by'], $pos + 1);
        }

        $queryOptions = array_merge($queryOptionsDefault, $queryOptions);

        //Field names array
        $fieldNames = [];
        if (!empty($contentTypeFields)) {
            $fieldNames = array_column($contentTypeFields, 'name');
            $fieldNames[] = '_id';
        }

        if($queryOptions['sort_by'] == 'id'){
            $queryOptions['sort_by'] = '_id';
        }

        $queryOptions['sort_by'] = self::stringToArray($queryOptions['sort_by']);
        if (!empty($fieldNames)) {
            $queryOptions['sort_by'] = self::arrayFilter($queryOptions['sort_by'], $fieldNames);
        }
        $queryOptions['sort_dir'] = self::stringToArray($queryOptions['sort_dir']);
        $queryOptions['sort_dir'] = self::arrayFilter($queryOptions['sort_dir'], ['asc', 'desc']);

        // Sorting options
        $queryOptions['sortOptions'] = [];
        $queryOptions['sortOptionsAggregation'] = [];
        foreach ($queryOptions['sort_by'] as $ind => $sortByName) {
            $queryOptions['sortOptions'][$sortByName] = isset($queryOptions['sort_dir'][$ind])
                ? $queryOptions['sort_dir'][$ind]
                : $queryOptions['sort_dir'][0];
            $queryOptions['sortOptionsAggregation'][$sortByName] = $queryOptions['sortOptions'][$sortByName] == 'asc' ? 1 : -1;
        }

        if(!is_numeric($queryOptions['limit'])){
            $queryOptions['limit'] = $queryOptionsDefault['limit'];
        }
        if(!is_numeric($queryOptions['page'])){
            $queryOptions['page'] = $queryOptionsDefault['page'];
        }
        $queryOptions['limit'] = min(abs(intval($queryOptions['limit'])), $queryOptions['limit_max']);
        $queryOptions['page'] = abs(intval($queryOptions['page']));

        if (!empty($queryOptions['filter']) && is_array($queryOptions['filter'])) {
            $queryOptions['filterStr'] = '&' . http_build_query(['filter' => $queryOptions['filter']]);
        }
        if (!empty($queryOptions['query']) && !is_array($queryOptions['query'])) {
            $queryOptions['filterStr'] = '&' . http_build_query(['query' => $queryOptions['query']]);
        }

        return $queryOptions;
    }

    /**
     * @param array $queryOptions
     * @param int $itemsTotal
     * @param array $catalogNavSettingsDefaults
     * @param array $options
     * @return array
     * @internal param array $pageSizeArr
     */
    public static function getPagesOptions($queryOptions, $itemsTotal, $catalogNavSettingsDefaults = [], $options = [])
    {
        $pagesOptions = [
            'pageSizeArr' => isset($catalogNavSettingsDefaults['pageSizeArr'])
                ? $catalogNavSettingsDefaults['pageSizeArr']
                : [12],
            'current' => $queryOptions['page'],
            'limit' => $queryOptions['limit'],
            'total' => ceil($itemsTotal / $queryOptions['limit']),
            'prev' => max(1, $queryOptions['page'] - 1),
            'skip' => ($queryOptions['page'] - 1) * $queryOptions['limit'],
            'pageVar' => isset($options['pageVar']) ? $options['pageVar'] : 'page',
            'limitVar' => isset($options['limitVar']) ? $options['limitVar'] : 'limit',
            'orderByVar' => isset($options['orderByVar']) ? $options['orderByVar'] : 'order_by'
        ];
        $pagesOptions['next'] = min($pagesOptions['total'], $queryOptions['page'] + 1);

        return $pagesOptions;
    }

    /**
     * @param $string
     * @return array
     */
    public static function stringToArray($string)
    {
        $output = $string ? explode(',', $string) : [];
        return array_map('trim', $output);
    }

    /**
     * @param array $inputArr
     * @param array $targetArr
     * @return array
     */
    public static function arrayFilter($inputArr, $targetArr)
    {
        return array_filter($inputArr, function($val) use ($targetArr) {
            return in_array($val, $targetArr);
        });
    }

    /**
     * @param string $optionName
     * @param array $properties
     * @param mixed $defaultValue
     * @return mixed|null
     */
    public static function getOption($optionName, $properties = [], $defaultValue = null)
    {
        $optionsDefault = [
            'action' => 'products',
            'debug' => false,
            'locale' => 'en',
            'localeDefault' => 'en',
            'mongodb_url' => 'mongodb://localhost:27017',
            'mongodb_database' => 'default',
            'parent' => '0',
            'rowTpl' => 'shk4_menuRowTpl',
            'outerTpl' => 'shk4_menuOuterTpl',
            'activeClassName' => 'active',
            'cacheKey' => '',
            'toPlaceholder' => '',
            'cache_resource_handler' => 'xPDOFileCache',
            'cache_expires' => 0
        ];
        if (!is_array($properties)) {
            $properties = [];
        }
        if (!empty($properties['action']) && isset($properties[$properties['action'] . '_' . $optionName])) {
            return $properties[$properties['action'] . '_' . $optionName];
        }
        if (isset($properties[$optionName])) {
            return $properties[$optionName];
        }
        return $optionsDefault[$optionName] ?? $defaultValue;
    }

    /**
     * @param array $inputArray
     * @param string $chunkContent
     * @param string $prefix
     * @param string $suffix
     * @return string
     */
    public static function renderPlaceholderArray($inputArray, $chunkContent, $prefix = '[[+', $suffix = ']]')
    {
        if (!is_array($inputArray) || !$chunkContent) {
            return '';
        }
        $output = '';
        $chunkParts = self::explodeByArray(['<!-- for -->', '<!-- endfor -->'], $chunkContent);
        if (count($chunkParts) < 3) {
            $chunkParts = ['', $chunkParts[0], ''];
        }
        foreach ($inputArray as $data) {
            if (!is_array($data) && !is_object($data)) {
                continue;
            }
            $chunk = $chunkParts[1];
            foreach ($data as $key => $value) {
                if (!is_array($value)) {
                    $chunk = str_replace($prefix.$key.$suffix, $value, $chunk);
                }
            }
            $output .= $chunk;
        }
        return implode('', [$chunkParts[0], $output, $chunkParts[2]]);
    }

    /**
     * @param array $delim
     * @param string $input
     * @return array
     */
    public static function explodeByArray($delim, $input) {
        $uniDelim = $delim[0];
        $output = str_replace($delim, $uniDelim, $input);
        return explode($uniDelim, $output);
    }

    /**
     * Parse uri
     * @param string $uri
     * @return array
     */
    public static function parseUri($uri)
    {
        if (strrpos($uri, '/') === false) {
            $pageAlias = $uri;
        } else {
            $pageAlias = strrpos($uri, '/') < strlen($uri) - 1
                ? substr($uri, strrpos($uri, '/')+1)
                : '';
        }
        $parentUri = strrpos($uri, '/') !== false
            ? substr($uri, 0, strrpos($uri, '/')+1)
            : '';
        $levelNum = substr_count($parentUri, '/');
        return [$pageAlias, $parentUri, $levelNum];
    }

    /**
     * @param array $list
     * @param array $parent
     * @return array
     */
    public static function createTree(&$list, $parent){
        $tree = array();
        foreach ($parent as $k => $l){
            if(isset($l['id']) && isset($list[$l['id']])){
                $l['children'] = self::createTree($list, $list[$l['id']]);
            }
            $tree[] = $l;
        }
        return $tree;
    }

    /**
     * @param $qs
     * @return string
     */
    public static function normalizeQueryString($qs)
    {
        if ('' == $qs) {
            return '';
        }
        $qs = str_replace('&amp;', '&', $qs);
        parse_str($qs, $qs);
        ksort($qs);
        return http_build_query($qs, '', '&', PHP_QUERY_RFC3986);
    }
}
