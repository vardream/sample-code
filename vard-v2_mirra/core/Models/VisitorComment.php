<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 12.11.2017
 * Time: 0:08
 */

namespace core\Models;

/**
 * Class VisitorComment
 *
 * @package core\Models
 *
 * @property int       $id
 * @property int       $visitor_id
 * @property string    $body
 * @property bool      $checked
 * @property bool      $mail_sent
 * @property string    $ip
 * @property string    $content_type
 * @property string    $content_id
 * @property string    $browser
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class VisitorComment
{
  private $fields = [
    'id' => null,
    'visitor_id' => null,
    'body' => null,
    'checked' => 0,
    'mail_sent' => 0,
    'content_type' => null,
    'content_id' => null,
    'ip' => null,
    'browser' => null,
    'created_at' => null,
    'updated_at' => null
  ];
}
