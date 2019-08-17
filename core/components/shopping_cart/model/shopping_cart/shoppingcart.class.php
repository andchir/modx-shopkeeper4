<?php

class ShoppingCart {

    const EVENT_OnShoppingCartAddProduct = 'OnShoppingCartAddProduct';
    const SESSION_KEY = 'shoppingcart_sessid';

    public $modx;
    public $config = array();

    public function __construct(modX &$modx, array $config = []) {

        $this->modx =& $modx;
        $basePath = $this->modx->getOption('shopping_cart.core_path', $config,$this->modx->getOption('core_path') . 'components/shopping_cart/');
        $assetsUrl = $this->modx->getOption('shopping_cart.assets_url', $config,$this->modx->getOption('assets_url') . 'components/shopping_cart/');
        $lifeTime = (int) $this->modx->getOption('shopping_cart.lifetime', null, 172800);
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
            'lifeTime' => $lifeTime,
            'currency' => 'USD',
            'rowTpl' => 'shoppingCart_rowTpl',
            'outerTpl' => 'shoppingCart_outerTpl',
            'emptyTpl' => 'shoppingCart_emptyTpl',
            'action' => ''
        ], $config);

        $this->modx->addPackage('shopping_cart', $this->config['modelPath']);
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

                $itemData = $this->modx->invokeEvent( self::EVENT_OnShoppingCartAddProduct, ['data' => $_POST]);
                if (!empty($itemData) && !empty($itemData[0]) && !empty($itemData[0]['title'])) {
                    $itemData = current($itemData);
                    $shoppingCart = $this->getShoppingCart(true);
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
                }

                if (!empty($_SERVER['HTTP_REFERER'])) {
                    $this->modx->sendRedirect($_SERVER['HTTP_REFERER']);
                }

                break;
            case 'print':

                $output = $this->renderOutput();

                break;
            case 'remove':

                $index = isset($_REQUEST['index']) ? intval($_REQUEST['index']) : 0;
                $shoppingCart = $this->getShoppingCart();
                if (!$shoppingCart) {
                    return '';
                }
                $shoppingCartContent = $shoppingCart->getMany('Content');
                if (empty($shoppingCartContent)) {
                    return '';
                }
                $shoppingCartContent = array_merge($shoppingCartContent);
                if (isset($shoppingCartContent[$index])) {
                    $shoppingCartContent[$index]->remove();
                }

                if (!empty($_SERVER['HTTP_REFERER'])) {
                    $this->modx->sendRedirect($_SERVER['HTTP_REFERER']);
                }

                break;
        }
        return $output;
    }

    /**
     * @param bool $create
     * @return null|object
     */
    public function getShoppingCart($create = false)
    {
        $user = $this->getUser();
        $sessionId = $this->getSessionId();
        $shoppingCart = $this->modx->getObject('ShoppingCartItem', [
            'session_id' => $sessionId
        ]);
        if (!$shoppingCart && $create) {
            $shoppingCart = $this->modx->newObject('ShoppingCartItem', [
                'session_id' => $sessionId,
                'createdon' => strftime('%Y-%m-%d %H:%M:%S'),
                'editedon' => strftime('%Y-%m-%d %H:%M:%S'),
                'currency' => $this->config['currency']
            ]);
            if ($user) {
                $shoppingCart->addOne($user);
            }
            $shoppingCart->save();
        }
        return $shoppingCart;
    }

    /**
     * @return string
     */
    public function renderOutput()
    {
        $shoppingCart = $this->getShoppingCart();
        $shoppingCartContent = $shoppingCart ? $shoppingCart->getMany('Content') : [];
        if (empty($shoppingCartContent)) {
            return $this->modx->getChunk($this->config['emptyTpl']);
        }
        $priceTotal = $this->getTotalPrice($shoppingCartContent);
        $countTotal = $this->getTotalCount($shoppingCartContent);

        $output = '';

        $index = 0;
        foreach ($shoppingCartContent as $content) {
            $output .= $this->modx->getChunk($this->config['rowTpl'], array_merge($content->toArray(), [
                'index' => $index,
                'num' => $index + 1,
                'priceTotal' => 0
            ]));
            $index++;
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
            $total += $content->get('price') * $content->get('count');
            // TODO: get price from options
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
