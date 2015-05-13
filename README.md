Swift Client
============

[![Build Status](https://travis-ci.org/treehouselabs/swift-client.svg)](https://travis-ci.org/treehouselabs/swift-client)
[![Code Coverage](https://scrutinizer-ci.com/g/treehouselabs/swift-client/badges/coverage.png)](https://scrutinizer-ci.com/g/treehouselabs/swift-client/)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/treehouselabs/swift-client/badges/quality-score.png)](https://scrutinizer-ci.com/g/treehouselabs/swift-client/)

## Installation

```sh
composer require treehouselabs/swift-client:~1.0
```

## Usage

```php
use TreeHouse\Keystone\Client\ClientFactory;
use TreeHouse\Keystone\Client\Model\Tenant;

// use `treehouselabs/keystone-client` to initialize a Guzzle Client that can
// communicate with Keystone-authenticated services
$driver = new SwiftDriver($client);
$store  = new ObjectStore($driver);

// create a new container and object
$container = $store->createContainer('foo');
$object = $store->createObject($container, 'bar');

// set a local file to the object
$object->setLocalFile($file);

// update the object in the store
$store->updateObject($object);


// ...

// get the stored container/object
$container = $store->getContainer('foo');
$object = $container->getObject('bar);

// get the contents
$store->getObjectContent($object);
```
