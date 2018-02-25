# Polymorphine Container
[![Build Status](https://travis-ci.org/shudd3r/polymorphine-container.svg?branch=develop)](https://travis-ci.org/shudd3r/container)
[![Coverage Status](https://coveralls.io/repos/github/shudd3r/polymorphine-container/badge.svg?branch=develop)](https://coveralls.io/github/shudd3r/container?branch=develop)
### PSR-11 Container for Dependencies and Configuration
#### Immutability & encapsulated configuration

### Installation
Requires **php 7.0** or higher

    composer require polymorphine/container


### Usage examples
#### Create directly with constructor parameter
Container created this way will be immutable, but all the data have to be passed at once.
Stateful nature of custom `Record` implementations might return different values on
subsequent calls or even have side effects - if you think you need such things,
take some time to reconsider.

```php
use Polymorphine\Container\Container;
use Polymorphine\Container\Record\DirectRecord;
use Polymorphine\Container\Record\LazyRecord;

$container = new Container([
    'config.uriString' => new DirectRecord('www.example.com'),
    'Psr-uri.fromString.callback' => new DirectRecord(function (string $x) {
        return new Psr\Implementation\Uri($x);
    }),
    'lazy.Psr-uri' => new LazyRecord(function (ContainerInterface $c) {
        $callback = $this->get('Psr-uri.fromString.callback');
        return $callback($this->get('config.uriString'))->withScheme('https');
    })
]);
```    

#### Create with Factory
Factory takes the same array parameter, but has method allowing to add new `Record`
before instantiating `Container`. Same result as above with different execution flow:

```php
use Polymorphine\Container\Factory;
use Polymorphine\Container\Record\DirectRecord;
use Polymorphine\Container\Record\LazyRecord;

$factory = new Factory([
    'config.uriString' => new DirectRecord('www.example.com'),
    'Psr-uri.fromString.callback' => new DirectRecord(function (string $x) {
        return new Psr\Implementation\Uri($x);
    })
]);

// do something else, resolve dependencies then...

$factory->record('lazy.instantiated.Psr-uri', new LazyRecord(function (ContainerInterface $c) {
    $callback = $this->get('uriInterface.');
    return $callback($this->get('config.value'));
});

$container = $factory->container();
```

#### Config proxy
`RecordEntry` helper, beside providing methods to inject new instances
of `DirectRecord` and `LazyRecord` into `Factory`, isolates it from scopes
that should not be allowed to peek inside `Container` by calling its `create()` method.

To make it possible `Factory` should be encapsulated in object,
that allows for its configuration. For example, if you have front
controller bootstrap class similar to...

```php
class App
{
    private $contaierFactory;
    
    public function __construct(ContainerFactory $factory) {
        $this->containerFactory = $factory;
    }
    
    //...
    
    public function config(string $name): RecordEntry {
        return new RecordEntry($name, $this->containerFactory);
    }
    
    public function handle(ServerRequestInterface $request): ResponseInterface {
        $container = $this->containerFactory->container();
        //...
    }
}
```

...creating same container as with methods above might go like this:

```php
$app = new App(
    new Factory([
        'config.uriString' => new DirectRecord('www.example.com')
    ])
);

$app->config('Psr-uri.fromString.callback')->value(function (string $x) {
    return new Psr\Implementation\Uri($x);
});

$app->config('lazy.Psr-uri')->lazy(function (ContainerInterface $c) {
    $callback = $this->get('Psr-uri.fromString.callback');
    return $callback($this->get('config.uriString'))->withScheme('https');
});

//...

$response = $app->handle($request);
```

Nothing in outer scope can use instance of `Container` created within `App`.
