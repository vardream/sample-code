<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 27.08.2017
 * Time: 10:37
 */

namespace core;

class Registry
{
  private static $instance;
  private $objects = [];

  /**
   * Registry constructor.
   */
  private function __construct()
  {
  }

  /**
   * @return Registry
   */
  public static function getInstance()
  {
    if (is_null(self::$instance)) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  public static function createKey($class, $id)
  {
    return $class . ':' . $id;
  }

  public function existsObject($key)
  {
    return array_key_exists($key, $this->objects);
  }

  public function addObject($key, $object)
  {
    $this->objects[$key] = $object;
  }

  public function delObject($key)
  {
    if ($this->existsObject($key)) {
      unset($this->objects[$key]);
    }
  }

  public function getObject($key)
  {
    return array_key_exists($key, $this->objects) ? $this->objects[$key] : null;
  }
}