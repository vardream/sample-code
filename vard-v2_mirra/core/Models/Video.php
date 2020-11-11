<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 30.01.2018
 * Time: 0:18
 */

namespace core\Models;

use core\DBMySQL;
use core\Registry;

/**
 * Class Video
 *
 * @package core\Models
 *
 * @property int         $id
 * @property string      $link
 * @property string|null $title
 * @property string|null $image
 * @property int|null    $content_id
 * @property string|null $content_type
 * @property \DateTime   $created_at
 * @property \DateTime   $updated_at
 */
class Video
{
  private $fields = [
    'id' => null,
    'link' => null,
    'title' => null,
    'image' => null,
    'content_id' => null,
    'content_type' => null,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * Video constructor.
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
   * @return mixed
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
      case 'content_id':
        $this->fields[$name] = is_int($value) ? $value : is_numeric($value) ? intval($value) : null;
        if (is_int($this->fields[$name]) && $this->fields[$name] < 1) {
          $this->fields[$name] = null;
        }
        break;
      case 'link':
      case 'title':
      case 'image':
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
   * @return object|null
   */
  public function content()
  {
    $result = null;

    if (!is_null($this->content_id) && !is_null($this->content_type)) {
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
   * @return bool
   */
  public function save()
  {
    $result = false;
    $content = null;
    $content_exists = true;

    if (!is_null($this->id)
      && !is_null($this->link)
      && ($db = DBMySQL::getInstance()->connection())
    ) {

      $content_id = $this->content_id;
      $content_type = $this->content_type;

      if (!is_null($content_id)
        && !is_null($content_type)
      ) {
        try {
          $class = new \ReflectionClass($content_type);
          $method_name = 'find';
          if ($class->hasMethod($method_name)) {
            $method = $class->getMethod($method_name);
            if ($method->isStatic()) {
              $content = $method->invoke(null, $content_id);
            }
          }
        } catch (\Exception $exception) {

        }

        $content_exists = !is_null($content);
      }

      if (!is_null($content_exists)) {

        $statement = <<<SQL
UPDATE mirra_videos 
SET link = :link, 
title = :title, 
image = :image, 
content_id = :content_id, 
content_type = :content_type 
WHERE id = :id
SQL;

        $stmt = $db->prepare($statement);
        $stmt->bindValue(':link', $this->link);
        $stmt->bindValue(':title', $this->title);
        $stmt->bindValue(':image', $this->image);
        $stmt->bindValue(':content_id', $content_id);
        $stmt->bindValue(':content_type', $content_type);
        $stmt->bindValue(':id', $this->id);

        try {
          $result = $stmt->execute();
        } catch (\Exception $exception) {

        }
      }
    }

    return $result;
  }

  /**
   * @static
   * @param Video $video
   * @return Video|null
   */
  public static function create(Video $video)
  {
    $result = null;
    $content = null;
    $content_exists = true;

    if (!is_null($video->link)
      && ($db = DBMySQL::getInstance()->connection())
    ) {

      $content_id = $video->content_id;
      $content_type = $video->content_type;

      if (!is_null($content_id)
        && !is_null($content_type)
      ) {
        try {
          $class = new \ReflectionClass($content_type);
          $method_name = 'find';
          if ($class->hasMethod($method_name)) {
            $method = $class->getMethod($method_name);
            if ($method->isStatic()) {
              $content = $method->invoke(null, $content_id);
            }
          }
        } catch (\Exception $exception) {

        }

        $content_exists = !is_null($content);
      }

      if (!is_null($content_exists)) {

        $statement = <<<SQL
INSERT INTO mirra_videos (link, title, image, content_id, content_type, created_at)
VALUES (:link, :title, :image, :content_id, :content_type,  NOW())
SQL;

        $stmt = $db->prepare($statement);
        $stmt->bindValue(':link', $video->link);
        $stmt->bindValue(':title', $video->title);
        $stmt->bindValue(':image', $video->image);
        $stmt->bindValue(':content_id', $content_id);
        $stmt->bindValue(':content_type', $content_type);

        try {
          if ($stmt->execute()) {
            $result = self::find($db->lastInsertId());
          }
        } catch (\Exception $exception) {

        }
      }
    }

    return $result;
  }


  /**
   * @static
   * @param $id
   * @return Video|null
   */
  public static function find($id)
  {
    $key = Registry::createKey(__CLASS__, $id);
    $reg = Registry::getInstance();

    if (!$reg->existsObject($key)) {
      if ($db = DBMySQL::getInstance()->connection()) {
        $query = <<<SQL
SELECT * FROM mirra_videos WHERE id = $id
SQL;
        /** @var Video $object */
        foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
          $reg->addObject($key, $object);
        }
      }
    }

    return $reg->getObject($key);
  }

  /**
   * @static
   * @param $content_type
   * @param $content_id
   * @return int
   */
  public static function countContentVideo($content_type, $content_id)
  {
    $result = 0;

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT COUNT(id) AS total FROM mirra_videos WHERE content_type = :content_type AND content_id = :content_id
SQL;

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':content_type', $content_type);
      $stmt->bindValue(':content_id', $content_id);

      try {
        if ($stmt->execute()) {
          $result = $stmt->fetchColumn(0);
        }
      } catch (\Exception $exception) {

      }
    }

    return $result;
  }

  /**
   * @param      $content_type
   * @param      $content_id
   * @param int  $from
   * @param null $limit
   * @return array
   */
  public static function selectContentVideo($content_type, $content_id, $from = 0, $limit = null)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT * FROM mirra_videos WHERE content_type = :content_type AND content_id = :content_id
SQL;

      $statement .= " ORDER BY created_at DESC";

      if (!is_null($limit)) {
        $statement .= " LIMIT :from, :limit";
      }

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':content_type', $content_type);
      $stmt->bindValue(':content_id', $content_id);
      if (!is_null($limit)) {
        $stmt->bindValue(':from', $from, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
      }

      if ($stmt->execute()) {

        $reg = Registry::getInstance();

        /** @var Video $object */
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