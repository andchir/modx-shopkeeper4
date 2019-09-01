<?php

/**
 * Class ShoppingCart
 * @author Andchir<andchir@gmail.com>
 */
class ShoppingCart {

    const EVENT_OnShoppingCartAddProduct = 'OnShoppingCartAddProduct';
    const EVENT_OnShoppingCartCheckoutSave = 'OnShoppingCartCheckoutSave';
    const SESSION_KEY = 'shoppingcart_sessid';

    public $modx;
    public $config = array();
    private $errorMessage = null;
    private $isError = false;

    public function __construct(modX &$modx, array $config = []) {
        $this->modx =& $modx;
        $basePath = $this->modx->getOption('shopping_cart.core_path', $config, $this->modx->getOption('core_path') . 'components/shopping_cart/');
        $assetsUrl = $this->modx->getOption('shopping_cart.assets_url', $config,$this->modx->getOption('assets_url') . 'components/shopping_cart/');
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
            'debug' => false,
            'lifeTime' => 172800,// 48 hours
            'currency' => 'USD',
            'rowTpl' => 'shoppingCart_rowTpl',
            'outerTpl' => 'shoppingCart_outerTpl',
            'emptyTpl' => 'shoppingCart_emptyTpl',
            'action' => '',
            'contentType' => ''
        ], $config);

        $this->modx->addPackage('shopping_cart', $this->config['modelPath']);
    }

    /**
     * @param array $config
     */
    public function updateConfig($config)
    {
        if (!is_array($config)) {
            return;
        }
        $this->config = array_merge($this->config, $config);
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
     * Create placeholders with shopping cart data
     */
    public function createPagePlaceholders()
    {
        $shoppingCart = $this->getShoppingCart($this->getUserId(), $this->getSessionId());
        $shoppingCartContent = $shoppingCart ? $shoppingCart->getMany('Content') : [];
        $placeholders = [
            'price_total' => $this->getTotalPrice($shoppingCartContent),
            'items_total' => $this->getTotalCount($shoppingCartContent),
            'items_unique_total' => count($shoppingCartContent),
            'delivery_price' => 0,
            'delivery_name' => 0,
            'ids' => implode(',', ShoppingCart::getContentValues($shoppingCartContent, 'item_id')),
        ];
        $this->modx->setPlaceholders($placeholders, 'shopping_cart.');
    }

    /**
     * @param bool $returnArray
     * @return string|array
     */
    public function actionResponse($returnArray = false)
    {
        $output = '';
        $action = $this->getActionName();
        switch ($action) {
            case 'add_to_cart':

                $output = $this->addToCartAction();

                if (!empty($_SERVER['HTTP_REFERER']) && !$returnArray) {
                    $this->modx->sendRedirect($_SERVER['HTTP_REFERER']);
                }

                break;
            case 'print':

                $output = $this->renderOutput();

                break;
            case 'remove':

                $index = isset($_REQUEST['index']) ? (int) $_REQUEST['index'] : null;
                if (is_null($index) && isset($_POST['remove_by_index'])) {
                    $index = (int) $_POST['remove_by_index'];
                }
                if (is_null($index)) {
                    $this->modx->log(modX::LOG_LEVEL_ERROR, 'Product index not specified.');
                    break;
                }
                $output = $this->removeByIndex($index);

                if (!empty($_SERVER['HTTP_REFERER']) && !$returnArray) {
                    $this->modx->sendRedirect($_SERVER['HTTP_REFERER']);
                }

                break;
            case 'update':

                $output = $this->updateAction();

                if (!empty($_SERVER['HTTP_REFERER']) && !$returnArray) {
                    $this->modx->sendRedirect($_SERVER['HTTP_REFERER']);
                }

                break;
            case 'clean':

                $this->clean();

                if (!empty($_SERVER['HTTP_REFERER']) && !$returnArray) {
                    $this->modx->sendRedirect($_SERVER['HTTP_REFERER']);
                }

                break;
        }
        if ($this->config['debug'] && $this->getIsError()) {
            return sprintf('<div style="padding:5px 10px; background-color:#f4c8b3; color:#a72323;">ERROR: %s</div>', $this->getErrorMessage());
        }
        return $returnArray ? [
            'result' => $output
        ] : $output;
    }

    /**
     * @param null|int $userId
     * @param null|string $sessionId
     * @param null|int $id
     * @param bool $create
     * @return null|xPDOObject
     */
    public function getShoppingCart($userId = null, $sessionId = null, $id = null, $create = false)
    {
        /** @var xPDOObject $shoppingCart */
        if ($id) {
            $shoppingCart = $this->modx->getObject('ShoppingCartItem', (int) $id);
        } else if ($userId || $sessionId || $id) {
            $query = $this->modx->newQuery('ShoppingCartItem');
            if ($userId && $sessionId) {
                $query->where([
                    [
                        'createdby' => $userId,
                        'OR:session_id:=' => $sessionId
                    ]
                ]);
            } else if ($userId) {
                $query->where(['createdby' => $userId]);
            } else if ($sessionId) {
                $query->where(['session_id' => $sessionId]);
            }
            $query->andCondition(['type' => $this->config['contentType']]);
            $shoppingCart = $this->modx->getObject('ShoppingCartItem', $query);
        } else {
            return null;
        }
        if (!$shoppingCart && $create) {
            $user = $this->getUser();
            $shoppingCart = $this->modx->newObject('ShoppingCartItem', [
                'session_id' => $sessionId,
                'createdon' => strftime('%Y-%m-%d %H:%M:%S'),
                'editedon' => strftime('%Y-%m-%d %H:%M:%S'),
                'currency' => $this->config['currency'],
                'type' => $this->config['contentType']
            ]);
            if ($this->config['lifeTime']) {
                $shoppingCart->set('expireson', strftime('%Y-%m-%d %H:%M:%S', time() + $this->config['lifeTime']));
            }
            if ($user) {
                $shoppingCart->addOne($user);
            }
            $shoppingCart->save();
        }
        return $shoppingCart;
    }

    /**
     * @return xPDOObject[]
     */
    public function getShoppingCartsBySession()
    {
        return $this->modx->getCollection('ShoppingCartItem', [
            'session_id' => $this->getSessionId()
        ]);
    }

    /**
     * @return bool
     */
    public function addToCartAction()
    {
        $itemData = $this->modx->invokeEvent( self::EVENT_OnShoppingCartAddProduct, ['data' => $_POST]);
        if (empty($itemData)
            || empty($itemData[0])
            || empty($itemData[0]['title'])) {
                return false;
        }
        $itemData = current($itemData);
        $shoppingCart = $this->getShoppingCart($this->getUserId(), $this->getSessionId(), null, true);
        /** @var xPDOObject[] $shoppingCartContent */
        $shoppingCartContent = $shoppingCart->getMany('Content');
        $contentIndex = $this->getContentIndex($shoppingCartContent, $itemData);
        if ($contentIndex > -1) {
            $count = $shoppingCartContent[$contentIndex]->get('count');
            $shoppingCartContent[$contentIndex]->set('count', $count + $itemData['count']);
        } else {
            $shoppingCartContent[] = $this->modx->newObject('ShoppingCartContent', [
                'item_id' => $itemData['id'] ?? 0,
                'title' => $itemData['title'],
                'name' => $itemData['name'] ?? '',
                'price' => $itemData['price'] ?? 0,
                'count' => $itemData['count'] ?? 1,
                'uri' => $itemData['uri'] ?? '',
                'options' => $itemData['options'] ?? []
            ]);
        }
        $shoppingCart->set('editedon', strftime('%Y-%m-%d %H:%M:%S'));
        $shoppingCart->addMany($shoppingCartContent);
        $shoppingCart->save();

        return true;
    }

    /**
     * @return bool
     */
    public function updateAction()
    {
        if (empty($_POST['count'])) {
            return false;
        }
        $shoppingCart = $this->getShoppingCart($this->getUserId(), $this->getSessionId());
        if (!$shoppingCart) {
            return false;
        }
        $index = 0;
        /** @var xPDOObject[] $shoppingCartContent */
        $shoppingCartContent = $shoppingCart->getMany('Content');
        foreach ($shoppingCartContent as $content) {
            if (isset($_POST['count'][$index]) && is_numeric($_POST['count'][$index])) {
                $content->set('count', max(1, intval($_POST['count'][$index])));
                $content->save();
            }
            $index++;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function clean()
    {
        $shoppingCart = $this->getShoppingCart($this->getUserId(), $this->getSessionId());
        if (!$shoppingCart) {
            return false;
        }
        /** @var xPDOObject[] $shoppingCartContent */
        $shoppingCartContent = $shoppingCart->getMany('Content');
        foreach ($shoppingCartContent as $content) {
            $content->remove();
        }
        $shoppingCart->remove();
        return true;
    }

    /**
     * @param int $index
     * @return bool
     */
    public function removeByIndex($index)
    {
        $shoppingCart = $this->getShoppingCart($this->getUserId(), $this->getSessionId());
        if (!$shoppingCart) {
            return false;
        }
        /** @var xPDOObject[] $shoppingCartContent */
        $shoppingCartContent = $shoppingCart->getMany('Content');
        if (count($shoppingCartContent) <= 1 && $index === 0) {
            return $this->clean();
        }
        if (empty($shoppingCartContent)) {
            return false;
        }
        $shoppingCartContent = array_merge($shoppingCartContent);
        if (isset($shoppingCartContent[$index])) {
            $shoppingCartContent[$index]->remove();
        }
        return true;
    }

    /**
     * @param array $properties
     * @return string
     */
    public function renderOutput($properties = [])
    {
        $shoppingCart = $this->getShoppingCart($this->getUserId(), $this->getSessionId());
        /** @var xPDOObject[] $shoppingCartContent */
        $shoppingCartContent = $shoppingCart ? $shoppingCart->getMany('Content') : [];
        if (empty($shoppingCartContent)) {
            return $this->config['emptyTpl']
                ? $this->modx->getChunk($this->config['emptyTpl'])
                : '';
        }
        $priceTotal = $this->getTotalPrice($shoppingCartContent);
        $countTotal = $this->getTotalCount($shoppingCartContent);

        $output = '';

        if ($this->config['rowTpl']) {
            $index = 0;
            foreach ($shoppingCartContent as $content) {
                $output .= $this->modx->getChunk($this->config['rowTpl'], array_merge($content->toArray(), $properties, [
                    'index' => $index,
                    'num' => $index + 1,
                    'priceTotal' => self::getContentPriceTotal($content)
                ]));
                if (!$output) {
                    $this->setErrorMessage("Chunk \"{$this->config['rowTpl']}\" not found.", __LINE__);
                    break;
                }
                $index++;
            }
        }
        if ($this->config['outerTpl']) {
            $output = $this->modx->getChunk($this->config['outerTpl'], array_merge($properties, [
                'wrapper' => $output,
                'priceTotal' => $priceTotal,
                'countTotal' => $countTotal,
                'countTotalUnique' => count($shoppingCartContent),
                'currency' => $shoppingCart->get('currency')
            ]));
            if (!$output) {
                $this->setErrorMessage("Chunk \"{$this->config['outerTpl']}\" not found.", __LINE__);
            }
        }

        return $output;
    }

    /**
     * @param array $shoppingCartContent
     * @return int|float
     */
    public function getTotalPrice($shoppingCartContent)
    {
        $total = 0;
        foreach ($shoppingCartContent as $content) {
            $total += self::getContentPriceTotal($content);
        }
        return $total;
    }

    /**
     * @param array $shoppingCartContent
     * @return float|int
     */
    public function getTotalCount($shoppingCartContent)
    {
        if (empty($shoppingCartContent)) {
            return 0;
        }
        $countArr = array_column($shoppingCartContent, 'count');
        return array_sum($countArr);
    }

    /**
     * @param array $shoppingCartContent
     * @param array $itemData
     * @return int
     */
    public function getContentIndex($shoppingCartContent, $itemData)
    {
        if (empty($shoppingCartContent)) {
            return -1;
        }
        $index = array_search($itemData['id'], array_column($shoppingCartContent, 'item_id', 'id'));
        if ($index === false
            || ($shoppingCartContent[$index]->get('price') != $itemData['price']
                || $shoppingCartContent[$index]->get('options') != $itemData['options'])) {
            return -1;
        }
        return $index;
    }

    /**
     * @return string
     */
    public function getActionName()
    {
        if (!empty($_REQUEST['action'])) {
            return $_REQUEST['action'];
        }
        $action = $this->config['action'];
        if (!empty($_POST['item_id'])) {
            $action = 'add_to_cart';
        }
        if (isset($_POST['remove_by_index'])) {
            $action = 'remove';
        }
        return $action;
    }

    public function getUser()
    {
        if ($this->modx->user->isAuthenticated($this->modx->context->key)) {
            return $this->modx->user;
        }
        return null;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        $userId = $this->modx->getLoginUserID($this->modx->context->key);
        return $userId ?: 0;
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        if (!empty($_COOKIE[self::SESSION_KEY])) {
            return $_COOKIE[self::SESSION_KEY];
        }
        $sessionId = self::generateRandomString(26);
        setcookie(self::SESSION_KEY, $sessionId, time()+$this->config['lifeTime'], '/');
        return $sessionId;
    }

    /**
     * @param xPDOObject[] $shoppingCartContent
     * @param string $key
     * @return array
     */
    public static function getContentValues($shoppingCartContent, $key)
    {
        $outputArr = array_map(function($content) use ($key) {
            return $content->get($key) ?: '';
        }, $shoppingCartContent);
        return array_unique(array_merge($outputArr));
    }


    /**
     * @param xPDOObject $shoppingCartContentItem
     * @return int|float
     */
    public static function getContentPriceTotal($shoppingCartContentItem)
    {
        if (!$shoppingCartContentItem) {
            return 0;
        }
        $count = $shoppingCartContentItem->get('count');
        $total = $shoppingCartContentItem->get('price') * $count;
        $options = $shoppingCartContentItem->get('options') ?: [];
        foreach ($options as $option) {
            if (!isset($option['price'])) {
                continue;
            }
            $total += $option['price'] * $count;
        }
        return $total;
    }

    /**
     * @param int $length
     * @return string
     */
    public static function generateRandomString($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);
        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }
        return $result;
    }

}
