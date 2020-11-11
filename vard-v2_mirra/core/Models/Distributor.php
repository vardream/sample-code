<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 11.11.2017
 * Time: 22:37
 */

namespace core\Models;

/**
 * Class Distributor
 *
 * @package core\Models
 *
 * @property int       $id
 * @property int       $visitor_id
 * @property string    $phone
 * @property string    $location
 * @property bool      $registered
 * @property bool      $mail_sent
 * @property string    $ip
 * @property string    $browser
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Distributor
{
  private $fields = [
    'id' => null,
    'visitor_id' => null,
    'phone' => null,
    'location' => null,
    'registered' => 0,
    'mail_sent' => 0,
    'ip' => null,
    'browser' => null,
    'created_at' => null,
    'updated_at' => null
  ];
}