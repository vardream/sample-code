<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 29.09.2017
 * Time: 19:59
 */

namespace core\Models;

use core\DBMySQL;
use core\Registry;

/**
 * Class Unit
 *
 * @package core\Models
 *
 * @property int       $id
 * @property string    $title
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Unit
{
  private $fields = [
    'id' => null,
    'title' => null,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * Unit constructor.
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
      case 'title':
        if (is_null($value)) {
          $value = '';
        } else {
          $value = trim($value);
          $value = preg_replace('/(\s{2,})/sim', ' ', $value);
        }
        if ($value != '') {
          $this->fields[$name] = $value;
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

    if (!is_null($this->id) && !is_null($this->title) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
UPDATE mirra_units SET title = :title WHERE id = :id
SQL;
      $stmt = $db->prepare($query);
      $stmt->bindParam(':title', $this->title);
      $stmt->bindParam(':id', $this->id);

      $result = $stmt->execute();

    }

    return $result;
  }

  /**
   * @param Unit $unit
   * @return Unit|null
   */
  public static function create(Unit $unit)
  {
    $result = null;

    if (!is_null($unit->title) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
INSERT INTO mirra_units (title, created_at) VALUES (:title, NOW())
SQL;
      $stmt = $db->prepare($query);
      $stmt->bindParam(':title', $unit->title);

      if ($stmt->execute()) {
        $result = self::find($db->lastInsertId());
      }
    }

    return $result;
  }


  /**
   * @param int $id
   * @return Unit|null
   */
  public static function find($id)
  {
    $reg = Registry::getInstance();
    $key = Registry::createKey(__CLASS__, $id);

    if (!$reg->existsObject($key) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
SELECT * FROM  mirra_units WHERE id = $id
SQL;

      /** @var Unit $object */
      foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $reg->addObject($key, $object);
      }
    }

    return $reg->existsObject($key) ? $reg->getObject($key) : null;
  }

  /**
   * @param $title
   * @return Unit|null
   */
  public static function selectTitle($title)
  {
    $key = null;
    $reg = Registry::getInstance();

    if ($db = DBMySQL::getInstance()->connection()) {

      $query = <<<SQL
SELECT * FROM mirra_units WHERE title = :title LIMIT 1
SQL;

      $stmt = $db->prepare($query);
      $stmt->bindValue(':title', trim($title));

      if ($stmt->execute()) {
        while ($object = $stmt->fetchObject(__CLASS__)) {
          $key = Registry::createKey(__CLASS__, $object->id);
          if (!$reg->existsObject($key)) {
            $reg->addObject($key, $object);
          }
        }
      }

    }

    return !is_null($key) ? $reg->getObject($key) : null;
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
SELECT * FROM mirra_units LIMIT $from,$number
SQL;

      $reg = Registry::getInstance();

      /** @var Unit $object */
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
SELECT COUNT(id) as total FROM mirra_units
SQL;
      foreach ($db->query($statement, \PDO::FETCH_ASSOC) as $row) {
        $result = $row['total'];
      }

    }

    return $result;
  }

}