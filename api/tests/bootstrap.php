<?php

$_ENV['APP_ENV'] = 'test';
$_SERVER['APP_ENV'] = 'test';

use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
