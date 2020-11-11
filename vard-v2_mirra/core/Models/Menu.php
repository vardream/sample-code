<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 30.09.2017
 * Time: 12:52
 */

namespace core\Models;

use core\DBMySQL;
use core\Registry;

/**
 * Class Menu
 *
 * @package core\Models
 *
 * @property int       $id
 * @property string    $name
 * @property string    $title
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Menu
{
  private $fields = [
    'id' => null,
    'name' => null,
    'title' => null,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * Menu constructor.
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
        if (is_null($value)) {
          $value = '';
        } else {
          $value = trim($value);
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

  public function children($published = true, $any = false)
  {
    return MenuItem::selectMenuChildren($this->id, $published, $any);
  }

  /**
   * @return bool
   */
  public function save()
  {
    $result = false;
    if (!is_null($this->id)
      && !is_null($this->name)
      && !is_null($this->title)
      && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
UPDATE mirra_menus SET name = :name, title = :title WHERE id = :id
SQL;

      $stmt = $db->prepare($query);
      $stmt->bindParam(':name', $this->name);
      $stmt->bindParam(':title', $this->title);
      $stmt->bindParam(':id', $this->id);

      $result = $stmt->execute();
    }

    return $result;
  }

  /**
   * @param Menu $menu
   * @return Menu|null
   */
  public static function create(Menu $menu)
  {
    $result = null;
    if (!is_null($menu->name)
      && !is_null($menu->title)
      && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
INSERT INTO mirra_menus (name, title, created_at) VALUES (:name, :title, NOW())
SQL;
      $stmt = $db->prepare($query);
      $stmt->bindParam(':name', $menu->name);
      $stmt->bindParam(':title', $menu->title);

      if ($stmt->execute()) {
        $result = self::find($db->lastInsertId());
      }
    }
    return $result;
  }

  /**
   * @param $id
   * @return Menu|null
   */
  public static function find($id)
  {
    $reg = Registry::getInstance();
    $key = Registry::createKey(__CLASS__, $id);

    if (!$reg->existsObject($key) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
SELECT * FROM mirra_menus WHERE id = $id
SQL;

      /** @var Menu $object */
      foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $reg->addObject($key, $object);
      }
    }

    return $reg->existsObject($key) ? $reg->getObject($key) : null;
  }

  /**
   * @param $name
   * @return Menu|null
   */
  public static function findName($name)
  {
    $result = null;

    if ($db = DBMySQL::getInstance()->connection()) {

      $query = <<<SQL
SELECT * FROM mirra_menus WHERE name = :name
SQL;
      $stmt = $db->prepare($query);
      $stmt->bindParam(':name', $name);

      if ($stmt->execute()) {

        $reg = Registry::getInstance();

        /** @var Menu $object */
        while ($object = $stmt->fetchObject(__CLASS__)) {
          $key = Registry::createKey(__CLASS__, $object->id);
          if (!$reg->existsObject($key)) {
            $reg->addObject($key, $object);
          }
          $result = $reg->getObject($key);
        }
      }

    }

    return $result;
  }
}