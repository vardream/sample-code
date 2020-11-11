<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 21.01.2018
 * Time: 3:10
 */

namespace core\Models;

use core\DBMySQL;
use core\Registry;

/**
 * Class Response
 *
 * @package core\Models
 *
 * @property  int       $id
 * @property  int       $visitor_id
 * @property  string    $body
 * @property  int       $checked
 * @property  int       $mail_sent
 * @property  string    $ip
 * @property  string    $browser
 * @property  int       $content_id
 * @property  string    $content_type
 * @property int        $old_id
 * @property  \DateTime $created_at
 * @property  \DateTime $updated_at
 */
class Response
{
  private $fields = [
    'id' => null,
    'visitor_id' => null,
    'body' => null,
    'checked' => false,
    'mail_sent' => false,
    'ip' => null,
    'browser' => null,
    'content_id' => null,
    'content_type' => null,
    'old_id' => null,
    'created_at' => null,
    'updated_at' => null
  ];

  public function __construct($fields = [])
  {
    foreach ($fields as $field => $value) {
      $this->$field = $value;
    }
  }

  public function __get($name)
  {
    return array_key_exists($name, $this->fields) ? $this->fields[$name] : null;
  }

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
      case 'visitor_id':
      case 'content_id':
      case 'old_id':
        $this->fields[$name] = is_int($value) ? $value : is_numeric($value) ? intval($value) : null;
        if (is_int($this->fields[$name]) && $this->fields[$name] < 1) {
          $this->fields[$name] = null;
        }
        break;
      case 'checked':
      case 'mail_sent':
        $this->fields[$name] = boolval($value);
        break;
      case 'ip':
      case 'browser':
        $this->fields[$name] = is_string($value) ? trim($value) : '';
        break;
      case 'body':
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
   * @return Visitor|null
   */
  public function visitor()
  {
    return !is_null($this->visitor_id) ? Visitor::find($this->visitor_id) : null;
  }

  /**
   * @return bool
   */
  public function save()
  {
    $result = false;

    if (($db = DBMySQL::getInstance()->connection())
      && !is_null($this->id)
    ) {

      $body = $this->body;

      if (!is_null($body)) {

        $visitor = $this->visitor();

        if (!is_null($visitor)) {

          $content = null;

          try {
            $class = new \ReflectionClass($this->content_type);
            $method_name = 'find';
            if ($class->hasMethod($method_name)) {
              $method = $class->getMethod($method_name);
              if ($method->isStatic()) {
                $content = $method->invoke(null, $this->content_id);
              }
            }
          } catch (\Exception $exception) {

          }

          if (!is_null($content)) {

            $statement = <<<SQL
UPDATE mirra_responses 
SET visitor_id = :visitor_id, 
  body = :body, 
  checked = :checked, 
  mail_sent =:mail_sent, 
  ip = :ip, 
  browser = :browser, 
  content_id = :content_id, 
  content_type = :content_type,
  old_id = :old_id,
  created_at = :created_at
WHERE id = :id
SQL;

            $stmt = $db->prepare($statement);
            $stmt->bindValue(':visitor_id', $visitor->id);
            $stmt->bindValue(':body', $body);
            $stmt->bindValue(':checked', $this->checked);
            $stmt->bindValue(':mail_sent', $this->mail_sent);
            $stmt->bindValue(':ip', $this->ip);
            $stmt->bindValue(':browser', $this->browser);
            $stmt->bindValue(':content_id', $this->content_id);
            $stmt->bindValue(':content_type', $this->content_type);
            $stmt->bindValue(':created_at', $this->created_at->format('Y-m-d H:i:s'));
            $stmt->bindValue(':old_id', $this->old_id);
            $stmt->bindValue(':id', $this->id);

            try {
              $result = $stmt->execute();
            } catch (\Exception $exception) {

            }
          }
        }
      }
    }

    return $result;
  }


  /**
   * @param Response $response
   * @return Response|null
   */
  public static function create(Response $response)
  {
    $result = null;

    if ($db = DBMySQL::getInstance()->connection()) {

      $body = $response->body;

      if (!is_null($body)) {

        $visitor = $response->visitor();

        if (!is_null($visitor)) {

          $content = null;

          try {
            $class = new \ReflectionClass($response->content_type);
            $method_name = 'find';
            if ($class->hasMethod($method_name)) {
              $method = $class->getMethod($method_name);
              if ($method->isStatic()) {
                $content = $method->invoke(null, $response->content_id);
              }
            }
          } catch (\Exception $exception) {

          }

          if (!is_null($content)) {

            $statement = <<<SQL
INSERT INTO mirra_responses (
  visitor_id, 
  body, 
  checked, 
  mail_sent, 
  ip, 
  browser, 
  content_id, 
  content_type, 
  old_id, 
  created_at
) 
VALUES (
  :visitor_id,
  :body,
  :checked, 
  :mail_sent, 
  :ip, 
  :browser, 
  :content_id, 
  :content_type, 
  :old_id, 
  NOW()
)
SQL;

            $stmt = $db->prepare($statement);
            $stmt->bindValue(':visitor_id', $visitor->id);
            $stmt->bindValue(':body', $body);
            $stmt->bindValue(':checked', $response->checked);
            $stmt->bindValue(':mail_sent', $response->mail_sent);
            $stmt->bindValue(':ip', $response->ip);
            $stmt->bindValue(':browser', $response->browser);
            $stmt->bindValue(':content_id', $response->content_id);
            $stmt->bindValue(':content_type', $response->content_type);
            $stmt->bindValue(':old_id', $response->old_id);

            try {
              if ($stmt->execute()) {
                $result = self::find($db->lastInsertId());
              }
            } catch (\Exception $exception) {

            }
          }
        }
      }
    }

    return $result;
  }


  /**
   * @param $id
   * @return Response
   */
  public static function find($id)
  {
    $key = Registry::createKey(__CLASS__, $id);
    $reg = Registry::getInstance();

    if (!$reg->existsObject($key)) {
      if ($db = DBMySQL::getInstance()->connection()) {
        $query = <<<SQL
SELECT * FROM mirra_responses WHERE id = $id
SQL;
        /** @var Response $object */
        foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
          $reg->addObject($key, $object);
        }
      }
    }

    return $reg->getObject($key);
  }

  /**
   * @param $id
   * @return Response|null
   */
  public static function old_id($id)
  {
    $key = Registry::createKey(__CLASS__, $id);
    $reg = Registry::getInstance();

    if (!$reg->existsObject($key)) {
      if ($db = DBMySQL::getInstance()->connection()) {
        $query = <<<SQL
SELECT * FROM mirra_responses WHERE old_id = $id
SQL;
        /** @var Response $object */
        foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
          $reg->addObject($key, $object);
        }
      }
    }

    return $reg->getObject($key);
  }

  /**
   * @param      $content_type
   * @param      $content_id
   * @param bool $checked
   * @param int  $from
   * @param null $limit
   * @return array
   */
  public static function selectContentResponses($content_type, $content_id, $checked = true, $from = 0, $limit = null)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT * FROM mirra_responses WHERE content_type = :content_type AND content_id = :content_id
SQL;

      if ($checked) {
        $statement .= " AND checked > 0";
      }

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

        /** @var Response $object */
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
   * @param      $content_type
   * @param      $content_id
   * @param bool $checked
   * @return int
   */
  public static function countContentResponses($content_type, $content_id, $checked = true)
  {
    $result = 0;

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT COUNT(id) AS total FROM mirra_responses WHERE content_type = :content_type AND content_id = :content_id
SQL;

      if ($checked) {
        $statement .= " AND checked > 0";
      }

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


}