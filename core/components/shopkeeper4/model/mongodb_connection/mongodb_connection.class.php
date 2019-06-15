<?php

namespace Andchir\Shopkeeper4;

class MongoDBConnection
{
    /** @var \MongoDB\Client */
    private $_client;
    /** @var MongoDBConnection */
    private static $_instance = null;

    private function __construct ($mongodb_url) {
        $this->_client = new \MongoDB\Client($mongodb_url);
    }

    private function __clone () {}
    private function __wakeup () {}

    public static function getInstance($mongodb_url)
    {
        if (self::$_instance != null) {
            return self::$_instance;
        }
        return new self($mongodb_url);
    }

    /**
     * @return \MongoDB\Client
     */
    public function getClient() {
        return $this->_client;
    }
}
