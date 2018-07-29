# Polymorphine/Container
[![Build Status](https://travis-ci.org/shudd3r/polymorphine-container.svg?branch=develop)](https://travis-ci.org/shudd3r/container)
[![Coverage Status](https://coveralls.io/repos/github/shudd3r/polymorphine-container/badge.svg?branch=develop)](https://coveralls.io/github/shudd3r/container?branch=develop)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/polymorphine/container/dev-develop.svg)](https://packagist.org/packages/polymorphine/container)
[![Packagist](https://img.shields.io/packagist/l/polymorphine/container.svg)](https://packagist.org/packages/polymorphine/container)
### PSR-11 Container for Dependencies and Configuration

#### Concept features: *Immutability & encapsulated configuration*
Until [`ContainerSetup`](src/ContainerSetup.php) is accessible new records can be added
to existing container. It's recommended to use [Config proxy](#recordsetup-as-configuration-proxy) that allows
for controlled scope for both configuration and early access to stored values.

Stateful nature of custom `Record` implementation might return different values on
subsequent calls or even have side effects - take some time to reconsider before you
decide on such feature.

### Installation with [Composer](https://getcomposer.org/)
    php composer.phar require polymorphine/container

### Records decide how it works internally
Values returned from Container are all wrapped into [`Record`](src/Setup/Record.php) abstraction that allows
for different strategies of unwrapping them - it may be either returned directly or internally created
by calling its (lazy) initialization procedure. You may read DocBlock comments in the provided default
[records](src/Setup/Record) sourcecode to get more information. Here's short explanation of package's
Record implementations:

- `DirectRecord`: Just a value, that will be returned as it was passed. No evaluation for callbacks.
- `LazyRecord`: Takes `Closure` that will be called with `Container` as parameter, and value of this call will be stored
and returned on subsequent calls.
- `CompositeRecord`: Lazy instantiated object using constructor parameters for given class. Constructor parameters are
passed as aliases to container entries.

**Decorator feature**: `CompositeRecord` can reassign existing entry if it's also used as one of the constructor parameters.
Of course it should return the same type as overwritten record returns, because all clients currently using it will fail.
Example (using ContainerSetup):

```php
//Standard container setup...
$setup = new ContainerSetup(...);
$setup->entry('html.response')->composite(HtmlResponse::class, 'auth.user', 'template.system');

//Suppose we are in dev environment and want to add a diagnostic toolbar for all html views...
$setup->entry('html.response')->commposite(DevToolbarHtmlResponse::class, 'html.response', 'system.info');
```

### Container Instance
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
    'uriString' => new Record\DirectRecord('www.example.com'),
    'Psr-uri' => new Record\DirectRecord(function (string $x) {
        return new Psr\Implementation\Uri($x);
    }),
    'https.uri' => new Record\LazyRecord(function (ContainerInterface $c) {
        $callback = $this->get('Psr-uri');
        return $callback($this->get('uriString'))->withScheme('https');
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
    'uriString' => new DirectRecord('www.example.com'),
    'Psr-uri' => new DirectRecord(function (string $x) {
        return new Psr\Implementation\Uri($x);
    })
]);

// do something else, resolve dependencies then...

$setup->entry('https.uri')->lazy(function (ContainerInterface $c) {
    $callback = $this->get('Psr-uri');
    return $callback($this->get('uriString'));
});

$container = $setup->container();
```

#### RecordSetup as configuration proxy
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
    'uriString' => new DirectRecord('www.example.com')
]);

$app->config('Psr-uri')->value(function (string $x) {
    return new Psr\Implementation\Uri($x);
});

$app->config('https.uri')->lazy(function (ContainerInterface $c) {
    $callback = $this->get('Psr-uri');
    return $callback($this->get('uriString'))->withScheme('https');
});

//...

$response = $app->handle($request);
```

Nothing in outer scope can use instance of `Container` created within `App`.  
It is possible to achieve, but it needs to be done by explicitly passing
stateful object identifier within callback passing container through one of
object's methods. Still, this is not recommended, so it won't be covered in details.
