<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 13.10.2017
 * Time: 16:43
 */

namespace core\Models;

use core\Registry;
use core\DBMySQL;

/**
 * Class Tag
 *
 * @package core\Models
 *
 * @property int       $id
 * @property int       $parent_id
 * @property string    $slug
 * @property int       $position
 * @property string    $title
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Tag
{
  private $fields = [
    'id' => null,
    'parent_id' => null,
    'slug' => null,
    'position' => 0,
    'title' => null,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * Tag constructor.
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
        if (!is_null($value) && !is_int($value)) {
          $value = intval($value);
        }
        if (is_int($value) && ($value < 1)) {
          $value = null;
        }
        $this->fields[$name] = $value;
        break;
      case 'slug':
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
   * @param null $content_type
   * @param int  $from
   * @param null $limit
   * @return array
   */
  public function items($content_type = null, $from = 0, $limit = null)
  {
    return TagItem::selectTagItems($this, $content_type, $from, $limit);
  }

  /**
   * @return Tag|null
   */
  public function parent()
  {
    return !is_null($this->parent_id) ? self::find($this->parent_id) : null;
  }

  /**
   * @return array
   */
  public function children()
  {
    $result = [];

    if (($id = $this->id)
      && ($db = DBMySQL::getInstance()->connection())
    ) {

      $statement = <<<SQL
SELECT * FROM mirra_tags WHERE parent_id = $id ORDER BY position ASC, title ASC
SQL;

      $reg = Registry::getInstance();

      /** @var Tag $object */
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
   * @param      $content_type
   * @param bool $published
   * @param null $order
   * @param int  $from
   * @param null $limit
   * @return array
   */
  public function content($content_type, $published = true, $order = null, $from = 0, $limit = null)
  {
    $result = [];

    try {
      $class = new \ReflectionClass($content_type);
      $method_name = 'selectTagContent';
      if ($class->hasMethod($method_name)) {
        $method = $class->getMethod($method_name);
        if ($method->isStatic()) {
          $result = $method->invoke(null, $this, $published, $order, $from, $limit);
        }
      }
    } catch (\Exception $exception) {

    }

    return $result;
  }

  /**
   * @param      $content_type
   * @param bool $published
   * @return int
   */
  public function contentCount($content_type, $published = true)
  {
    $result = 0;

    try {
      $class = new \ReflectionClass($content_type);
      $method_name = 'selectTagContentCount';
      if ($class->hasMethod($method_name)) {
        $method = $class->getMethod($method_name);
        if ($method->isStatic()) {
          $result = $method->invoke(null, $this, $published);
        }
      }
    } catch (\Exception $exception) {

    }

    return $result;
  }

  /**
   * @return bool
   */
  public function save()
  {
    $result = false;

    if (!is_null($this->id)
      && !is_null($this->slug)
      && !is_null($this->title)
    ) {
      if ($db = DBMySQL::getInstance()->connection()) {

        $query = <<<SQL
UPDATE mirra_tags SET parent_id = :parent_id, slug = :slug, position = :position, title = :title WHERE id = :id 
SQL;
        $stmt = $db->prepare($query);
        $stmt->bindValue(':parent_id', $this->parent_id);
        $stmt->bindValue(':slug', $this->slug);
        $stmt->bindValue(':position', $this->position);
        $stmt->bindValue(':title', $this->title);
        $stmt->bindValue(':id', $this->id);

        $result = $stmt->execute();
      }
    }

    return $result;
  }

  /**
   * @param Tag $tag
   * @return Tag|null
   */
  public static function create(Tag $tag)
  {
    $result = null;

    if (!is_null($tag->slug)
      && !is_null($tag->title)
      && ($db = DBMySQL::getInstance()->connection())
    ) {

      $statement = <<<SQL
INSERT INTO mirra_tags (parent_id, slug, position, title, created_at) VALUES (:parent_id, :slug, :position, :title, NOW())
SQL;
      $stmt = $db->prepare($statement);
      $stmt->bindValue(':parent_id', $tag->parent_id);
      $stmt->bindValue(':slug', $tag->slug);
      $stmt->bindValue(':position', $tag->position);
      $stmt->bindValue(':title', $tag->title);

      try {
        if ($stmt->execute()) {
          $result = self::find($db->lastInsertId());
        }
      } catch (\Exception $e) {

      }
    }

    return $result;
  }

  /**
   * @param $id
   * @return Tag|null
   */
  public static function find($id)
  {
    $key = Registry::createKey(__CLASS__, $id);
    $reg = Registry::getInstance();

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT * FROM mirra_tags WHERE id = :id
SQL;

      $stmt = $db->prepare($statement);
      $stmt->bindParam(':id', $id);

      if ($stmt->execute()) {
        /** @var Tag $object */
        while ($object = $stmt->fetchObject(__CLASS__)) {
          $reg->addObject($key, $object);
        }
      }
    }

    return $reg->existsObject($key) ? $reg->getObject($key) : null;
  }

  /**
   * @param $slug
   * @return Tag|null
   */
  public static function findSlug($slug)
  {
    $key = null;
    $reg = Registry::getInstance();

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT * FROM mirra_tags WHERE slug = :slug
SQL;

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':slug', $slug);

      if ($stmt->execute()) {
        /** @var Tag $object */
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
   * @param $content_type
   * @param $content_id
   * @param $slug
   * @return Tag|null
   */
  public static function findContentTagWithSlug($content_type, $content_id, $slug)
  {
    $key = null;
    $reg = Registry::getInstance();

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT mt.* FROM mirra_tags_items mti, mirra_tags mt WHERE mt.slug = :slug AND mti.content_type = :content_type AND mti.content_id = :content_id AND mti.tag_id = mt.id  
SQL;

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':content_type', $content_type);
      $stmt->bindValue(':content_id', $content_id);
      $stmt->bindValue(':slug', $slug);

      if ($stmt->execute()) {
        /** @var Tag $object */
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
   * @param $content_type
   * @param $content_id
   * @param $slug
   * @return array
   */
  public static function selectContentTags($content_type, $content_id, $slug)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT DISTINCT mt.* FROM mirra_tags_items mti, mirra_tags mt WHERE mti.content_type = :content_type AND mti.content_id = :content_id AND mt.id = mti.tag_id
SQL;

      if (!is_null($slug)) {
        $statement .= " AND mt.parent_id = (SELECT id FROM mirra_tags WHERE slug = :slug)";
      }

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':content_type', $content_type);
      $stmt->bindValue(':content_id', $content_id);
      if (!is_null($slug)) {
        $stmt->bindValue(':slug', $slug);
      }


      if ($stmt->execute()) {

        $reg = Registry::getInstance();

        /** @var Tag $object */
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