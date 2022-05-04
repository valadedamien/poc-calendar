<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv('SMART_APP_ENV', 'SMART_APP_DEBUG'))->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['SMART_APP_ENV'], (bool) $_SERVER['SMART_APP_DEBUG']);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
