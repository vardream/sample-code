<?php
return [
  'default_charset' => 'UTF-8',
  'language' => 'ru',
  'locale' => 'ru_RU.UTF-8',
  'timezone' => 'Europe/Kiev',

  'mode' => [
    'developing' => true,
    'youtube' => false,
    'facebook' => false,

  ],

  'connections' => [
    'mysql' => [
      'driver' => 'mysql',
      'host' => 'xxxx',
      'port' => '3306',
      'database' => 'xxxxx',
      'username' => 'xxxxx',
      'password' => 'xxxxx',
      'unix_socket' => '/var/lib/mysql/mysql.sock',
      'charset' => 'utf8',
      'collation' => 'utf8_unicode_ci',
      'prefix' => '',
      'strict' => true,
      'engine' => null,
    ]
  ],

  'mail' => [
    'mail_driver' => 'smtp',
    'mail_host' => 'xxxx',
    'mail_port' => 25,
    'mail_username' => 'xxxx',
    'mail_password' => 'xxxx',
    'mail_encryption' => null,
    'spool' => [
      'type' => 'file',
      'path' => __DIR__ . '/spool'
    ],

    'from' => [
      'address' => 'xxxx',
      'name' => 'Mirra'
    ],

    'admin' => [
      'address' => 'xxxx',
      'name' => 'Info'
    ],

    'support' => [
      'address' => 'xxxx',
      'name' => 'Support'
    ],
  ],

  'log' => [
    'enabled' => true,
    'path' => __DIR__ . '/log/support.log'
  ]

];