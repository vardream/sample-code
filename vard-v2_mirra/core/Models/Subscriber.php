<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 11.11.2017
 * Time: 23:20
 */

namespace core\Models;

use core\Registry;
use core\DBMySQL;

/**
 * Class Subscriber
 *
 * @package core\Models
 *
 * @property int       $id
 * @property int       $visitor_id
 * @property bool      $mail_sent
 * @property string    $ip
 * @property string    $browser
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Subscriber
{
  private $fields = [
    'id' => null,
    'visitor_id' => null,
    'mail_sent' => 0,
    'ip' => null,
    'browser' => null,
    'created_at' => null,
    'updated_at' => null
  ];

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
      case 'visitor_id':
        $this->fields[$name] = is_int($value) ? $value : is_numeric($value) ? intval($value) : null;
        break;
      case 'mail_sent':
        $this->fields[$name] = boolval($value);
        break;
      case 'ip':
      case 'browser':
        $this->fields[$name] = is_string($value) ? trim($value) : '';
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

  public function save()
  {
    $result = false;

    $id = $this->id;
    $visitor_id = $this->visitor_id;

    if (!is_null($id)
      && !is_null($visitor_id)
      && ($db = DBMySQL::getInstance()->connection())
      && $this->visitor()) {

      $mail_sent = $this->mail_sent;
      $ip = $this->ip;
      $browser = $this->browser;

      $statement = <<<SQL
UPDATE mirra_subscribers SET visitor_id = :visitor_id, mail_sent = :mail_sent, ip = :ip, browser = :browser WHERE id = :id
SQL;
      $stmt = $db->prepare($statement);
      $stmt->bindParam(':id', $id);
      $stmt->bindParam(':visitor_id', $visitor_id);
      $stmt->bindParam(':mail_sent', $mail_sent);
      $stmt->bindParam(':ip', $ip);
      $stmt->bindParam(':browser', $browser);

      $result = $stmt->execute();
    }

    return $result;
  }

  /**
   * @param Subscriber $subscriber
   * @return Subscriber|null
   */
  public static function create(Subscriber $subscriber)
  {
    $result = null;

    $visitor_id = $subscriber->visitor_id;

    if (!is_null($visitor_id)
      && ($db = DBMySQL::getInstance()->connection())
      && $subscriber->visitor()) {

      $mail_sent = $subscriber->mail_sent;
      $ip = $subscriber->ip;
      $browser = $subscriber->browser;

      $statement = <<<SQL
INSERT INTO mirra_subscribers (visitor_id, mail_sent, ip, browser, created_at) VALUES (:visitor_id, :mail_sent, :ip, :browser, NOW()) 
SQL;
      $stmt = $db->prepare($statement);
      $stmt->bindParam(':visitor_id', $visitor_id);
      $stmt->bindParam(':mail_sent', $mail_sent);
      $stmt->bindParam(':ip', $ip);
      $stmt->bindParam(':browser', $browser);

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
   * @return Subscriber|null
   */
  public static function find($id)
  {
    $key = Registry::createKey(__CLASS__, $id);
    $reg = Registry::getInstance();

    if (!$reg->existsObject($key)) {
      if ($db = DBMySQL::getInstance()->connection()) {
        $query = <<<SQL
SELECT * FROM mirra_subscribers WHERE id = $id
SQL;
        /** @var Subscriber $object */
        foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
          $reg->addObject($key, $object);
        }
      }
    }

    return $reg->getObject($key);
  }

  /**
   * @param int|null $visitor_id
   * @return Subscriber|null
   */
  public static function findByVisitorId($visitor_id = null)
  {
    $result = null;

    if (
      !is_null($visitor_id) &&
      $db = DBMySQL::getInstance()->connection()
    ) {
      $statement = <<<SQL
SELECT * FROM mirra_subscribers WHERE visitor_id = :visitor_id
SQL;
      $stmt = $db->prepare($statement);
      $stmt->bindValue('visitor_id', $visitor_id);

      /** @var Subscriber $object */
      if (
        $stmt->execute() &&
        $object = $stmt->fetchObject(__CLASS__)
      ) {
        $key = Registry::createKey(__CLASS__, $object->id);
        $reg = Registry::getInstance();
        if (!$reg->existsObject($key)) {
          $reg->addObject($key, $object);
        }

        $result = $reg->getObject($key);
      }
    }

    return $result;
  }
}