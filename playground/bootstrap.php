<?php

declare(strict_types=1);
use Playground\Support\Playground;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Support/Playground.php';
require __DIR__ . '/Support/WebhookPlayground.php';

return Playground::workspace(__DIR__);
