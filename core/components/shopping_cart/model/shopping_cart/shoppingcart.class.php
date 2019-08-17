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
            'currency' => 'USD'
        ], $config);

        $this->modx->addPackage('shopping_cart', $this->config['modelPath']);
    }

    /**
     * @return string
     */
    public function actionResponse()
    {
        $output = '';
        $action = $_POST['action'] ?? '';
        if (!$action) {
            $action = !empty($_POST['item_id']) ? 'add_to_cart' : 'print';
        }
        switch ($action) {
            case 'add_to_cart':

                $itemData = $this->modx->invokeEvent( self::EVENT_OnShoppingCartAddProduct, ['data' => $_POST]);
                if (!empty($itemData) && !empty($itemData[0])) {
                    $itemData = current($itemData);
                    $shoppingCart = $this->getShoppingCart(true);
                    $shoppingCartContent = $shoppingCart->getMany('Content');
                    $contentIndex = $this->getContentIndex($shoppingCartContent, $itemData);
                    if ($contentIndex > -1) {
                        $count = $shoppingCartContent[$contentIndex]->get('count');
                        $shoppingCartContent[$contentIndex]->set('count', $count + $itemData['count']);
                    } else {
                        $shoppingCartContent[] = $this->modx->newObject('ShoppingCartContent', [
                            'item_id' => $itemData['id'],
                            'title' => $itemData['title'],
                            'name' => $itemData['name'],
                            'price' => $itemData['price'],
                            'count' => $itemData['count'],
                            'uri' => $itemData['uri'],
                            'options' => $itemData['options']
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

                $shoppingCart = $this->getShoppingCart();
                if (!$shoppingCart) {
                    return '';
                }
                $shoppingCartContent = $shoppingCart->getMany('Content');
                $totalPrice = $this->getTotalPrice($shoppingCartContent);
                $totalCount = $this->getTotalCount($shoppingCartContent);

                // TODO: render shopping cart content

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
