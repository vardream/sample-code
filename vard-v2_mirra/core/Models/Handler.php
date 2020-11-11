<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 29.09.2017
 * Time: 13:09
 */

namespace core\Models;

use core\DBMySQL;
use core\Registry;

/**
 * Class Handler
 *
 * @package core\Models
 *
 * @property int       $id
 * @property string    $name
 * @property string    $title
 * @property string    $controller
 * @property string    $method
 * @property string    $template
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Handler
{
  private $fields = [
    'id' => null,
    'name' => null,
    'title' => null,
    'controller' => null,
    'method' => null,
    'template' => null
  ];

  /**
   * Handler constructor.
   *
   * @param array $fields
   */
  public function __construct($fields = [])
  {
    foreach ($fields as $field => $value) {
      $this->$field = $value;
    }
  }

  /**
   * @param $name
   * @return mixed|null
   */
  public function __get($name)
  {
    return array_key_exists($name, $this->fields) ? $this->fields[$name] : null;
  }

  /**
   * @param string $name
   * @param mixed  $value
   */
  public function __set($name, $value)
  {
    switch ($name) {
      case 'id':
        if (is_null($this->fields[$name]) && is_numeric($value)) {
          $value = intval($value);
          if ($value > 0) {
            $this->fields[$name] = $value;
          }
        }
        break;
      case 'name':
      case 'title':
      case 'controller':
      case 'method':
      case 'template':
        if (!is_null($value)) {
          $value = trim($value);
          if ($value != '') {
            $this->fields[$name] = $value;
          }
        }
        break;
      case "created_at":
      case "updated_at":
        if ($value instanceof \DateTime) {
          $this->fields[$name] = $value;
        } elseif (is_string($value)) {
          $this->fields[$name] = \DateTime::createFromFormat("Y-m-d H:i:s", trim($value));
          if (is_bool($this->fields[$name])) {
            $this->fields[$name] = null;
          }
        } else {
          $this->fields[$name] = null;
        }
        break;
      default:
        break;
    }
  }

  /**
   * @return bool
   */
  public function save()
  {
    $result = false;

    if (!is_null($this->id) && !is_null($this->name) && (!is_null($this->title)) && !is_null($this->controller) && !is_null($this->method) && !is_null($this->template) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
UPDATE mirra_handlers SET name = :name, title = :title, controller = :controller, method = :method, template = :template WHERE id = :id
SQL;

      $stmt = $db->prepare($query);
      $stmt->bindParam(':name', $this->name);
      $stmt->bindParam(':title', $this->title);
      $stmt->bindParam(':controller', $this->controller);
      $stmt->bindParam(':method', $this->method);
      $stmt->bindParam(':template', $this->template);

      $result = $stmt->execute();
    }

    return $result;
  }

  /**
   * @param Handler $handler
   * @return Handler|null
   */
  public static function create(Handler $handler)
  {
    $result = null;

    if (!is_null($handler->name) && (!is_null($handler->title)) && !is_null($handler->controller) && !is_null($handler->method) && !is_null($handler->template) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
INSERT INTO mirra_handlers (name, title, controller, method, template, created_at) VALUES (:name, :title, :controller, :method, :template, NOW())
SQL;

      $stmt = $db->prepare($query);
      $stmt->bindParam(':name', $handler->name);
      $stmt->bindParam(':title', $handler->title);
      $stmt->bindParam(':controller', $handler->controller);
      $stmt->bindParam(':method', $handler->method);
      $stmt->bindParam(':template', $handler->template);

      if ($stmt->execute()) {
        $result = self::find($db->lastInsertId());
      }
    }

    return $result;
  }

  /**
   * @param $id
   * @return Handler|null
   */
  public static function find($id)
  {
    $reg = Registry::getInstance();
    $key = Registry::createKey(__CLASS__, $id);

    if (!$reg->existsObject($key)) {

      if ($db = DBMySQL::getInstance()->connection()) {

        $query = <<<SQL
SELECT * FROM mirra_handlers WHERE id = $id
SQL;
        /** @var Handler $object */
        foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
          $reg->addObject($key, $object);
        }
      }
    }

    return $reg->existsObject($key) ? $reg->getObject($key) : null;
  }


  /**
   * @param $name
   * @return Handler|null
   */
  public static function findName($name)
  {
    $result = null;

    if ($db = DBMySQL::getInstance()->connection()) {

      $query = <<<SQL
SELECT * FROM mirra_handlers WHERE name = '$name';
SQL;

      $reg = Registry::getInstance();
      $key = null;

      /** @var Handler $object */
      foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $key = Registry::createKey(__CLASS__, $object->id);
        if (!$reg->existsObject($key)) {
          $reg->addObject($key, $object);
        }
      }

      if (!is_null($key)) {
        $result = $reg->getObject($key);
      }
    }

    return $result;
  }

  /**
   * @param int $from
   * @param int $number
   * @return array
   */
  public static function selectItems($from = 0, $number = 10)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT * FROM mirra_handlers LIMIT $from,$number
SQL;

      $reg = Registry::getInstance();

      /** @var Handler $object */
      foreach ($db->query($statement, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $key = Registry::createKey(__CLASS__, $object->id);

        if (!$reg->existsObject($key)) {
          $reg->addObject($key, $object);
        }

        $result[] = $reg->getObject($key);
      }

    }

    return $result;
  }

  /**
   * @return int
   */
  public static function countItems()
  {
    $result = 0;

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT COUNT(id) as total FROM mirra_handlers
SQL;
      foreach ($db->query($statement, \PDO::FETCH_ASSOC) as $row) {
        $result = $row['total'];
      }

    }

    return $result;
  }

}