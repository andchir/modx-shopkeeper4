<?php

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/Connection/MongoDBConnection.php';

/**
 * Class Shopkeeper4
 * @author Andchir<andchir@gmail.com>
 */
class Shopkeeper4 {

    public $modx;
    public $config = [];
    private $mongodbConnection;
    private $errorMessage = null;
    private $isError = false;
    private $chunks = [];

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
        if ($line) {
            $errorMessage .= " LINE: {$line}";
        }
        $this->errorMessage = $errorMessage;
        $this->modx->log(modX::LOG_LEVEL_ERROR, $errorMessage);
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
     * @param string $collectionName
     * @return int
     */
    public function getNextId($collectionName)
    {
        $autoincrementCollection = $this->getCollection('doctrine_increment_ids');
        $count = $autoincrementCollection->count(['_id' => $collectionName]);
        if(!$count){
            $record = [
                '_id' => $collectionName,
                'current_id' => 0
            ];
            $autoincrementCollection->insertOne($record);
        }
        $ret = $autoincrementCollection->findOneAndUpdate(
            ['_id' => $collectionName],
            ['$inc' => ['current_id' => 1]],
            ['new' => true]
        );
        return $ret['current_id'];
    }

    /**
     * @param string $categoryUri
     * @param int|null $categoryId
     * @return array|null|object
     */
    public function getCategory($categoryUri = '', $categoryId = null)
    {
        $categoryCollection = $this->getCollection('category');
        if (!$categoryCollection) {
            return null;
        }
        $this->mongodbConnection->queryCountIncrement();
        if (!is_null($categoryId)) {
            return $categoryCollection->findOne([
                '_id' => $categoryId
            ], [
                'typeMap' => ['array' => 'array']
            ]);
        } else {
            return $categoryCollection->findOne([
                'uri' => $categoryUri
            ], [
                'typeMap' => ['array' => 'array']
            ]);
        }
    }

    /**
     * @param int $categoryId
     * @param string $collectionName
     * @param string $pageAlias
     * @param int $pageId
     * @return mixed|null
     */
    public function getCatalogItem($categoryId, $collectionName, $pageAlias = '', $pageId = 0)
    {
        $contentCollection = $this->getCollection($collectionName);
        if (!$contentCollection || (!$pageAlias && !$pageId)) {
            return null;
        }
        $where = [
            'parentId' => (int) $categoryId,
            'isActive' => true
        ];
        if ($pageAlias) {
            $where['name'] = $pageAlias;
        } else {
            $where['_id'] = $pageId;
        }
        $aggregateFields = $this->getAggregationFields(
            self::getOption('locale', $this->config),
            self::getOption('localeDefault', $this->config),
            true
        );
        $pipeline = $this->createAggregatePipeline(
            $where,
            $aggregateFields,
            1
        );
        $this->mongodbConnection->queryCountIncrement();
        $contentObject = $contentCollection->aggregate($pipeline, [
            'cursor' => []
        ])->toArray();
        if (empty($contentObject)) {
            return null;
        }
        return current($contentObject);
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
        $this->mongodbConnection->queryCountIncrement();
        return $contentTypeCollection->findOne([
            'name' => $category->contentTypeName
        ], [
            'typeMap' => ['array' => 'array']
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
                    $output = Shopkeeper4::renderTemplate($this->modx->placeholders[$placeholderName], $chunkContent);
                }

                break;
            case 'breadcrumbs':

                $this->config['placeholderName'] = 'shk4.dataBreadcrumbs';
                return $this->getOutput('render_placeholder_array');

                break;
            case 'categories':
                $output = $this->renderCategories();
                break;
            case 'products':
                $output = $this->renderProducts();
                break;
            case 'filters':
                $output = $this->renderFilters();
                break;
            case 'pagination':
                $output = $this->renderPagination();
                break;
        }
        if (self::getOption('debug', $this->config)) {
            if ($this->getIsError()) {
                return sprintf('<div style="padding:5px 10px; background-color:#f4c8b3; color:#a72323;">ERROR: %s</div>', $this->getErrorMessage());
            }
        }
        $this->modx->setPlaceholder('shk4.queryCount', $this->getMongoQueryCount());
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
            $properties = self::arrayFlatten($product);
            $properties['id'] = $properties['_id'];
            $output .= $this->modx->getChunk(self::getOption('rowTpl', $this->config), $properties);
        }

        return $output;
    }

    /**
     * Render catalog pagination
     * @return string
     */
    public function renderPagination()
    {
        $queryOptions = $this->modx->getPlaceholder('shk4.queryOptions') ?? [];
        if (empty($queryOptions)) {
            $queryOptions = ['page' => 1, 'limit' => self::getOption('limit')];
        }
        $total = $this->modx->getPlaceholder(self::getOption('totalPlaceholder', $this->config)) ?: 0;
        $pagesOptions = self::getPagesOptions($queryOptions, $total);

        if ($pagesOptions['total'] <= 1) {
            return '';
        }

        $output = '';
        $skipped = false;
        $categoryUri = '/' . $this->modx->getPlaceholder('shk4.categoryUri');
        $pages = range(1, $pagesOptions['total']);
        $pageNavTpl = self::getOption('pageNavTpl', $this->config);
        $pageActiveTpl = self::getOption('pageActiveTpl', $this->config);
        $pageNavOuterTpl = self::getOption('pageNavOuterTpl', $this->config);
        $pagePrevTpl = self::getOption('pagePrevTpl', $this->config);
        $pageNextTpl = self::getOption('pageNextTpl', $this->config);
        $pageSkippedTpl = self::getOption('pageSkippedTpl', $this->config);

        $categoryUri .= '?limit=' . $pagesOptions['limit'] . $queryOptions['filterStr'];

        foreach ($pages as $page) {
            if ($page > 2
                && $page < $pagesOptions['total'] - 1
                && ($page < $pagesOptions['current'] - 2 || $page > $pagesOptions['current'] + 2)) {
                    if (!$skipped) {
                        $output .= $pageSkippedTpl;
                    }
                $skipped = true;
            } else {
                $skipped = false;
                $output .= self::replacePlaceholders([
                    'pageNo' => $page,
                    'href' => $categoryUri . '&page=' . $page
                ], $pagesOptions['current'] == $page ? $pageActiveTpl : $pageNavTpl);
            }
        }

        if ($pagePrevTpl && $pagesOptions['current'] != $pagesOptions['prev']) {
            $output = self::replacePlaceholders([
                'href' => $categoryUri . '&page=' . $pagesOptions['prev']
                ], $pagePrevTpl) . $output;
        }
        if ($pageNextTpl && $pagesOptions['current'] != $pagesOptions['next']) {
            $output .= self::replacePlaceholders([
                'href' => $categoryUri . '&page=' . $pagesOptions['next']
            ], $pageNextTpl);
        }
        if ($pageNavOuterTpl) {
            $output = self::replacePlaceholders(['wrapper' =>  $output], $pageNavOuterTpl);
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
            $properties = self::objectToArray($category);
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
     * @return string
     */
    public function renderFilters()
    {
        $filters = $this->getListFilters();
        if (empty($filters)) {
            return '';
        }
        $contentType = $this->modx->getPlaceholder('shk4.contentType');
        $contentTypeFields = $contentType ? $contentType->fields : [];
        $output = '';
        $chunks = [];
        $queryOptions = $this->modx->getPlaceholder('shk4.queryOptions');
        $filtersQueryData = $queryOptions['filter'] ?? [];

        foreach ($filters->values as $name => $filterValues){
            $filterData = self::findOneFromArray($contentTypeFields, 'name', $name);
            $properties = $filterData instanceof stdClass
                ? json_decode(json_encode($filterData), true)
                : $filterData;
            $chunkName = self::getOption('rowTpl', $this->config);
            $chunk = self::replacePlaceholders($properties, $this->getChunkContent($chunkName));

            $chunkName = 'shk4_filter_' . $filterData->inputType;
            $chunkDefaultName = 'shk4_filter_default';
            if (!isset($chunks[$chunkName])) {
                $chunks[$chunkName] = $this->getChunkContent($chunkName, $chunkDefaultName);
            }
            if ($filterData->inputType === 'number') {
                $filterData->min = min($filterValues);
                $filterData->max = max($filterValues);
                $filterData->minValue = isset($filtersQueryData[$name])
                    ? ($filtersQueryData[$name]['from'] ?? $filterData->min)
                    : $filterData->min;
                $filterData->maxValue = isset($filtersQueryData[$name])
                    ? ($filtersQueryData[$name]['to'] ?? $filterData->max)
                    : $filterData->max;
                $filterValues = [];
            }

            $wrapperContent = self::renderTemplate($filterValues, $this->getChunkContent($chunkName, $chunkDefaultName), $filterData);
            $chunk = str_replace('[[+wrapper]]', $wrapperContent, $chunk);

            $output .= $chunk;
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
     * @param string $chunkName
     * @param string $chunkDefaultName
     * @return string
     */
    public function getChunkContent($chunkName, $chunkDefaultName = '')
    {
        if (isset($this->chunks[$chunkName])) {
            return $this->chunks[$chunkName];
        }
        $chunkObj = $this->modx->getObjectGraph('modChunk', ['Source' => []], ['name' => $chunkName]);
        if ($chunkObj) {
            $this->chunks[$chunkName] = $chunkObj->getContent();
            return $this->chunks[$chunkName];
        }
        if ($chunkDefaultName) {
            if (isset($this->chunks[$chunkDefaultName])) {
                return $this->chunks[$chunkDefaultName];
            }
            $chunkObj = $this->modx->getObjectGraph('modChunk', ['Source' => []], ['name' => $chunkDefaultName]);
            $this->chunks[$chunkDefaultName] = $chunkObj ? $chunkObj->getContent() : '';
            return $this->chunks[$chunkDefaultName];
        }
        $this->chunks[$chunkName] = '';
        $this->setErrorMessage("Chunk {$chunkName} not found.", __LINE__);
        return $this->chunks[$chunkName];
    }

    /**
     * @return array
     */
    public function getListProducts()
    {
        $parentId = $this->getParentId(false);
        if (!is_null($parentId)) {
            $currentCategory = $this->getCategory('', $parentId);
            $contentType = $this->getContentType($currentCategory);
            if (!$contentType) {
                $this->setErrorMessage('Content type not found.', __LINE__);
            }
        } else {
            $currentCategory = $this->modx->getPlaceholder('shk4.category');
            $contentType = $this->modx->getPlaceholder('shk4.contentType');
        }
        if (!$contentType) {
            return [];
        }
        $productsCollection = $this->getCollection($contentType->collection);
        if (!$productsCollection) {
            return [];
        }
        $contentTypeFields = $contentType->fields;
        $queryOptions = $this->getQueryOptionsData();

        $aggregateFields = $this->getAggregationFields(
            self::getOption('locale', $this->config),
            self::getOption('localeDefault', $this->config),
            true,
            $contentType
        );
        $criteria = [
            'isActive' => true
        ];
        $this->applyFilters($queryOptions['filter'], $contentTypeFields, $criteria);
        $this->applyCategoryFilter($currentCategory, $contentTypeFields, $criteria, $parentId);

        $total = $productsCollection->countDocuments($criteria);
        $this->mongodbConnection->queryCountIncrement();
        $this->modx->setPlaceholder(self::getOption('totalPlaceholder', $this->config), $total);
        $pagesOptions = self::getPagesOptions($queryOptions, $total);

        $pipeline = $this->createAggregatePipeline(
            $criteria,
            $aggregateFields,
            $queryOptions['limit'],
            $queryOptions['sortOptionsAggregation'],
            $pagesOptions['skip']
        );
        $this->mongodbConnection->queryCountIncrement();

        if (!$total && self::getOption('emptyMessage', $this->config)) {
            $this->modx->setPlaceholder(self::getOption('toPlaceholder', $this->config).'_emptyMessage', self::getOption('emptyMessage', $this->config));
        }

        return $productsCollection->aggregate($pipeline, [
            'cursor' => []
        ])->toArray();
    }

    /**
     * @param string $locale
     * @param string $localeDefault
     * @param bool $addFieldsOnly
     * @param null|object $contentType
     * @return array
     */
    public function getAggregationFields($locale, $localeDefault, $addFieldsOnly = false, $contentType = null)
    {
        if (is_null($contentType)) {
            $contentType = $this->modx->getPlaceholder('shk4.contentType');
        }
        if (!$contentType) {
            $this->setErrorMessage('Content type not found.', __LINE__);
            return [];
        }
        $aggregateFields = [];
        if ($locale === $localeDefault && $addFieldsOnly) {
            return [];
        }
        foreach ($contentType->fields as $contentTypeField) {
            if ($locale !== $localeDefault
                && in_array($contentTypeField->inputType, ['text', 'textarea', 'rich_text'])) {
                $aggregateFields[$contentTypeField->name] = "\$translations.{$contentTypeField->name}.{$locale}";
            } else if (!$addFieldsOnly) {
                $aggregateFields[$contentTypeField->name] = 1;
            }
        }
        if (!$addFieldsOnly) {
            $aggregateFields['parentId'] = 1;
        }
        return $aggregateFields;
    }

    /**
     * @param stdClass $currentCategory
     * @param array $contentTypeFields
     * @param array $criteria
     * @param null $parent
     */
    public function applyCategoryFilter(stdClass $currentCategory, $contentTypeFields, &$criteria, $parent = null)
    {
        $categoriesField = array_filter($contentTypeFields, function($field){
            return $field->inputType == 'categories';
        });
        $categoriesField = current($categoriesField);
        $parentId = !is_null($parent) ? intval($parent) : $currentCategory->_id;

        if (!empty($categoriesField)) {
            $orCriteria = [
                '$or' => [
                    ['parentId' => $parentId]
                ]
            ];
            $orCriteria['$or'][] = ["{$categoriesField->name}" => [
                '$elemMatch' => ['$in' => [$parentId]]
            ]];
            $criteria = ['$and' => [$criteria, $orCriteria]];

        } else {
            $criteria['parentId'] = $parentId;
        }
    }

    /**
     * @param array $filters
     * @param array $contentTypeFields
     * @param $criteria
     */
    public function applyFilters($filters, $contentTypeFields, &$criteria)
    {
        if (empty($filters)) {
            return;
        }

        foreach ($filters as $name => $filter) {
            if (empty($filter)) {
                continue;
            }
            if (!is_array($filter)) {
                $filter = [$filter];
            }
            $index = array_search($name, array_column($contentTypeFields, 'name'));
            $outputType = '';
            if ($index !== false) {
                $flt = $contentTypeFields[$index];
                $outputType = $flt->outputType;
                // Process color filter
                if ($outputType === 'color') {
                    foreach ($filter as &$val) {
                        $val = '#' . $val;
                    }
                }
            }
            if (isset($filter['from']) && isset($filter['to'])) {
                $criteria[$name] = ['$gte' => floatval($filter['from']), '$lte' => floatval($filter['to'])];
            } else if ($outputType === 'parameters') {
                $fData = [];
                foreach ($filter as $fValue) {
                    $fValueArr = explode('__', $fValue);
                    if (count($fValueArr) < 2) {
                        continue;
                    }
                    $index = array_search($fValueArr[0], array_column($fData, 'name'));
                    if ($index === false) {
                        $fData[] = [
                            'name' => $fValueArr[0],
                            'values' => []
                        ];
                        $index = count($fData) - 1;
                    }
                    if (!in_array($fValueArr[1], $fData[$index]['values'])) {
                        $fData[$index]['values'][] = $fValueArr[1];
                    }
                }
                if (!empty($fData)) {
                    $criteria[$name] = ['$all' => []];
                    foreach ($fData as $k => $v) {
                        $criteria[$name]['$all'][] = [
                            '$elemMatch' => [
                                'name' => $v['name'],
                                'value' => ['$in' => $v['values']]
                            ]
                        ];
                    }
                }
            } else {
                $criteria[$name] = ['$in' => $filter];
            }
        }
    }

    /**
     * @param $contentTypeFields
     * @param array $options
     * @param string $type
     * @param array $filtersArr
     * @param array $filtersData
     * @param array $queryOptions
     * @return array
     */
    public function getFieldsData($contentTypeFields, $options = [], $type = 'page', $filtersArr = [], $filtersData = [], $queryOptions = [])
    {
        $filters = [];
        $fields = [];
        $filterIndex = 0;
        $queryOptionsFilter = !empty($queryOptions['filter']) ? $queryOptions['filter'] : [];
        foreach ($contentTypeFields as $field) {
            if ($type != 'list' || !empty($field['showInList'])) {
                if (!isset($field['outputProperties']['chunkName'])) {
                    $field['outputProperties']['chunkName'] = '';
                }
                $fields[] = array_merge($field, [
                    'listOrder' => $field['listOrder'] ?? 0,
                    'outputProperties' => array_merge($field['outputProperties'], $options)
                ]);
            }
            if (!empty($field['isFilter'])) {
                if (!empty($filtersArr[$field['name']])) {
                    $filters[] = [
                        'name' => $field['name'],
                        'title' => $field['title'],
                        'outputType' => $field['outputType'],
                        'values' => $filtersArr[$field['name']],
                        'fieldValues' => $filtersArr[$field['name']],
                        'index' => $filterIndex,
                        'order' => !empty($field['filterOrder']) ? $field['filterOrder'] : 0,
                        'selected' => isset($queryOptionsFilter[$field['name']])
                            ? is_array($queryOptionsFilter[$field['name']])
                                ? $queryOptionsFilter[$field['name']]
                                : [$queryOptionsFilter[$field['name']]]
                            : []
                    ];
                    $filterIndex++;
                } else if (!empty($filtersData)) {
                    $fieldFilterData = array_filter($filtersData, function($item) use ($field) {
                        return $item['fieldName'] === $field['name'];
                    });
                    foreach ($fieldFilterData as $fData) {
                        $fValues = array_map(function($value) use ($fData) {
                            return $fData['name'] . '__' . $value;
                        }, $fData['values']);
                        $filters[] = [
                            'name' => $field['name'],
                            'title' => $fData['name'],
                            'outputType' => $field['outputType'],
                            'values' => $fData['values'],
                            'fieldValues' => $fValues,
                            'index' => $filterIndex,
                            'order' => !empty($field['filterOrder']) ? $field['filterOrder'] : 0,
                            'selected' => isset($queryOptionsFilter[$field['name']])
                                ? is_array($queryOptionsFilter[$field['name']])
                                    ? $queryOptionsFilter[$field['name']]
                                    : [$queryOptionsFilter[$field['name']]]
                                : []
                        ];
                        $filterIndex++;
                    }
                }
            }
        }

        usort($filters, function($a, $b) {
            if ($a['order'] == $b['order']) {
                return 0;
            }
            return ($a['order'] < $b['order']) ? -1 : 1;
        });

        if (!empty($options['needSortFields'])) {
            usort($fields, function($a, $b) {
                if ($a['listOrder'] == $b['listOrder']) {
                    return 0;
                }
                return ($a['listOrder'] < $b['listOrder']) ? -1 : 1;
            });
        }

        return [$filters, $fields];
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
                    $data[$category->parentId][] = self::objectToArray($category);
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
     * @return array|null|object
     */
    public function getListFilters()
    {
        $filtersCollection = $this->getCollection('filters');
        if (!$filtersCollection) {
            return [];
        }
        $currentCategory = $this->modx->getPlaceholder('shk4.category');
        try {
            $filters = $filtersCollection->findOne([
                'categoryId' => $currentCategory->_id
            ], [
                'typeMap' => ['array' => 'array']
            ]);
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage(), __LINE__);
            return [];
        }
        $this->mongodbConnection->queryCountIncrement();
        return $filters;
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
     * @param bool $useCurrentCategory
     * @return int|null
     */
    public function getParentId($useCurrentCategory = true)
    {
        $parentId = self::getOption('parent', $this->config);
        if (!is_null($parentId)) {
            $parentId = intval($parentId);
        } else if ($useCurrentCategory) {
            $currentCategory = $this->modx->getPlaceholder('shk4.category');
            if ($currentCategory) {
                $parentId = $currentCategory->_id;
            }
        }
        return $parentId;
    }

    /**
     * @return int
     */
    public function getMongoQueryCount()
    {
        return $this->mongodbConnection->getQueryCount();
    }

    /**
     * @param bool $useCache
     * @return array
     */
    public function getQueryOptionsData($useCache = true)
    {
        if ($useCache && $queryOptions = $this->modx->getPlaceholder('shk4.queryOptions')) {
            return $queryOptions;
        }
        $request_param_alias = $this->modx->getOption('request_param_alias',null,'q');
        $uri = Shopkeeper4::getUriString($request_param_alias);
        $catalogNavSettingsDefaults = [
            'pageSizeArr' => Shopkeeper4::stringToArray($this->modx->getOption('shopkeeper4.catalog_page_size', null, '12,24,60')),
            'orderBy' => $this->modx->getOption('shopkeeper4.catalog_default_order_by', null, 'title_asc'),
            'queryPrefix' => self::getOption('queryPrefix', $this->config)
        ];
        if ($orderBy = self::getOption('orderBy', $this->config)) {
            $catalogNavSettingsDefaults['orderBy'] = $orderBy;
        }
        if ($limit = self::getOption('limit', $this->config)) {
            $catalogNavSettingsDefaults['pageSizeArr'] = [$limit];
        }
        $queryOptions = self::getQueryOptions($uri, [], $catalogNavSettingsDefaults);
        if ($useCache) {
            $this->modx->setPlaceholder('shk4.queryOptions', $queryOptions);
        }
        return $queryOptions;
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
     * @param array|object $contentTypeFields
     * @param string $chunkName
     * @param string $defaultValue
     * @return string
     */
    public function getFieldByChunkName($contentTypeFields, $chunkName, $defaultValue = '')
    {
        $index = array_search(
            $chunkName,
            array_map( function($outputProperties) {
                return $outputProperties->chunkName ?? '';
            }, array_column($contentTypeFields, 'outputProperties'))
        );
        return $index !== false ? $contentTypeFields[$index]->name : $defaultValue;
    }

    /**
     * Get system field name
     * @param array|object $contentTypeFields
     * @param string $defaultValue
     * @return string
     */
    public function getSystemNameField($contentTypeFields, $defaultValue = 'name')
    {
        $output = $defaultValue;
        foreach ($contentTypeFields as $contentTypeField) {
            if (!empty($contentTypeField->inputType)
                && $contentTypeField->inputType == 'system_name') {
                $output = $contentTypeField->name;
                break;
            }
        }
        return $output;
    }

    /**
     * @param string $groupName
     * @return array|bool
     */
    public function getSettings($groupName = '')
    {
        $settingsCollection = $this->getCollection('settings');
        if (!$settingsCollection) {
            return false;
        }
        $where = [];
        if ($groupName) {
            $where['groupName'] = $groupName;
        }
        return $settingsCollection->find($where, [
            'typeMap' => ['array' => 'array']
        ])->toArray();
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
        $queryPrefix = self::getOption('queryPrefix', $catalogNavSettingsDefaults);
        parse_str($queryString, $queryOptions);
        unset($queryOptions['q']);

        // Apply query prefix
        if ($queryPrefix) {
            $queryOptions['limit'] = $queryOptions[$queryPrefix . 'limit'] ?? null;
            $queryOptions['page'] = $queryOptions[$queryPrefix . 'page'] ?? null;
            $queryOptions['order_by'] = $queryOptions[$queryPrefix . 'order_by'] ?? null;
            $queryOptions['filter'] = $queryOptions[$queryPrefix . 'filter'] ?? null;
        }

        $queryOptions = array_filter($queryOptions, function ($value) {
            return $value !== null;
        });
        $queryOptions = array_merge($queryOptionsDefault, $queryOptions);

        if (!empty($options['pageVar']) && isset($queryOptions[$queryPrefix . $options['pageVar']])) {
            $queryOptions['page'] = $queryOptions[$queryPrefix . $options['pageVar']];
        }
        if (!empty($options['limitVar']) && isset($queryOptions[$queryPrefix . $options['limitVar']])) {
            $queryOptions['limit'] = $queryOptions[$queryPrefix . $options['limitVar']];
        }

        $queryOptions['uri'] = $currentUri;

        // Sorting
        if ($queryOptions['order_by'] && strpos($queryOptions['order_by'], '_') !== false) {
            $pos = strrpos($queryOptions['order_by'], '_');
            $queryOptions['sort_by'] = substr($queryOptions['order_by'], 0, $pos);
            $queryOptions['sort_dir'] = substr($queryOptions['order_by'], $pos + 1);
        }

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
        if (empty($queryOptions['filter']) || !is_array($queryOptions['filter'])) {
            $queryOptions['filter'] = [];
        }
        if (!empty($queryOptions['filter'])) {
            $queryOptions['filterStr'] = '&' . http_build_query(['filter' => $queryOptions['filter']]);
        }
        if (!empty($queryOptions['query']) && !is_array($queryOptions['query'])) {
            $queryOptions['filterStr'] = '&' . http_build_query(['query' => $queryOptions['query']]);
        }

        return $queryOptions;
    }

    /**
     * @param string $input
     * @return mixed
     */
    public function removeModxTags($input)
    {
        $input = preg_replace($this->modx->sanitizePatterns['tags1'], '', $input);
        $input = preg_replace($this->modx->sanitizePatterns['tags2'], '', $input);
        return $input;
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
            'queryPrefix' => '',
            'parent' => null,
            'limit' => 12,
            'tpl' => '',
            'rowTpl' => 'shk4_menuRowTpl',
            'outerTpl' => 'shk4_menuOuterTpl',
            'totalPlaceholder' => 'products_total',
            'activeClassName' => 'active',
            'cacheKey' => '',
            'toPlaceholder' => '',
            'cache_resource_handler' => 'xPDOFileCache',
            'cache_expires' => 0,
            'pageNavOuterTpl' => '',
            'pagePrevTpl' => '',
            'pageNextTpl' => '',
            'pageNavTpl' => '',
            'pageActiveTpl' => '',
            'pageSkippedTpl' => ''
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
     * @param array $inputListArray
     * @param string $chunkContent
     * @param array $inputOuterArray
     * @param string $prefix
     * @param string $suffix
     * @return string
     */
    public static function renderTemplate($inputListArray, $chunkContent, $inputOuterArray = [], $prefix = '[[+', $suffix = ']]')
    {
        if (!is_array($inputListArray) || !$chunkContent) {
            return $chunkContent;
        }
        $output = '';
        $chunkParts = self::explodeByArray(['<!-- for -->', '<!-- endfor -->'], $chunkContent);
        if (!empty($inputListArray) && count($chunkParts) >= 3) {
            foreach ($inputListArray as $index => $value) {
                if (is_array($value) || is_object($value)) {
                    $data = $value;
                } else {
                    $data = ['value' => $value];
                }
                $data['index'] = $index;
                $chunk = $chunkParts[1];
                $chunk = self::replacePlaceholders($data, $chunk, $prefix, $suffix);
                $output .= $chunk;
            }
            $output = implode('', [$chunkParts[0], $output, $chunkParts[2]]);
        } else {
            $output = count($chunkParts) >= 3 ? $chunkParts[1] : $chunkParts[0];
        }
        if (!empty($inputOuterArray)) {
            $output = self::replacePlaceholders($inputOuterArray, $output, $prefix, $suffix);
        }
        return $output;
    }

    /**
     * @param array $inputArray
     * @param string $chunkContent
     * @param string $prefix
     * @param string $suffix
     * @return mixed
     */
    public static function replacePlaceholders($inputArray, $chunkContent, $prefix = '[[+', $suffix = ']]')
    {
        if (!is_array($inputArray) && !is_object($inputArray)) {
            return $chunkContent;
        }
        foreach ($inputArray as $key => $value) {
            if (!is_array($value) && !is_object($value)) {
                $chunkContent = str_replace($prefix.$key.$suffix, $value, $chunkContent);
            }
        }
        return $chunkContent;
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
     * @param string $request_param_alias
     * @return string
     */
    public static function getUriString($request_param_alias)
    {
        return isset($_GET[$request_param_alias]) ? $_GET[$request_param_alias] : '';
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
     * @param mixed $input
     * @return array|mixed
     */
    public static function objectToArray($input)
    {
        if ($input instanceof stdClass) {
            return json_decode(json_encode($input), true);
        }
        if ($input instanceof \MongoDB\Model\BSONDocument) {
            return iterator_to_array($input);
        }
        return $input;
    }

    /**
     * @param array|object $input
     * @param string $parentKey
     * @param array $placeholders
     * @return array
     */
    public static function createPlaceholdersArray($input, $parentKey = '', $placeholders = [])
    {
        $input = self::objectToArray($input);
        foreach ($input as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $placeholders = self::createPlaceholdersArray($value, ($parentKey ? $parentKey.'.' : '') . $key, $placeholders);
            } else {
                $placeholders[($parentKey ? $parentKey.'.' : '') . $key] = $value;
            }
        }
        return $placeholders;
    }

    /**
     * Array convert to flatten keys
     * @param array $input
     * @param string $prefix
     * @return array
     */
    public static function arrayFlatten($input, $prefix = '') {
        $input = self::objectToArray($input);
        $result = [];
        foreach($input as $key=> $value) {
            if(is_array($value) || is_object($value)) {
                $result = $result + self::arrayFlatten($value, $prefix . $key . '.');
            }
            else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
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
