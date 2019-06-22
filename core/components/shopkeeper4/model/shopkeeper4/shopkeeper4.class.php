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
        }
        if (self::getOption('debug', $this->config)) {
            if ($this->getIsError()) {
                return sprintf("<div style=\"padding:5px 10px; background-color:#f4c8b3; color:#a72323;\">ERROR: %s</div>", $this->getErrorMessage());
            }
            $this->modx->setPlaceholder('shk4.queryCount', $this->getMongoQueryCount());
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
     * @param bool $saveToPlaceholders
     * @return array
     */
    public function getListCategories($saveToPlaceholders = true)
    {
        $parentId = self::getOption('parent', $this->config);
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

        $crumb = $this->findOneFromArray($categories, 'parentId', 0);
        while (!empty($crumb)) {
            $breadcrumbs[] = $crumb;
            $crumb = $this->findOneFromArray($categories, 'parentId', $crumb['_id']);
        }
        if (!empty($breadcrumbs) && $pop) {
            array_pop($breadcrumbs);
        }

        return $breadcrumbs;
    }

    /**
     * @param array $items
     * @param string $key
     * @param string $value
     * @return null | mixed
     */
    public function findOneFromArray($items, $key, $value)
    {
        $index = array_search($value, array_column($items, $key));
        return $index !== false ? $items[$index] : null;
    }

    /**
     * @return int
     */
    public function getMongoQueryCount()
    {
        return $this->mongodbConnection->getQueryCount();
    }

    /**
     * @param string $optionName
     * @param array $properties
     * @return mixed|null
     */
    public static function getOption($optionName, $properties = [])
    {
        $optionsDefault = [
            'action' => 'products',
            'debug' => false,
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
        return $optionsDefault[$optionName] ?? null;
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
}
