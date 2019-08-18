<?php

/**
 * Class ShoppingCart
 * @author <andchir@gmail.com> Andchir
 */
class ShoppingCart {

    const EVENT_OnShoppingCartAddProduct = 'OnShoppingCartAddProduct';
    const SESSION_KEY = 'shoppingcart_sessid';

    public $modx;
    public $config = array();

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
     * @return string
     */
    public function actionResponse()
    {
        $output = '';
        $action = $this->getActionName();
        switch ($action) {
            case 'add_to_cart':

                $this->addToCartAction();

                if (!empty($_SERVER['HTTP_REFERER'])) {
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
                $this->removeByIndex($index);

                if (!empty($_SERVER['HTTP_REFERER'])) {
                    $this->modx->sendRedirect($_SERVER['HTTP_REFERER']);
                }

                break;
            case 'update':

                $this->updateAction();

                if (!empty($_SERVER['HTTP_REFERER'])) {
                    $this->modx->sendRedirect($_SERVER['HTTP_REFERER']);
                }

                break;
            case 'clean':

                $this->clean();

                if (!empty($_SERVER['HTTP_REFERER'])) {
                    $this->modx->sendRedirect($_SERVER['HTTP_REFERER']);
                }

                break;
        }
        return $output;
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
        } else {
            $where = [];
            if ($userId && $sessionId) {
                $where['createdby'] = $userId;
                $where['OR:session_id'] = $sessionId;
            } else if ($userId) {
                $where['createdby'] = $userId;
            } else if ($sessionId) {
                $where['session_id'] = $sessionId;
            }
            $shoppingCart = !empty($where)
                ? $this->modx->getObject('ShoppingCartItem', array_merge($where, ['type' => $this->config['contentType']]))
                : null;
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
     * @return string
     */
    public function renderOutput()
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
                $output .= $this->modx->getChunk($this->config['rowTpl'], array_merge($content->toArray(), [
                    'index' => $index,
                    'num' => $index + 1,
                    'priceTotal' => self::getContentPriceTotal($content)
                ]));
                $index++;
            }
        }
        if ($this->config['outerTpl']) {
            $output = $this->modx->getChunk($this->config['outerTpl'], [
                'wrapper' => $output,
                'priceTotal' => $priceTotal,
                'countTotal' => $countTotal,
                'currency' => $shoppingCart->get('currency')
            ]);
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
