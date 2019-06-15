<?php

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/mongodb_connection/mongodb_connection.class.php';

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
            'connectorUrl' => $assetsUrl . 'connector.php',
            'mongodb_url' => 'mongodb://localhost:27017',
            'mongodb_database' => 'default'
        ], $config);

        $this->mongodbConnection = \Andchir\Shopkeeper4\MongoDBConnection::getInstance($this->config['mongodb_url']);
    }

    /**
     * @param string $errorMessage
     */
    public function setErrorMessage($errorMessage)
    {
        $this->isError = true;
        $this->errorMessage = $errorMessage;
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
        } catch (\MongoDB\Driver\Exception\AuthenticationException $e) {
            $this->setErrorMessage($e->getMessage());
            return null;
        }
        return $this->mongodbConnection->getClient()
            ->selectCollection($this->config['mongodb_database'], $collectionName);
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        $categoryCollection = $this->getCollection('category');
        if ($this->getIsError()) {
            return $this->getErrorMessage();
        }

        try {
            $categories = $categoryCollection->find([], [
                'sort' => ['_id' => 1]
            ])->toArray();
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            return '';
        }

        return '<pre>' . print_r($categories, true) . '</pre>';
    }

}

