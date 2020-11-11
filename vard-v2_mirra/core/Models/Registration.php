<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 12.01.2018
 * Time: 0:25
 */

namespace core\Models;

use core\DBMySQL;
use core\Registry;

/**
 * Class Registration
 *
 * @package core\Models
 *
 * @property int       $id
 * @property int       $visitor_id
 * @property bool      $mail_sent
 * @property string    $phone
 * @property string    $location
 * @property string    $ip
 * @property string    $browser
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Registration
{
  private $fields = [
    'id' => null,
    'visitor_id' => null,
    'phone' => null,
    'location' => null,
    'mail_sent' => 0,
    'ip' => null,
    'browser' => null,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * Registration constructor.
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
      case 'visitor_id':
        $this->fields[$name] = is_int($value) ? $value : is_numeric($value) ? intval($value) : null;
        break;
      case 'mail_sent':
        $this->fields[$name] = boolval($value);
        break;
      case 'phone':
      case 'location':
      case 'ip':
      case 'browser':
        $this->fields[$name] = is_string($value) ? trim($value) : null;
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

    $id = $this->id;
    $visitor_id = $this->visitor_id;

    if (!is_null($id)
      && !is_null($visitor_id)
      && ($db = DBMySQL::getInstance()->connection())
      && $this->visitor()) {

      $statement = <<<SQL
UPDATE mirra_registrations SET visitor_id = :visitor_id, phone = :phone, location = :location, mail_sent = :mail_sent, ip = :ip, browser = :browser WHERE id = :id
SQL;
      $stmt = $db->prepare($statement);
      $stmt->bindValue(':id', $id);
      $stmt->bindValue(':visitor_id', $visitor_id);
      $stmt->bindValue(':mail_sent', $this->mail_sent);
      $stmt->bindValue(':phone', $this->phone);
      $stmt->bindValue(':location', $this->location);
      $stmt->bindValue(':ip', $this->ip);
      $stmt->bindValue(':browser', $this->browser);

      $result = $stmt->execute();
    }

    return $result;
  }


  /**
   * @param Registration $registration
   * @return Registration|null
   */
  public static function create(Registration $registration)
  {
    $result = null;

    $visitor_id = $registration->visitor_id;

    if (!is_null($visitor_id)
      && ($db = DBMySQL::getInstance()->connection())
      && $registration->visitor()
    ) {

      $statement = <<<SQL
INSERT INTO mirra_registrations (visitor_id, phone, location, mail_sent, ip, browser, created_at) VALUES (:visitor_id, :phone, :location, :mail_sent, :ip, :browser, NOW()) 
SQL;
      $stmt = $db->prepare($statement);
      $stmt->bindValue(':visitor_id', $visitor_id);
      $stmt->bindValue(':mail_sent', $registration->mail_sent);
      $stmt->bindValue(':phone', $registration->phone);
      $stmt->bindValue(':location', $registration->location);
      $stmt->bindValue(':ip', $registration->ip);
      $stmt->bindValue(':browser', $registration->browser);

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
   * @return Registration|null
   */
  public static function find($id)
  {
    $key = Registry::createKey(__CLASS__, $id);
    $reg = Registry::getInstance();

    if (!$reg->existsObject($key)) {
      if ($db = DBMySQL::getInstance()->connection()) {
        $query = <<<SQL
SELECT * FROM mirra_registrations WHERE id = $id
SQL;
        /** @var Registration $object */
        foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
          $reg->addObject($key, $object);
        }
      }
    }

    return $reg->getObject($key);
  }

  /**
   * @param int|null $visitor_id
   * @return Registration|null
   */
  public static function findByVisitorId($visitor_id = null)
  {
    $result = null;

    if (
      !is_null($visitor_id) &&
      $db = DBMySQL::getInstance()->connection()
    ) {
      $statement = <<<SQL
SELECT * FROM mirra_registrations WHERE visitor_id = :visitor_id
SQL;
      $stmt = $db->prepare($statement);
      $stmt->bindValue('visitor_id', $visitor_id);

      /** @var Registration $object */
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