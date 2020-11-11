<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 11.11.2017
 * Time: 23:40
 */

namespace core\Models;

use core\Registry;
use core\DBMySQL;

/**
 * Class Visitor
 *
 * @package core\Models
 *
 * @property int       $id
 * @property string    $name
 * @property string    $email
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Visitor
{
  private $fields = [
    'id' => null,
    'name' => null,
    'email' => null,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * Visitor constructor.
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
      case 'name':
      case 'email':
        $value = is_string($value) ? trim($value) : '';
        $this->fields[$name] = $value != '' ? $value : null;
        break;
//      case 'email':
//        $value = is_string($value) ? trim($value) : '';
//        if (preg_match('/[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/sim', $value)) {
//          $this->fields[$name] = $value;
//        } else {
//          $this->fields[$name] = null;
//        }
//        break;
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
   * @return Subscriber|null
   */
  public function subscriber()
  {
    return Subscriber::findByVisitorId($this->id);
  }

  /**
   * @return Registration|null
   */
  public function registration()
  {
    return Registration::findByVisitorId($this->id);
  }

  /**
   * @return array
   */
  public function consultations()
  {
    return Consultation::findVisitorsQuestions($this);
  }

  /**
   * @return bool
   */
  public function save()
  {
    $result = false;

    $id = $this->id;
    $name = $this->name;
    $email = $this->email;

    if (!is_null($id)
      && !is_null($name)
      && !is_null($email)
      && ($db = DBMySQL::getInstance()->connection())) {

      $statement = <<<SQL
UPDATE mirra_visitors SET name = :name, email = :email WHERE id = :id
SQL;
      $stmt = $db->prepare($statement);
      $stmt->bindParam(':id', $id);
      $stmt->bindParam(':name', $name);
      $stmt->bindParam(':email', $email);

      $result = $stmt->execute();
    }

    return $result;
  }

  /**
   * @param Visitor $visitor
   * @return Visitor|null
   */
  public static function create(Visitor $visitor)
  {
    $result = null;

    $name = $visitor->name;
    $email = $visitor->email;

    if (!is_null($name) && !is_null($email) && ($db = DBMySQL::getInstance()->connection())) {

      $statement = <<<SQL
INSERT INTO mirra_visitors (name, email, created_at) VALUES (:name, :email, NOW())
SQL;
      $stmt = $db->prepare($statement);
      $stmt->bindParam(':name', $name);
      $stmt->bindParam(':email', $email);

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
   * @return Visitor|null
   */
  public static function find($id)
  {
    $key = Registry::createKey(__CLASS__, $id);
    $reg = Registry::getInstance();

    if (!$reg->existsObject($key)) {
      if ($db = DBMySQL::getInstance()->connection()) {
        $query = <<<SQL
SELECT * FROM mirra_visitors WHERE id = $id
SQL;
        /** @var Visitor $object */
        foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
          $reg->addObject($key, $object);
        }
      }
    }

    return $reg->getObject($key);
  }

  /**
   * @param $email
   * @return Visitor|null
   */
  public static function findEmail($email)
  {
    $result = null;

    if ($db = DBMySQL::getInstance()->connection()) {
      $statement = <<<SQL
SELECT * FROM mirra_visitors WHERE email = :email
SQL;
      $stmt = $db->prepare($statement);
      $stmt->bindParam(':email', $email);

      if ($stmt->execute()) {
        /** @var Visitor $object */
        if ($object = $stmt->fetchObject(__CLASS__)) {
          $reg = Registry::getInstance();
          $key = Registry::createKey(__CLASS__, $object->id);

          if (!$reg->existsObject($key)) {
            $reg->addObject($key, $object);
          }

          $result = $reg->getObject($key);
        }
      }
    }

    return $result;
  }
}