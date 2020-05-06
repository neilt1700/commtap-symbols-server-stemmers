<?php

require_once 'utility.php';

spl_autoload_register(function ($class) {
  $root = dirname(dirname(__FILE__)) . '/src/';
  if (strpos($class, '\\') === FALSE && strpos($class, 'PHPUnit') == FALSE) {
    include_once $root . '/classes/' . $class . '.class.php';
  }
});

