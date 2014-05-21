<?php

$loader = new \Composer\Autoload\ClassLoader();
$loader->setPsr4('TreeHouse\\Swift\\Tests\\', __DIR__ . '/TreeHouse/Swift/Tests');
$loader->register();
