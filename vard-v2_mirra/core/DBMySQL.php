<?php

namespace core;

/**
 * Created by PhpStorm.
 * User: alex
 * Date: 24.08.2017
 * Time: 18:46
 */

/**
 * Class DBMySQL
 *
 * @package core
 */
class DBMySQL
{
  private static $instance;
  /**
   * @var \PDO|null
   */
  private $connection;

  /**
   * DBMySQL constructor.
   */
  private function __construct()
  {
    if (isset($GLOBALS['cfg'])) {
      if (!is_null($GLOBALS['cfg']['connections']['mysql']['unix_socket'])
        && ($GLOBALS['cfg']['connections']['mysql']['unix_socket'] != '')
      ) {

        $unix_socket = ini_get('pdo_mysql.default_socket');

        if (!is_string($unix_socket)
          || ($unix_socket == '')
          || !file_exists($unix_socket)) {
          $unix_socket = $GLOBALS['cfg']['connections']['mysql']['unix_socket'];
        }
        $dsn = 'mysql:unix_socket=' . $unix_socket;
      } else {
        $dsn = 'mysql:host=' . $GLOBALS['cfg']['connections']['mysql']['host'];
        $dsn .= ';port=' . $GLOBALS['cfg']['connections']['mysql']['port'];
      }
      $dsn .= ';dbname=' . $GLOBALS['cfg']['connections']['mysql']['database'];
      $options = [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_PERSISTENT => true,
        \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$GLOBALS['cfg']['connections']['mysql']['charset']}' COLLATE '{$GLOBALS['cfg']['connections']['mysql']['collation']}'"
      ];
      try {
        $this->connection = new \PDO($dsn, $GLOBALS['cfg']['connections']['mysql']['username'], $GLOBALS['cfg']['connections']['mysql']['password'], $options);
      } catch (\PDOException $exception) {
        \ErrorHandler::error_504_gateway_timeout($exception->getMessage());
      }
    }
  }

  /**
   * @return DBMySQL
   */
  public static function getInstance()
  {
    if (is_null(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * @param \DateTime $value
   * @return string
   */
  public static function prepareDateTimeOrNull(\DateTime $value = null)
  {
    return is_null($value) ? 'NULL' : "'" . $value->format("Y-m-d H:i:s") . "'";
  }

  /**
   * @param $value
   * @return string
   */
  public static function prepareStringOrNull($value)
  {
    return is_null($value) ? 'NULL' : "'" . trim($value) . "'";
  }

  /**
   * @param $value
   * @return string
   */
  public static function prepareIntOrNull($value)
  {
    return is_null($value) ? 'NULL' : $value;
  }

  /**
   * @return \PDO|null
   */
  public function connection()
  {
    return $this->connection;
  }

}