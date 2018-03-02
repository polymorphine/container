# Polymorphine/Container
[![Build Status](https://travis-ci.org/shudd3r/polymorphine-container.svg?branch=develop)](https://travis-ci.org/shudd3r/container)
[![Coverage Status](https://coveralls.io/repos/github/shudd3r/polymorphine-container/badge.svg?branch=develop)](https://coveralls.io/github/shudd3r/container?branch=develop)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/polymorphine/container/dev-develop.svg)](https://packagist.org/packages/polymorphine/container)
[![Packagist](https://img.shields.io/packagist/l/polymorphine/container.svg)](https://packagist.org/packages/polymorphine/container)
### PSR-11 Container for Dependencies and Configuration

#### Concept features: *Immutability & encapsulated configuration*
Stateful nature of custom `Record` implementation might return different values on
subsequent calls or even have side effects - take some time to reconsider before you
decide on such feature.

Container configuration might be separated from container itself, so that values
stored in container could not be (easily) accessed - see: [Config proxy](#config-proxy)

### Installation
    composer require polymorphine/container

### Container Instance
#### Records
Values returned from Container are all wrapped into [`Record`](src/Setup/Record.php) abstraction that allows
for different strategies of unwrapping them - it may be either returned directly or internally created
by calling its (lazy) initialization procedure. Read comments in the provided default [records](src/Setup/Record)
sourcecode to get more information.

#### Constructor
Container can't be instantiated with invalid state, because [`RecordCollection`](src/Setup/RecordCollection.php)
will throw [`Exception`](src/Exception) from constructor. Container is immutable (but not necessary objects within it)
so all the data have to be passed at once.

There's also static named constructor `Container::fromRecordsArray()` that instantiates `Container` with given array
of Record instances.

```php
use Polymorphine\Container\Container;
use Polymorphine\Container\Setup\Record;

$container = Container::fromRecordsArray([
    'config.uriString' => new Record\DirectRecord('www.example.com'),
    'Psr-uri.fromString.callback' => new Record\DirectRecord(function (string $x) {
        return new Psr\Implementation\Uri($x);
    }),
    'lazy.Psr-uri' => new Record\LazyRecord(function (ContainerInterface $c) {
        $callback = $this->get('Psr-uri.fromString.callback');
        return $callback($this->get('config.uriString'))->withScheme('https');
    })
]);
```

#### ContainerSetup
`ContainerSetup` takes the same array parameter, but has a method allowing to add new `Record`
instances before creating `Container`. Same result as above with different execution flow:

```php
use Polymorphine\Container\ContainerSetup;
use Polymorphine\Container\Record;

$setup = new ContainerSetup([
    'config.uriString' => new DirectRecord('www.example.com'),
    'Psr-uri.fromString.callback' => new DirectRecord(function (string $x) {
        return new Psr\Implementation\Uri($x);
    })
]);

// do something else, resolve dependencies then...

$setup->entry('lazy.instantiated.Psr-uri')->lazy(function (ContainerInterface $c) {
    $callback = $this->get('Psr-uri.fromString.callback');
    return $callback($this->get('config.uriString'));
});

$container = $setup->container();
```

#### Config proxy
Calling `ContainerSetup::entry($name)` returns `RecordSetup` helper object.
Beside providing methods to inject new instances of `Record` implementations
into `RecordCollection` it also isolates `Container` from scopes that should
not be allowed to peek inside by calling `ContainerSetup::container()` method.

To make it possible `ContainerSetup` should be encapsulated in object,
that allows for its configuration. For example, if you have front
controller bootstrap class similar to...

```php
class App
{
    private $containerSetup;
    
    public function __construct(array $records = []) {
        $this->containerSetup = new ContainerSetup($records);
    }
    
    //...
    
    public function config(string $name): RecordEntry {
        return $this->containerSetup->entry($name);
    }
    
    public function handle(ServerRequestInterface $request): ResponseInterface {
        $container = $this->containerSetup->container();
        //...
    }
}
```

...creating same container as with methods above might go like this:

```php
$app = new App([
    'config.uriString' => new DirectRecord('www.example.com')
]);

$app->config('Psr-uri.fromString.callback')->value(function (string $x) {
    return new Psr\Implementation\Uri($x);
});

$app->config('lazy.instantiated.Psr-uri')->lazy(function (ContainerInterface $c) {
    $callback = $this->get('Psr-uri.fromString.callback');
    return $callback($this->get('config.uriString'))->withScheme('https');
});

//...

$response = $app->handle($request);
```

Nothing in outer scope can use instance of `Container` created within `App`.  
It is possible to achieve, but it needs to be done by explicitly passing
stateful object identifier within callback passing container through one of
object's methods. Still, this is not recommended, so it won't be covered in details.
