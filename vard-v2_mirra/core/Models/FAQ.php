<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 05.01.2018
 * Time: 22:20
 */

namespace core\Models;

use core\DBMySQL;
use core\Registry;

/**
 * Class FAQ
 *
 * @package core\Models
 *
 * @property int       $id
 * @property string    $question
 * @property string    $answer
 * @property int       $page_id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class FAQ
{
  const ORDER_BY_DATE = 'date';
  const ORDER_BY_QUESTION = 'headline';

  private $fields = [
    'id' => null,
    'question' => null,
    'answer' => null,
    'page_id' => null,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * FAQ constructor.
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
   * @param $name
   * @param $value
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
      case 'page_id':
        if (!is_null($value) && !is_int($value)) {
          $value = intval($value);
        }
        if (is_int($value) && ($value < 1)) {
          $value = null;
        }
        $this->fields[$name] = $value;
        break;
      case 'question':
      case 'answer':
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
   * @return Page|null
   */
  public function page()
  {
    return !is_null($this->page_id) ? Page::find($this->page_id) : null;
  }

  /**
   * @return bool
   */
  public function save()
  {
    $result = false;

    if (!is_null($this->id)
      && !is_null($this->question)
      && !is_null($this->answer)
      && ($db = DBMySQL::getInstance()->connection())
    ) {

      $statement = <<<SQL
UPDATE mirra_faq SET question = :question, answer = :answer, page_id = :page_id WHERE id = :id
SQL;
      $stmt = $db->prepare($statement);
      $stmt->bindValue(':question', $this->question);
      $stmt->bindValue(':answer', $this->answer);
      $stmt->bindValue(':page_id', $this->page_id);
      $stmt->bindValue(':id', $this->id);

      $result = $stmt->execute();
    }

    return $result;
  }


  /**
   * @param FAQ $faq
   * @return FAQ|null
   */
  public static function create(FAQ $faq)
  {
    $result = null;

    if (!is_null($faq->question)
      && !is_null($faq->answer)
      && ($db = DBMySQL::getInstance()->connection())
    ) {

      $query = <<<SQL
INSERT INTO mirra_faq (question, answer, page_id, created_at) VALUES (:question, :answer, :page_id, NOW())
SQL;

      $stmt = $db->prepare($query);
      $stmt->bindValue(':question', $faq->question);
      $stmt->bindValue(':answer', $faq->answer);
      $stmt->bindValue(':page_id', $faq->page_id);

      if ($stmt->execute()) {
        $result = self::find($db->lastInsertId());
      }
    }

    return $result;
  }

  /**
   * @param $id
   * @return FAQ|null
   */
  public static function find($id)
  {
    $reg = Registry::getInstance();
    $key = Registry::createKey(__CLASS__, $id);

    if (!$reg->existsObject($key) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
SELECT * FROM  mirra_faq WHERE id = $id
SQL;

      /** @var FAQ $object */
      foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $reg->addObject($key, $object);
      }
    }

    return $reg->existsObject($key) ? $reg->getObject($key) : null;
  }

  /**
   * @param bool $published
   * @param null $order
   * @param int  $from
   * @param null $limit
   * @return array
   */
  public static function selectItems($published = true, $order = null, $from = 0, $limit = null)
  {
    /*
     * Примечание.
     * Опубликоваными принято считать записи с page_id IS NOT NULL AND page_id > 0,
     * при условии, что страница с page_id опубликована
     */

    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT f.* FROM mirra_faq f
SQL;

      if ($published) {
        $statement .= ", mirra_pages p WHERE p.id = f.page_id AND f.page_id IS NOT NULL AND p.published > 0";
      }

      if (is_null($order)) {
        $order = self::ORDER_BY_DATE;
      }

      switch ($order) {
        case self::ORDER_BY_DATE :
          $statement .= " ORDER BY f.updated_at ASC";
          break;
        case self::ORDER_BY_QUESTION :
          $statement .= " ORDER BY f.question ASC";
          break;
      }

      if (!is_null($limit)) {
        $statement .= " LIMIT :from, :limit";
      }

      $stmt = $db->prepare($statement);
      if (!is_null($limit)) {
        $stmt->bindValue(':from', $from);
        $stmt->bindValue(':limit', $limit);
      }


      if ($stmt->execute()) {

        $reg = Registry::getInstance();

        /** @var FAQ $object */
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
   * @param bool $published
   * @return int
   */
  public static function countItems($published = true)
  {
    $result = 0;

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT COUNT(f.id) as total FROM mirra_faq f
SQL;
      if ($published) {
        $statement .= ", mirra_pages p WHERE p.id = f.page_id AND f.page_id IS NOT NULL AND p.published > 0";
      }

      $stmt = $db->prepare($statement);
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
   * @param int  $deep
   * @return int
   */
  public static function selectPageContentRecursiveCount(Page $page, $handler_id = null, $published = true, $deep = 3)
  {
    $result = 0;

    if ($db = DBMySQL::getInstance()->connection()) {

      $select = '';
      $left = '';

      for ($i = 1; $i <= $deep; $i++) {

        if ($i == 1) {
          $select .= "IFNULL(mp{$i}.id, mp" . ($i - 1) . ".id)";
        } else {
          $select = "IFNULL({$select}, mp{$i}.id)";
        }
        $left .= " LEFT JOIN mirra_pages mp{$i} ON mp{$i}.parent_id = mp" . ($i - 1) . ".id AND mp{$i}.content_type = :content_type";
        if (!is_null($handler_id)) {
          $left .= " AND mp{$i}.handler_id = :handler_id";
        }
        if ($published) {
          $left .= " AND mp{$i}.published = 1";
        }
      }

      $statement = "SELECT COUNT(id) as total FROM mirra_faq WHERE page_id IN (SELECT {$select} AS page_id FROM mirra_pages mp0 {$left} WHERE mp0.id = :page_id) OR page_id = :page_id";

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

  public static function selectPageContentRecursive(Page $page, $handler_id = null, $published = true, $order = null, $from = 0, $limit = null, $deep = 3)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $select = '';
      $left = '';

      for ($i = 1; $i <= $deep; $i++) {

        if ($i == 1) {
          $select .= "IFNULL(mp{$i}.id, mp" . ($i - 1) . ".id)";
        } else {
          $select = "IFNULL({$select}, mp{$i}.id)";
        }
        $left .= " LEFT JOIN mirra_pages mp{$i} ON mp{$i}.parent_id = mp" . ($i - 1) . ".id AND mp{$i}.content_type = :content_type";
        if (!is_null($handler_id)) {
          $left .= " AND mp{$i}.handler_id = :handler_id";
        }
        if ($published) {
          $left .= " AND mp{$i}.published = 1";
        }
      }

      $statement = "SELECT * FROM mirra_faq WHERE page_id IN (SELECT {$select} AS page_id FROM mirra_pages mp0 {$left} WHERE mp0.id = :page_id) OR page_id = :page_id";

      if (is_null($order)) {
        $order = self::ORDER_BY_DATE;
      }

      switch ($order) {
        case self::ORDER_BY_DATE :
          $statement .= " ORDER BY updated_at DESC, id DESC";
          break;
        case self::ORDER_BY_QUESTION :
          $statement .= " ORDER BY question ASC";
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

        /** @var FAQ $object */
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