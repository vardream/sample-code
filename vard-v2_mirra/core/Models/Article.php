<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 29.09.2017
 * Time: 15:05
 */

namespace core\Models;

use core\DBMySQL;
use core\Registry;

/**
 * Class Article
 *
 * @package core\Models
 *
 * @property int       $id
 * @property string    $title
 * @property string    $notice
 * @property string    $body
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Article
{
  const ORDER_BY_ID = 'id';
  const ORDER_BY_TITLE = 'headline';

  private $fields = [
    'id' => null,
    'title' => null,
    'notice' => null,
    'body' => null,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * Article constructor.
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
        if (!is_null($value)) {
          $value = trim($value);
          if ($value != '') {
            $this->fields[$name] = $value;
          }
        }
        break;
      case 'notice':
      case 'body':
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
  public function page()
  {
    return Page::findContentPage(__CLASS__, $this->id);
  }

  /**
   * @param bool $checked
   * @param int  $from
   * @param null $limit
   * @return array
   */
  public function responses($checked = true, $from = 0, $limit = null)
  {
    return Response::selectContentResponses(__CLASS__, $this->id, $checked, $from, $limit);
  }

  /**
   * @param int  $from
   * @param null $limit
   * @return array
   */
  public function videos($from = 0, $limit = null)
  {
    return Video::selectContentVideo(__CLASS__, $this->id, $from, $limit);
  }

  /**
   * @return bool
   */
  public function save()
  {
    $result = false;

    if (!is_null($this->id) && !is_null($this->title) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
UPDATE mirra_articles SET title = :title, notice = :notice, body = :body WHERE id = :id
SQL;
      $stmt = $db->prepare($query);
      $stmt->bindValue(':title', $this->title);
      $stmt->bindValue(':notice', $this->notice);
      $stmt->bindValue(':body', $this->body);
      $stmt->bindValue(':id', $this->id);

      $result = $stmt->execute();

    }

    return $result;
  }

  /**
   * @param Article $article
   * @return Article|null
   */
  public static function create(Article $article)
  {
    $result = null;

    if (!is_null($article->title) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
INSERT INTO mirra_articles (title, notice, body, created_at) VALUES (:title, :notice, :body, NOW())
SQL;

      $stmt = $db->prepare($query);
      $stmt->bindValue(':title', $article->title);
      $stmt->bindValue(':notice', $article->notice);
      $stmt->bindValue(':body', $article->body);

      if ($stmt->execute()) {
        $result = self::find($db->lastInsertId());
      }
    }

    return $result;
  }

  /**
   * @param $id
   * @return Article|null
   */
  public static function find($id)
  {
    $reg = Registry::getInstance();
    $key = Registry::createKey(__CLASS__, $id);

    if (!$reg->existsObject($key) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
SELECT * FROM  mirra_articles WHERE id = $id
SQL;

      /** @var Article $object */
      foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $reg->addObject($key, $object);
      }
    }

    return $reg->existsObject($key) ? $reg->getObject($key) : null;
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
SELECT * FROM mirra_articles LIMIT $from,$number
SQL;

      $reg = Registry::getInstance();

      /** @var Article $object */
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
   * @param Page $page
   * @param null $handler_id
   * @param bool $published
   * @return int
   */
  public static function selectPageContentCount(Page $page, $handler_id = null, $published = true)
  {
    $result = 0;

    if ($db = DBMySQL::getInstance()->connection()) {

      $parent_id = $page->id;
      $content_type = __CLASS__;

      $statement = <<<SQL
SELECT COUNT(p.id) as total FROM mirra_articles a, mirra_pages p 
WHERE p.parent_id = :parent_id AND p.content_type = :content_type
SQL;
      if (!is_null($handler_id)) {
        $statement .= " AND p.handler_id = :handler_id ";
      }

      if ($published) {
        $statement .= " AND p.published = 1";
      }

      $statement .= " AND a.id = p.content_id";

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':parent_id', $parent_id);
      $stmt->bindValue(':content_type', $content_type);
      if (!is_null($handler_id)) {
        $stmt->bindValue(':handler_id', $handler_id);
      }

      if ($stmt->execute()) {
        $result = $stmt->fetchColumn(0);
      }
    }

    return $result;
  }

  /**
   * @param Page $page
   * @param null $handler_id
   * @param bool $published
   * @param null $order
   * @param int  $from
   * @param null $limit
   * @return array
   */
  public static function selectPageContent(Page $page, $handler_id = null, $published = true, $order = null, $from = 0, $limit = null)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $parent_id = $page->id;
      $content_type = __CLASS__;

      $statement = <<<SQL
SELECT a.* FROM mirra_articles a, mirra_pages p 
WHERE p.parent_id = :parent_id AND p.content_type = :content_type 
SQL;

      if (!is_null($handler_id)) {
        $statement .= " AND p.handler_id = :handler_id";
      }

      if ($published) {
        $statement .= " AND p.published = 1";
      }

      $statement .= " AND a.id = p.content_id";

      if (!is_null($order)) {
        switch ($order) {
          case self::ORDER_BY_ID:
            $statement .= " ORDER BY a.created_at DESC, a.id DESC";
            break;
          case self::ORDER_BY_TITLE :
            $statement .= " ORDER BY a.title ASC";
            break;
        }
      }

      if (!is_null($limit)) {
        $statement .= " LIMIT {$from},{$limit}";
      }

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':parent_id', $parent_id);
      $stmt->bindValue(':content_type', $content_type);
      if (!is_null($handler_id)) {
        $stmt->bindValue(':handler_id', $handler_id);
      }

      if ($stmt->execute()) {

        $reg = Registry::getInstance();

        /** @var Article $object */
        while ($object = $stmt->fetchObject(__CLASS__)) {
          $key = Registry::createKey(__CLASS__, $object->id);
          if (!$reg->existsObject($key)) {
            $reg->addObject($key, $object);
          }
          $result[] = $reg->getObject($key);
        }
      }
    }

    return $result;
  }

  /**
   * @param Page $page
   * @param null $handler_id
   * @param bool $published
   * @param int  $deep
   * @return int
   */
  public static function selectPageContentRecursiveCount(Page $page, $handler_id = null, $published = true, $deep = 3)
  {
    $result = 0;

    if ($db = DBMySQL::getInstance()->connection()) {

      $select = '';
      $left = '';
      $where = '';

      for ($i = 1; $i <= $deep; $i++) {

        if ($i == 1) {
          $select .= " mp{$i}.content_id";
          $where .= "mp{$i}.content_type = :content_type";

        } else {
          $select = "IFNULL(mp{$i}.content_id, {$select})";
          $where .= " OR mp{$i}.content_type = :content_type";
        }
        $left .= " LEFT JOIN mirra_pages mp{$i} ON mp{$i}.parent_id = mp" . ($i - 1) . ".id";
        if ($published) {
          $left .= " AND mp{$i}.published = 1";
        }
        if (!is_null($handler_id)) {
          $where .= " AND mp{$i}.handler_id = :handler_id";
        }
      }

      $statement = "SELECT COUNT(id) as total FROM mirra_articles WHERE id IN (SELECT DISTINCT {$select} AS id FROM mirra_pages mp0 {$left} WHERE mp0.id = :page_id AND ({$where}))";

      $class = __CLASS__;
      $page_id = $page->id;

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':content_type', $class);
      $stmt->bindValue(':page_id', $page_id);

      if (!is_null($handler_id)) {
        $stmt->bindValue(':handler_id', $handler_id);
      }

      if ($stmt->execute()) {
        $result = $stmt->fetchColumn(0);
      }
    }

    return $result;
  }

  /**
   * @param Page $page
   * @param null $handler_id
   * @param bool $published
   * @param null $order
   * @param int  $from
   * @param null $limit
   * @param int  $deep
   * @return array
   */
  public static function selectPageContentRecursive(Page $page, $handler_id = null, $published = true, $order = null, $from = 0, $limit = null, $deep = 3)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $select = '';
      $left = '';
      $where = '';

      for ($i = 1; $i <= $deep; $i++) {

        if ($i == 1) {
          $select .= " mp{$i}.content_id";
          $where .= "mp{$i}.content_type = :content_type";

        } else {
          $select = "IFNULL(mp{$i}.content_id, {$select})";
          $where .= " OR mp{$i}.content_type = :content_type";
        }
        $left .= " LEFT JOIN mirra_pages mp{$i} ON mp{$i}.parent_id = mp" . ($i - 1) . ".id";
        if ($published) {
          $left .= " AND mp{$i}.published = 1";
        }
        if (!is_null($handler_id)) {
          $where .= " AND mp{$i}.handler_id = :handler_id";
        }
      }

      $statement = "SELECT * FROM mirra_articles WHERE id IN (SELECT DISTINCT {$select} AS id FROM mirra_pages mp0 {$left} WHERE mp0.id = :page_id AND ({$where}))";

      switch ($order) {
        case self::ORDER_BY_ID :
          $statement .= " ORDER BY id ASC";
          break;
        case self::ORDER_BY_TITLE :
          $statement .= " ORDER BY title ASC";
          break;
      }

      if (!is_null($limit)) {
        $statement .= " LIMIT {$from},{$limit}";
      }

      $class = __CLASS__;
      $page_id = $page->id;

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':content_type', $class);
      $stmt->bindValue(':page_id', $page_id);

      if (!is_null($handler_id)) {
        $stmt->bindValue(':handler_id', $handler_id);
      }

      if ($stmt->execute()) {

        $reg = Registry::getInstance();

        /** @var Article $object */
        while ($object = $stmt->fetchObject(__CLASS__)) {
          $key = Registry::createKey(__CLASS__, $object->id);
          if (!$reg->existsObject($key)) {
            $reg->addObject($key, $object);
          }
          $result[] = $reg->getObject($key);
        }
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
SELECT COUNT(id) as total FROM mirra_articles
SQL;
      foreach ($db->query($statement, \PDO::FETCH_ASSOC) as $row) {
        $result = $row['total'];
      }

    }

    return $result;
  }

}