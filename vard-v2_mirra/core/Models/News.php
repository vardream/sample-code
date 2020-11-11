<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 22.12.2017
 * Time: 14:35
 */

namespace core\Models;

use core\Registry;
use core\DBMySQL;

/**
 * Class News
 *
 * @package core\Models
 *
 * @property int       $id
 * @property \DateTime $published
 * @property string    $title
 * @property string    $notice
 * @property string    $body
 * @property string    $type
 * @property string    $link
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class News
{
  const TYPE_TEXT = 'text';
  const TYPE_EXTERNAL = 'external';
  const TYPE_INTERNAL = 'internal';

  const ORDER_BY_DATE = 'date';
  const ORDER_BY_TITLE = 'title';

  private $fields = [
    'id' => null,
    'published' => null,
    'title' => null,
    'notice' => null,
    'body' => null,
    'type' => self::TYPE_TEXT,
    'link' => null,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * News constructor.
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
      case 'type':
        if (($value != self::TYPE_EXTERNAL) && ($value != self::TYPE_INTERNAL)) {
          $this->fields[$name] = self::TYPE_TEXT;
        } else {
          $this->fields[$name] = $value;
        }
        break;
      case 'link':
        if (is_string($value)) {
          $value = trim($value);
          if ($value != '') {
            $this->fields[$name] = $value;
          }
        }
        break;
      case "published":
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
   * @return bool
   */
  public function save()
  {
    $result = false;

    $valid = !is_null($this->id) && !is_null($this->published) && !is_null($this->title);

    if ($valid && ($this->type != self::TYPE_TEXT)) {
      $valid = !is_null($this->link);
    }

    if ($valid && ($db = DBMySQL::getInstance()->connection())) {

      /** @var \DateTime $published */
      $published = $this->published;

      $query = <<<SQL
UPDATE mirra_news 
SET published = :published, title = :title, notice = :notice, body = :body, type = : type, link = :link
WHERE id = :id
SQL;
      $stmt = $db->prepare($query);
      $stmt->bindValue(':published', $published->format('Y-m-d H:i:s'));
      $stmt->bindValue(':title', $this->title);
      $stmt->bindValue(':notice', $this->notice);
      $stmt->bindValue(':body', $this->body);
      $stmt->bindValue(':type', $this->type);
      $stmt->bindValue(':link', $this->link);
      $stmt->bindValue(':id', $this->id);

      $result = $stmt->execute();

    }

    return $result;
  }

  /**
   * @param News $news
   * @return News|null
   */
  public static function create(News $news)
  {
    $result = null;

    $valid = !is_null($news->published) && !is_null($news->title);

    if ($valid && ($news->type != self::TYPE_TEXT)) {
      $valid = !is_null($news->link);
    }

    if ($valid && ($db = DBMySQL::getInstance()->connection())) {

      /** @var \DateTime $published */
      $published = $news->published;

      $query = <<<SQL
INSERT INTO mirra_news (published, title, notice, body, type, link, created_at) 
VALUES (:published, :title, :notice, :body, :type, :link, NOW())
SQL;

      $stmt = $db->prepare($query);
      $stmt->bindValue(':published', $published->format('Y-m-d H:i:s'));
      $stmt->bindValue(':title', $news->title);
      $stmt->bindValue(':notice', $news->notice);
      $stmt->bindValue(':body', $news->body);
      $stmt->bindValue(':type', $news->type);
      $stmt->bindValue(':link', $news->link);

      if ($stmt->execute()) {
        $result = self::find($db->lastInsertId());
      }
    }

    return $result;
  }

  /**
   * @param $id
   * @return News|null
   */
  public static function find($id)
  {
    $reg = Registry::getInstance();
    $key = Registry::createKey(__CLASS__, $id);

    if (!$reg->existsObject($key) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
SELECT * FROM  mirra_news WHERE id = $id
SQL;

      /** @var News $object */
      foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $reg->addObject($key, $object);
      }
    }

    return $reg->existsObject($key) ? $reg->getObject($key) : null;
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
SELECT COUNT(p.id) as total FROM mirra_news n, mirra_pages p 
WHERE p.parent_id = :parent_id AND p.content_type = :content_type
SQL;
      if (!is_null($handler_id)) {
        $statement .= " AND p.handler_id = :handler_id ";
      }

      if ($published) {
        $statement .= " AND p.published = 1";
      }

      $statement .= " AND n.id = p.content_id";

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
SELECT n.* FROM mirra_news n, mirra_pages p 
WHERE p.parent_id = :parent_id AND p.content_type = :content_type 
SQL;

      if (!is_null($handler_id)) {
        $statement .= " AND p.handler_id = :handler_id";
      }

      if ($published) {
        $statement .= " AND p.published = 1";
      }

      $statement .= " AND n.id = p.content_id";

      if (!is_null($order)) {
        switch ($order) {
          case self::ORDER_BY_DATE :
            $statement .= " ORDER BY n.published DESC";
            break;
          case self::ORDER_BY_TITLE :
            $statement .= " ORDER BY n.title ASC";
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

        /** @var News $object */
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

}