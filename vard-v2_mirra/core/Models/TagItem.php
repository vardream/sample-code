<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 13.10.2017
 * Time: 18:01
 */

namespace core\Models;

use core\DBMySQL;
use core\Registry;

/**
 * Class TagItem
 *
 * @package core\Models
 *
 * @property int       $id
 * @property int       $tag_id
 * @property int       $position
 * @property int       $content_id
 * @property string    $content_type
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class TagItem
{
  private $fields = [
    'id' => null,
    'tag_id' => null,
    'position' => 0,
    'content_id' => null,
    'content_type' => null,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * TagItem constructor.
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
      case 'tag_id':
      case 'content_id':
        if (!is_null($value) && !is_int($value)) {
          $value = intval($value);
        }
        if (is_int($value) && ($value < 1)) {
          $value = null;
        }
        $this->fields[$name] = $value;
        break;
      case 'content_type':
        if (is_null($value)) {
          $value = '';
        } else {
          $value = trim($value);
        }
        if ($value != '') {
          $this->fields[$name] = $value;
        }
        break;
      case 'position':
        $value = intval($value);
        $this->fields[$name] = ($value > 0) ? $value : 0;
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
   * @return Tag|null
   */
  public function tag()
  {
    return !is_null($this->tag_id) ? Tag::find($this->tag_id) : null;
  }

  /**
   * @return bool
   */
  public function save()
  {
    $result = false;

    if (($id = $this->id)
      && ($tag_id = $this->tag_id)
      && ($content_id = $this->content_id)
      && ($content_type = $this->content_type)
      && ($db = DBMySQL::getInstance()->connection())
    ) {

      $statement = <<<SQL
UPDATE mirra_tags_items SET tag_id = :tag_id, position = :position, content_id = :content_id, content_type = :content_type WHERE id = :id
SQL;

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':tag_id', $tag_id);
      $stmt->bindValue(':position', $this->position);
      $stmt->bindValue(':content_id', $content_id);
      $stmt->bindValue(':content_type', $content_type);
      $stmt->bindValue(':id', $id);

      $result = $stmt->execute();
    }

    return $result;
  }

  /**
   * @param TagItem $tagItem
   * @return TagItem|null
   */
  public static function create(TagItem $tagItem)
  {
    $result = null;

    if (!is_null($tagItem->tag_id)
      && !is_null($tagItem->content_id)
      && !is_null($tagItem->content_type)
      && ($db = DBMySQL::getInstance()->connection())
    ) {

      $statement = <<<SQL
INSERT INTO mirra_tags_items (tag_id, position, content_id, content_type, created_at) VALUES (:tag_id, :position, :content_id, :content_type, NOW())
SQL;
      $stmt = $db->prepare($statement);
      $stmt->bindValue(':tag_id', $tagItem->tag_id);
      $stmt->bindValue(':position', $tagItem->position);
      $stmt->bindValue(':content_id', $tagItem->content_id);
      $stmt->bindValue(':content_type', $tagItem->content_type);

      try {
        if ($stmt->execute()) {
          $result = self::find($db->lastInsertId());
        }
      } catch (\Exception $e) {

      }
    }

    return $result;
  }

  public static function delete(TagItem $tagItem)
  {
    $result = false;

    if (!is_null($tagItem->tag_id)
      && !is_null($tagItem->content_id)
      && !is_null($tagItem->content_type)
      && ($db = DBMySQL::getInstance()->connection())
    ) {

      $statement = <<<SQL
DELETE FROM mirra_tags_items WHERE tag_id = :tag_id AND content_id = :content_id AND content_type = :content_type
SQL;
      $stmt = $db->prepare($statement);
      $stmt->bindValue(':tag_id', $tagItem->tag_id);
      $stmt->bindValue(':content_id', $tagItem->content_id);
      $stmt->bindValue(':content_type', $tagItem->content_type);

      try {
        if ($stmt->execute()) {
          $key = Registry::createKey(__CLASS__, $tagItem->id);
          $reg = Registry::getInstance();
          $reg->delObject($key);

          $result = true;
        }
      } catch (\Exception $e) {
      }
    }

    return $result;
  }

  /**
   * @param $id
   * @return TagItem|null
   */
  public static function find($id)
  {
    $key = Registry::createKey(__CLASS__, $id);
    $reg = Registry::getInstance();

    if (!is_null($id) && ($db = DBMySQL::getInstance()->connection())) {

      $statement = <<<SQL
SELECT * FROM mirra_tags_items WHERE id = $id
SQL;

      foreach ($db->query($statement, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $reg->addObject($key, $object);
      }

    }

    return $reg->existsObject($key) ? $reg->getObject($key) : null;
  }

  /**
   * @param Tag  $tag
   * @param null $content_type
   * @param int  $from
   * @param null $limit
   * @return array
   */
  public static function selectTagItems(Tag $tag, $content_type = null, $from = 0, $limit = null)
  {
    $result = [];

    if (($tag_id = $tag->id)
      && ($db = DBMySQL::getInstance()->connection())
    ) {

      $statement = <<<SQL
SELECT * FROM mirra_tags_items WHERE tag_id = $tag_id
SQL;

      if (!is_null($content_type)) {
        $statement .= " AND content_type = '$content_type'";
      }

      $statement .= " ORDER BY position ACS";

      if ($from < 0) {
        $from = 0;
      }

      if (!is_null($limit)) {
        $statement .= " LIMIT $from,$limit";
      }

      $reg = Registry::getInstance();

      /** @var TagItem $object */
      foreach ($db->query($statement, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $key = Registry::createKey(__CLASS__, $object->id);
        if ($reg->existsObject($key)) {
          $reg->addObject($key, $object);
        }
        $result[] = $reg->getObject($key);
      }
    }

    return $result;
  }

}