<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 03.11.2017
 * Time: 20:21
 */

namespace core\Models;

use Symfony\Component\HttpFoundation\Session\Session;
use core\Registry;

/**
 * Class Cart
 *
 * @package core\Models
 */
class Cart implements \Serializable
{
  private static $instance;

  private $items = [];

  private function __construct()
  {
    $this->load();
  }

  /**
   * @return Cart
   */
  public static function getInstance()
  {
    if (is_null(self::$instance)) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  /**
   * @return string
   */
  public function serialize()
  {
    return serialize($this->items);
  }

  /**
   * @param string $serialized
   */
  public function unserialize($serialized)
  {
    $this->items = unserialize($serialized);
  }


  /**
   * @param Product $product
   */
  public function addItem(Product $product)
  {
    $key = Registry::createKey(get_class($product), $product->id);
    if (!array_key_exists($key, $this->items)) {

      $price = $product->price;
      $discount = $product->discount();

      if ($discount > 0 && $price > $discount) {
        $discount = $price - $discount;
      }

      $volume = is_null($product->package()) ? '' : $product->package()->title . ' ';
      $volume .= $product->quantity . ' ' . $product->unit()->title;

      $this->items[$key] = [
        'id' => $product->id,
        'title' => $product->title,
        'nomenclature' => $product->nomenclature,
        'price' => $price,
        'discount' => $discount,
        'quantity' => 1,
        'volume' => $volume
      ];

      $this->save();
    }
  }

  /**
   * @param int $id
   * @return array|null
   */
  public function getItem($id)
  {
    $result = null;

    $key = Registry::createKey('core\Models\Product', $id);
    if (array_key_exists($key, $this->items)) {
      $result = $this->items[$key];
    }

    return $result;
  }

  /**
   * @param $id
   * @return bool
   */
  public function existItem($id)
  {
    $key = Registry::createKey('core\Models\Product', $id);
    return array_key_exists($key, $this->items);
  }

  /**
   * @param $id
   */
  public function delItem($id)
  {
    $key = Registry::createKey('core\Models\Product', $id);
    if (array_key_exists($key, $this->items)) {
      unset($this->items[$key]);
      $this->save();
    }
  }

  /**
   * Устанавливает количество единиц товара
   *
   * @param $id
   * @param $quantity
   * @return bool
   */
  public function setItemQuantity($id, $quantity)
  {
    $result = false;

    $quantity = intval($quantity);

    if ($quantity > 0) {

      $key = Registry::createKey('core\Models\Product', $id);

      if (array_key_exists($key, $this->items)) {
        $this->items[$key]['quantity'] = $quantity;
        $this->save();
        $result = true;
      }
    }

    return $result;
  }

  /**
   * @return int
   */
  public function total()
  {
    return count($this->items);
  }

  /**
   * @return array
   */
  public function items()
  {
    return array_values($this->items);
  }

  public function sum()
  {
    $result = 0;
    foreach ($this->items as $item) {
      $result += ($item['price'] - $item['discount']) * $item['quantity'];
    }

    return $result;
  }

  public function reset()
  {
    $this->items = [];
    $this->save();
  }

  private function load()
  {
    /** @var Session $session */
    $session = isset($GLOBALS['session']) ? $GLOBALS['session'] : null;

    if (!is_null($session)) {

      if (!$session->isStarted()) {
        $session->start();
      }

      if ($session->has('cart')) {
        $this->unserialize($session->get('cart'));
      } else {
        $session->set('cart', $this->serialize());
      }

    }
  }

  private function save()
  {
    /** @var Session $session */
    $session = isset($GLOBALS['session']) ? $GLOBALS['session'] : null;

    if (!is_null($session)) {

      if (!$session->isStarted()) {
        $session->start();
      }

      $session->set('cart', $this->serialize());
    }
  }
}