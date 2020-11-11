<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 30.09.2017
 * Time: 13:32
 */

namespace core\Models;

use core\DBMySQL;
use core\Registry;

/**
 * Class MenuItem
 *
 * @package core\Models
 *
 * @property int         $id
 * @property int         $menu_id
 * @property int         $page_id
 * @property int         $position
 * @property string|null $title
 * @property string|null $image
 * @property \DateTime   $created_at
 * @property \DateTime   $updated_at
 */
class MenuItem
{
  private $fields = [
    'id' => null,
    'menu_id' => null,
    'page_id' => null,
    'position' => 0,
    'title' => null,
    'image' => null,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * MenuItem constructor.
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
      case 'menu_id':
      case 'page_id':
        if (!is_null($value) && !is_int($value)) {
          $value = intval($value);
        }
        if (is_int($value) && ($value < 1)) {
          $value = null;
        }
        if (!is_null($value)) {
          $this->fields[$name] = $value;
        }
        break;
      case 'position':
        if (!is_int($value)) {
          $value = intval($value);
        }
        if ($value < 0) {
          $value = 0;
        }
        $this->fields[$name] = $value;
        break;
      case 'title':
      case 'image':
        if (!is_null($value)) {
          $value = trim($value);
          if ($value == '') {
            $value = null;
          }
        }
        $this->fields[$name] = $value;
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
   * @return Page|null
   */
  public function page()
  {
    return !is_null($this->page_id) ? Page::find($this->page_id) : null;
  }

  /**
   * @return string|null
   */
  public function path()
  {
    $page = $this->page();
    return !is_null($page) ? $page->path() : null;
  }

  /**
   * @return bool
   */
  public function save()
  {
    $result = false;

    if (!is_null($this->id)
      && !is_null($this->menu_id)
      && !is_null($this->page_id)
      && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
UPDATE mirra_menus_items SET menu_id = :menu_id, page_id = :page_id, position = :position, title = :title, image = :image WHERE id = :id
SQL;

      $stmt = $db->prepare($query);
      $stmt->bindParam(':menu_id', $this->menu_id);
      $stmt->bindParam(':page_id', $this->page_id);
      $stmt->bindParam(':position', $this->position);
      $stmt->bindParam(':title', $this->title);
      $stmt->bindParam(':image', $this->image);
      $stmt->bindParam(':id', $this->id);

      $result = $stmt->execute();

    }

    return $result;
  }

  /**
   * @param MenuItem $item
   * @return MenuItem|null
   */
  public static function create(MenuItem $item)
  {
    $result = null;

    if (!is_null($item->menu_id)
      && !is_null($item->page_id)
      && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
INSERT INTO mirra_menus_items (menu_id, page_id, position, title, image, created_at) VALUES (:menu_id, :page_id, :position, :title, :image, NOW())
SQL;
      $stmt = $db->prepare($query);
      $stmt->bindParam(':menu_id', $item->menu_id);
      $stmt->bindParam(':page_id', $item->page_id);
      $stmt->bindParam(':position', $item->position);
      $stmt->bindParam(':title', $item->title);
      $stmt->bindParam(':image', $item->image);

      if ($stmt->execute()) {
        $result = self::find($db->lastInsertId());
      }

    }

    return $result;
  }

  /**
   * @param $id
   * @return MenuItem|null
   */
  public static function find($id)
  {
    $reg = Registry::getInstance();
    $key = Registry::createKey(__CLASS__, $id);

    if (!$reg->existsObject($key) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
SELECT * FROM mirra_menus_items WHERE id = $id
SQL;

      /** @var MenuItem $object */
      foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $reg->addObject($key, $object);
      }
    }

    return $reg->existsObject($key) ? $reg->getObject($key) : null;
  }

  /**
   * @param      $menu_id
   * @param bool $published
   * @param bool $any
   * @return array
   */
  public static function selectMenuChildren($menu_id, $published = true, $any = false)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      if ($any) {
        $query = <<<SQL
SELECT m.* FROM mirra_menus_items m WHERE m.menu_id = $menu_id ORDER BY m.position ASC, m.title
SQL;
      } else {

        $query = <<<SQL
SELECT m.* FROM mirra_menus_items m, mirra_pages p WHERE m.menu_id = $menu_id AND p.id = m.page_id
SQL;
        if ($published) {
          $query .= " AND p.published = 1";
        }
        $query .= " ORDER BY m.position ASC, m.title ASC, p.menu_title ASC";
      }

      $reg = Registry::getInstance();

      /** @var MenuItem $object */
      foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $key = Registry::createKey(__CLASS__, $object->id);
        if (!$reg->existsObject($key)) {
          $reg->addObject($key, $object);
        }
        $result[] = $reg->getObject($key);
      }

    }

    return $result;
  }

}