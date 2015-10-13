Swift Client
============

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]

## Installation

```sh
composer require treehouselabs/swift-client:~1.0
```

## Usage

```php
// use `treehouselabs/keystone-client` to initialize a Guzzle Client that can
// communicate with Keystone-authenticated services
$driver = new SwiftDriver($keystoneClient);
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

## Testing

``` bash
composer test
```


## Security

If you discover any security related issues, please email peter@treehouse.nl instead of using the issue tracker.


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


## Credits

- [Peter Kruithof][link-author]
- [All Contributors][link-contributors]


[ico-version]: https://img.shields.io/packagist/v/treehouselabs/swift-client.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/treehouselabs/swift-client/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/treehouselabs/swift-client.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/treehouselabs/swift-client.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/treehouselabs/swift-client.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/treehouselabs/swift-client
[link-travis]: https://travis-ci.org/treehouselabs/swift-client
[link-scrutinizer]: https://scrutinizer-ci.com/g/treehouselabs/swift-client/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/treehouselabs/swift-client
[link-downloads]: https://packagist.org/packages/treehouselabs/swift-client
[link-author]: https://github.com/treehouselabs
[link-contributors]: ../../contributors
