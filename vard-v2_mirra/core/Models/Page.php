<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 29.09.2017
 * Time: 10:48
 */

namespace core\Models;

use core\DBMySQL;
use core\Registry;

/**
 * Class Page
 *
 * @package core\Models
 *
 * @property int       $id
 * @property int       $parent_id
 * @property string    $uri
 * @property bool      $published
 * @property string    $meta_title
 * @property string    $meta_keywords
 * @property string    $meta_description
 * @property string    $menu_title
 * @property int       $handler_id
 * @property int       $content_id
 * @property string    $content_type
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Page
{
  private $fields = [
    'id' => null,
    'parent_id' => null,
    'uri' => null,
    'published' => false,
    'meta_title' => null,
    'meta_keywords' => null,
    'meta_description' => null,
    'menu_title' => null,
    'handler_id' => null,
    'content_id' => null,
    'content_type' => null,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * Page constructor.
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
      case 'parent_id':
      case 'handler_id':
      case 'content_id':
        if (!is_null($value) && !is_int($value)) {
          $value = intval($value);
        }
        if (is_int($value) && ($value < 1)) {
          $value = null;
        }
        $this->fields[$name] = $value;
        break;
      case 'uri':
        if (is_null($value)) {
          $value = '';
        } else {
          $value = trim($value);
        }
        if ($value != '/') {
          $value = trim($value, " /");
        } else {
          $this->fields['parent_id'] = null;
        }
        if ($value != '') {
          $this->fields[$name] = $value;
        }
        break;
      case 'published':
        $this->fields[$name] = boolval($value);
        break;
      case 'meta_title':
      case 'meta_keywords':
      case 'meta_description':
      case 'menu_title':
      case 'content_type':
        if (!is_null($value)) {
          $value = trim($value);
        }
        if ($value == '') {
          $value = null;
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
  public function parent()
  {
    return !is_null($this->parent_id) ? Page::find($this->parent_id) : null;
  }

  /**
   * @param bool  $published
   * @param array $filter
   * @return array
   */
  public function children($published = true, $filter = [])
  {
    $result = [];

    if ($this->uri == '/') {
      $query = <<<SQL
SELECT * FROM mirra_pages WHERE parent_id IS NULL AND id != $this->id
SQL;
    } else {
      $query = <<<SQL
SELECT * FROM mirra_pages WHERE parent_id = $this->id
SQL;
    }

    if ($published) {
      $query .= " AND published = 1";
    }

    if (!empty($filter)) {
      foreach ($filter as $field => $value) {
        if (is_null($value)) {
          $value = 'NULL';
        } else {
          if (is_string($value)) {
            $value = "'$value'";
          }
        }
        $query .= " AND {$field} = {$value}";
      }
    }

    if ($db = DBMySQL::getInstance()->connection()) {

      $reg = Registry::getInstance();

      /** @var Page $object */
      foreach ($db->query($query, \PDo::FETCH_CLASS, __CLASS__) as $object) {

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
   * @param      $content_type
   * @param null $handler_id
   * @param bool $published
   * @param null $order
   * @param int  $from
   * @param null $limit
   * @param int  $deep
   * @return array
   */
  public function childrenContentRecursive($content_type, $handler_id = null, $published = true, $order = null, $from = 0, $limit = null, $deep = 3)
  {
    $result = [];

    try {
      $class = new \ReflectionClass($content_type);
      $method_name = 'selectPageContentRecursive';
      if ($class->hasMethod($method_name)) {
        $method = $class->getMethod($method_name);
        if ($method->isStatic()) {
          $result = $method->invoke(null, $this, $handler_id, $published, $order, $from, $limit, $deep);
        }
      }
//      if ($class->hasMethod('selectPageContentRecursive')) {
//        $object = $class->name;
//        $result = $object::selectPageContentRecursive($this, $handler_id, $published, $order, $from, $limit, $deep);
//      }
    } catch (\Exception $exception) {

    }

    return $result;
  }

  /**
   * @param      $content_type
   * @param null $handler_id
   * @param bool $published
   * @return int
   */
  public function childrenContentRecursiveCount($content_type, $handler_id = null, $published = true)
  {
    $result = 0;

    try {
      $class = new \ReflectionClass($content_type);
      $method_name = 'selectPageContentRecursiveCount';
      if ($class->hasMethod($method_name)) {
        $method = $class->getMethod($method_name);
        if ($method->isStatic()) {
          $result = $method->invoke(null, $this, $handler_id, $published);
        }
      }
    } catch (\Exception $exception) {

    }

    return $result;
  }

  /**
   * @param      $content_type
   * @param null $handler_id
   * @param bool $published
   * @param null $order
   * @param int  $from
   * @param null $limit
   * @return array
   */
  public function childrenContent($content_type, $handler_id = null, $published = true, $order = null, $from = 0, $limit = null)
  {
    $result = [];

    try {
      $class = new \ReflectionClass($content_type);
      $method_name = 'selectPageContent';
      if ($class->hasMethod($method_name)) {
        $method = $class->getMethod($method_name);
        if ($method->isStatic()) {
          $result = $method->invoke(null, $this, $handler_id, $published, $order, $from, $limit);
        }
      }
    } catch (\Exception $exception) {

    }

    return $result;
  }

  /**
   * @param      $content_type
   * @param null $handler_id
   * @param bool $published
   * @return int
   */
  public function childrenContentCount($content_type, $handler_id = null, $published = true)
  {
    $result = 0;

    try {
      $class = new \ReflectionClass($content_type);
      $method_name = 'selectPageContentCount';
      if ($class->hasMethod($method_name)) {
        $method = $class->getMethod($method_name);
        if ($method->isStatic()) {
          $result = $method->invoke(null, $this, $handler_id, $published);
        }
      }
    } catch (\Exception $exception) {

    }

    return $result;
  }

  /**
   * @return string
   */
  public function path()
  {
    $result = trim($this->uri, '/');

    if (!is_null($this->parent_id) && ($parent = $this->parent())) {
      $result = trim($parent->path(), '/') . '/' . $result;
    }

    return '/' . $result;
  }

  /**
   * @return object|null
   */
  public function content()
  {
    $result = null;
    if (!is_null($this->content_type) && !is_null($this->content_id)) {
      try {
        $class = new \ReflectionClass($this->content_type);
        $method_name = 'find';
        if ($class->hasMethod($method_name)) {
          $method = $class->getMethod($method_name);
          if ($method->isStatic()) {
            $result = $method->invoke(null, $this->content_id);
          }
        }
      } catch (\Exception $exception) {

      }
    }
    return $result;
  }

  /**
   * @return Handler|null
   */
  public function handler()
  {
    return !is_null($this->handler_id) ? Handler::find($this->handler_id) : null;
  }

  /**
   * @return bool
   */
  public function save()
  {
    $result = false;
    $id = $this->id;
    $uri = $this->uri;

    if (!is_null($uri) && $id > 0) {
      if ($db = DBMySQL::getInstance()->connection()) {

        $query = <<<SQL
UPDATE mirra_pages SET parent_id = :parent_id, uri = :uri, published = :published, meta_title = :meta_title, meta_keywords = :meta_keywords, meta_description = :meta_description, menu_title = :menu_title, handler_id = :handler_id, content_id = :content_id, content_type = :content_type WHERE id = :id 
SQL;
        $stmt = $db->prepare($query);
        $stmt->bindValue(':parent_id', $this->parent_id);
        $stmt->bindValue(':uri', $uri);
        $stmt->bindValue(':published', $this->published);
        $stmt->bindValue(':meta_title', $this->meta_title);
        $stmt->bindValue(':meta_keywords', $this->meta_keywords);
        $stmt->bindValue(':meta_description', $this->meta_description);
        $stmt->bindValue(':menu_title', $this->menu_title);
        $stmt->bindValue(':handler_id', $this->handler_id);
        $stmt->bindValue(':content_id', $this->content_id);
        $stmt->bindValue(':content_type', $this->content_type);
        $stmt->bindValue(':id', $id);

        $result = $stmt->execute();
      }
    }

    return $result;
  }

  /**
   * @param Page $page
   * @return Page|null
   */
  public static function create(Page $page)
  {
    $result = null;

    if ($uri = $page->uri) {

      if ($db = DBMySQL::getInstance()->connection()) {

        $query = <<<SQL
INSERT INTO mirra_pages (parent_id, uri, published, meta_title, meta_keywords, meta_description, menu_title, handler_id, content_id, content_type, created_at) VALUES (:parent_id, :uri, :published, :meta_title, :meta_keywords, :meta_description, :menu_title, :handler_id, :content_id, :content_type, NOW())
SQL;
        $stmt = $db->prepare($query);
        $stmt->bindValue(':parent_id', $page->parent_id);
        $stmt->bindValue(':uri', $page->uri);
        $stmt->bindValue(':published', $page->published);
        $stmt->bindValue(':meta_title', $page->meta_title);
        $stmt->bindValue(':meta_keywords', $page->meta_keywords);
        $stmt->bindValue(':meta_description', $page->meta_description);
        $stmt->bindValue(':menu_title', $page->menu_title);
        $stmt->bindValue(':handler_id', $page->handler_id);
        $stmt->bindValue(':content_id', $page->content_id);
        $stmt->bindValue(':content_type', $page->content_type);

        try {
          if ($stmt->execute()) {
            $result = self::find($db->lastInsertId());
          }
        } catch (\Exception $e) {

        }
      }
    }
    return $result;
  }

  /**
   * @param $id
   * @return Page|null
   */
  public static function find($id)
  {
    $key = Registry::createKey(__CLASS__, $id);
    $reg = Registry::getInstance();

    if (!$reg->existsObject($key)) {
      if ($db = DBMySQL::getInstance()->connection()) {
        $query = <<<SQL
SELECT * FROM mirra_pages WHERE id = $id
SQL;
        /** @var Page $object */
        foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
          $reg->addObject($key, $object);
        }
      }
    }

    return $reg->getObject($key);
  }

  /**
   * @param $content_type
   * @param $content_id
   * @return Page|null
   */
  public static function findContentPage($content_type, $content_id)
  {
    $result = null;

    if ($db = DBMySQL::getInstance()->connection()) {

      $query = <<<SQL
SELECT * FROM mirra_pages WHERE content_type = :content_type AND content_id = :content_id
SQL;

      $stmt = $db->prepare($query);
      $stmt->bindParam(':content_type', $content_type);
      $stmt->bindParam(':content_id', $content_id);

      if ($stmt->execute()) {
        /** @var Page $object */
        $object = $stmt->fetchObject(__CLASS__);

        $reg = Registry::getInstance();
        $key = Registry::createKey(__CLASS__, $object->id);

        if (!$reg->existsObject($key)) {
          $reg->addObject($key, $object);
        }

        $result = $reg->getObject($key);
      }

    }

    return $result;
  }

  /**
   * @param      $url
   * @param bool $published
   * @return Page|null
   */
  public static function route($url, $published = true)
  {
    $result = null;

    $path = explode('/', trim($url, "/"));

    $n = count($path);

    if ($n > 0) {
      $from = '';
      $where = '';

      for ($i = 0; $i < $n; $i++) {
        if ($i == 0) {
          $parent_id = null;
          if ($path[0] == '') {
            $path[0] = '/';
          } else {
            if ($n >= 1) {
              if ($parent = self::route('/')) {
                $parent_id = $parent->id;
              }
            }
          }
          $from .= "mirra_pages p{$i}";

          if (is_null($parent_id)) {
            $where .= "p{$i}.uri = '" . $path[$i] . "' AND p{$i}.parent_id IS NULL";
          } else {
            $where .= "p{$i}.uri = '" . $path[$i] . "' AND (p{$i}.parent_id IS NULL OR p{$i}.parent_id = {$parent_id})";
          }

        } else {
          $from .= ", mirra_pages p{$i}";
          $where .= " AND p{$i}.uri = '" . $path[$i] . "' AND p{$i}.parent_id = p" . ($i - 1) . ".id";
        }
        if ($published) {
          $where .= " AND p{$i}.published = 1";
        }
      }

      $query = "SELECT p" . ($n - 1) . ".* FROM $from WHERE $where";

      if ($db = DBMySQL::getInstance()->connection()) {
        /** @var Page $object */
        foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
          $key = Registry::createKey(__CLASS__, $object->id);
          $reg = Registry::getInstance();
          if (!$reg->existsObject($key)) {
            $reg->addObject($key, $object);
          }
          $result = $reg->getObject($key);
        }
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
SELECT * FROM mirra_pages LIMIT $from,$number
SQL;

      $reg = Registry::getInstance();

      /** @var Page $object */
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
SELECT COUNT(id) as total FROM mirra_pages
SQL;
      foreach ($db->query($statement, \PDO::FETCH_ASSOC) as $row) {
        $result = $row['total'];
      }

    }

    return $result;
  }

}