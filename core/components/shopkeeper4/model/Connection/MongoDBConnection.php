<?php

namespace Andchir\Shopkeeper4\Connection;

class MongoDBConnection
{
    /** @var \MongoDB\Client */
    private $_client;
    /** @var MongoDBConnection */
    private static $_instance = null;
    private $queryCount = 0;

    private function __construct ($mongodb_url) {
        $this->_client = new \MongoDB\Client($mongodb_url);
    }

    private function __clone () {}
    private function __wakeup () {}

    public static function getInstance($mongodb_url)
    {
        if (self::$_instance === null)
        {
            self::$_instance = new self($mongodb_url);
        }
        return self::$_instance;
    }

    /**
     * @return \MongoDB\Client
     */
    public function getClient() {
        return $this->_client;
    }

    /**
     * @return int
     */
    public function getQueryCount()
    {
        return $this->queryCount;
    }

    /**
     * Set query count
     * @param $queryCount
     */
    public function setQueryCount($queryCount)
    {
        $this->queryCount = $queryCount;
    }

    /**
     * Query count increment
     * @param int $number
     */
    public function queryCountIncrement($number = 1)
    {
        $this->queryCount += $number;
    }
}
