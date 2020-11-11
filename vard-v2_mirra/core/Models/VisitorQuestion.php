<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 11.11.2017
 * Time: 23:43
 */

namespace core\Models;

/**
 * Class VisitorQuestion
 *
 * @package core\Models
 *
 * @property int       $id
 * @property int       $visitor_id
 * @property string    $subject
 * @property string    $body
 * @property bool      $mail_sent
 * @property string    $ip
 * @property string    $browser
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class VisitorQuestion
{
  private $fields = [
    'id' => null,
    'visitor_id' => null,
    'subject' => null,
    'body' => null,
    'mail_sent' => 0,
    'ip' => null,
    'browser' => null,
    'created_at' => null,
    'updated_at' => null
  ];
}
