# Polymorphine/Container
[![Latest Stable Version](https://poser.pugx.org/polymorphine/container/version)](https://packagist.org/packages/polymorphine/container)
[![Build status](https://travis-ci.org/polymorphine/container.svg?branch=develop)](https://travis-ci.org/polymorphine/container)
[![Coverage status](https://coveralls.io/repos/github/polymorphine/container/badge.svg?branch=develop)](https://coveralls.io/github/polymorphine/container?branch=develop)
[![PHP version](https://img.shields.io/packagist/php-v/polymorphine/container.svg)](https://packagist.org/packages/polymorphine/container)
[![LICENSE](https://img.shields.io/github/license/polymorphine/container.svg?color=blue)](LICENSE)
### PSR-11 Container for libraries & configuration

#### Concept features:
- immutable PSR-11 implementation
- encapsulated configuration ([more](#read-and-write-separation))
- abstract strategy to retrieve stored values ready to extend functionality with some original
  built-in strategies ([more](#records-decide-how-it-works-internally))
- composition of PSR-11 sub-Containers
- optional path notation access to config values ([more](#configuration-container))
- dev mode for integrity checks, call stack tracking & circular reference protection ([more](#secure-setup--circular-reference-detection))
- explicit configuration by default - auto-wired dependencies can be resolved by custom strategies (not included)
- intended limited use for generic stuff: libraries, configuration and other context independent
  objects or functions ([more](#recommended-use))

### Installation with [Composer](https://getcomposer.org/)
    php composer.phar require polymorphine/container

### Container setup
This example will show how to set up simple container. It starts with instantiating
[`Setup`](src/Setup.php) object, and using its methods to set container's entries:
```php
<?php
use Polymorphine\Container\Setup;

require_once 'vendor/autoload.php';

$setup = new Setup();
````
Using `Setup::entry()` method:
```php
$setup->entry('value')->set('Hello world!');
$setup->entry('domain')->set('http://api.example.com');
$setup->entry('direct.object')->set(new ClassInstance());

$setup->entry('deferred')->invoke(function (ContainerInterface $c) {
    return new DeferredClassInstance($c->get('value'));
});

$setup->entry('composed.factory')->compose(ComposedClass::class, 'direct.object', 'deferred');
$setup->entry('factory.product')->create('composed.factory', 'create', 'domain');
```
Or passing array of [`Record`](src/Records/Record.php) instances to `Setup::records()`:
```php
// assumed Record namespace is also imported at the top
use Polymorphine\Container\Records\Record;
// ...

$setup->records([
    'value' => new Record\VelueRecord('Hello world!'),
    'domain' => new Recoed\ValueRecord('http://api.example.com');
    'direct.object' => new Record\VelueRecord(new ClassInstance()),
    'deferred' => new Record\CallbackRecord(function (ContainerInterface $c) {
         return new DeferredClassInstance($c->get('env.value'));
    },
    'composed.factory' => new Record\ComposeRecord(ComposedClass::class, 'direct.object', 'deferred'),
    'factory.product' => new Record\CreateMethodRecord('composed.factory', 'create', 'domain')
]);
```
And instantiate container:
```php
$container = $setup->container();
$container->has('composed.factory'); // true
$container->get('factory.product'); // return type of ComposedClass::create() method
```
`Setup::container()` may be called again with more entries added using `Setup` methods, but each time
new Container instance will be produced. Container entries stored in Setup instance may not be removed
or changed except with [*composed record*](#composed-entry---nested-composition--decorator-feature)
described below. It is recommended that access to [`Setup`](src/Setup.php) was encapsulated within
controlled scope - see: [Read and Write separation](#read-and-write-separation).

#### Records decide how it works internally
Values returned from Container are all wrapped into [`Record`](src/Records/Record.php) abstraction
that allows for different unwrapping strategies - it may be either returned directly or internally
created by calling its (lazy) initialization procedure. You may read DocBlock comments in provided
default [records](src/Records/Record) sourcecode to get more information. Here's short explanation of
package's Record implementations:

- `ValueRecord`: Direct value, that will be returned as it was passed (callbacks will be returned
  without evaluation as well). To push value record mapped to given `$id` into container with
  setup object use `set()` method:
  ```php
  $setup->entry(string $id)->set(mixed $value);
  ```
- `CallbackRecord`: Lazily invoked and cached value. Takes callback that will be given container
  as parameter, and value of this call will be cached and returned on subsequent calls. Records
  are added to setup with `invoke()` method:
  ```php
  $setup->entry(string $id)->invoke(callable $callback);
  ```
- `ComposeRecord`: Lazy instantiated (and cached) object of given class. Constructor parameters
  are passed as resolved aliases to other container entries. Setup with `compose()` method:
  ```php
  $setup->entry(string $id)->compose(string $class, string ...$dependencyRecords);
  ```
  With `Entry::compose()` method it is possible to build single entry in more than one
  call allowing to compose multi layered structure or decorate (wrap) existing entry
  with another composition ([read more](#composed-entry---nested-composition--decorator-feature))
- `CreateMethodRecord`: Similar to composition method, but object is created (and cached) by
  calling method given as string on container provided instance of factory, and container
  identifiers will resolve into arguments for this method. Setup with `create` method:
  ```php
  $setup->entry(string $id)->create(string $factoryId, string $factoryMethod, string ...$parameterIdentifiers);
  ```
Custom `Record` implementations might be mutable, return different values on subsequent
calls or introduce various side effects, but such use of container is not recommended.

#### Composite Container
Setup `Entry::container()` method can be used to add another ContainerInterface instances
allowing to create composite container wrapping multiple sub-containers which values (or
containers themselves) may be accessed with container's id prefix (dot notation):
```php
$setup->entry('env')->container($container);
...
$compositeContainer = $setup->container();
$compositeContainer->get('env') === $container; //true
$compositeContainer->get('env.some.id') === $container->get('some.id'); //true
```
Because the way enclosed containers are accessed and because they're stored separately
from Record instances some naming constraints are required:

>_Sub-container identifier MUST be a string, MUST NOT contain separator (`.` by default)
and MUST NOT be used as id prefix for stored `Record`._

Having container stored with `foo` identifier would make `foo.bar` record inaccessible.
The rules might be hard to follow with multiple entries and sub-containers, so runtime checks
were implemented. To instantiate `Setup` with integrity checks instantiate with static
constructor: `$setup = Setup::secure();` - more on that in following sections.

#### Preconfigured setup - constructor parameters
`Setup` may be instantiated with [`Setup\Collection`](src/Setup/Collection.php) parameter that
may contain already configured records or sub-containers. This collection can be also created
with associative arrays passed to static constructor `Setup::withData()`. Both `Collection` and
`Setup::withData()` method takes associative `Recrod[]` and `ContainerInetrface[]` arrays.

```php
$records = [
    'foo.bar' => new Record\ValueRecord(true),
    // ...
];
$containers = [
    'baz' => new ContainerImpl(),
    // ...
];

$setup = new Setup(new Setup\Collection($records, $containers));
// or
$setup = Setup::withData($records, $containers);
```
Of course using `Setup` makes no sense when all the records and containers are already in there
and no new entries will be added - you may instantiate container directly (see: [direct instantiation](#direct-instantiation--container-composition))
However, these parameters won't be validated to minimize performance cost - application won't
work with invalid configuration anyway. In development environment those checks are valuable,
because they allow to fail as early as possible and help localize the source of an error.

#### Secure setup & circular reference detection
Instantiating `Setup` with [`Setup\ValidatedCollection`](src/Setup/ValidatedCollection.php),
with `Setup::secure()` static constructor or passing `true` as third parameter of `Setup::withData()`
will enable runtime integrity checks for container configuration and detect circular references
when resolving dependencies with recursive container calls. Container will be created with
identifiers that will be accessible, **call stack** will be added to all exceptions thrown from
container, and `ContainerInterface::get()` method will throw `CircularReferenceException` immediately
after subsequent call would try to retrieve currently resolved record without blowing up the stack.

> This feature comes with minor performance overhead, and checking this kind of developer errors have
almost no value in production. It is recommended to use it as **development tool** only.

Checking such errors just because you can is pointless, because there are much more config related
bugs that cannot be checked in other way than testing if application works before deploying it to
production (invalid values or identifiers in container calling methods). On the other hand if those
checks are causing visible drop in performance you probably using container to extensively (see
[recommended use](#recommended-use) section).

#### Composed entry - nested composition & decorator feature:
Entry called for existing record can reassign it with `compose()` method if it uses it
recursively as one of its constructor parameters. This feature might be used as multi-level
composition for single entry or to decorate object from currently used container entry.

For example: Let's assume that in dev environment we want to log messages passed/returned
by a library stored at container's 'my.library' id:
```php
 $setup->entry('my.library')->invoke(function (ContainerInterface $c) {
     return new MyLibrary($c->get('myLib.dependency'), ...);
 });

 if ($env === 'develop') {
     $setup->entry('my.library')->commpose(LoggedMyLibrary::class, 'my.library', 'logger.object');
 }
```
Of course it should return the same type as overwritten record would, because all clients
currently using it would fail on type-checking, and due to lazy instantiation container
can't ensure decorator use and possible errors will emerge at runtime.

#### Direct instantiation & container composition
All `Setup` does, beside ability to validate configuration, is providing help to compose final container
based on provided configuration and chosen options. The simple container with `Record[]` array would be
instantiated like this:
```php
$container = new RecordContainer(new Records($records));
``` 
And here's an example composition of container with circular reference checking and encapsulated
sub-containers (`ContainerInterface[]` array):
```php
$container = new CompositeContainer(new TrackedRecords($records), $containers);
```

### Configuration Container

[`ConfigContainer`](src/ConfigContainer.php) that comes with this package is convenient
way to store and retrieve values from multidimensional associative arrays using path notation.
This container is instantiated directly with constructor, and values can be accessed by
separated keys on consecutive nesting levels. Example:
```php
$container = new ConfigContainer([
    'value' => 'Hello World!',
    'domain' => 'http://api.example.com',
    'pdo' => [
        'dsn' => 'mysql:dbname=testdb;host=localhost',
        'user' => 'root',
        'pass' => 'secret',
        'options' => [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    ]
]);

$container->get('pdo'); // ['dsn => 'mysql:dbname=testdb;host=localhost', 'user' => 'root', ...]
$container->get('pdo.user'); // root
$container->get('pdo.options'); // [ PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8', ... ]
 ```
As it was described in section on [composite container](#composite-container) you can use both
record-based and config container as a single Container using `Entry::container()` method:
```php
...
$setup = new Setup();
$setup->entry('env')->container($conatiner);
$setup->entry('direct.object')->set(new ClassInstance());
$setup->entry('deferred')->invoke(function (ContainerInterface $c) {
  return new DeferredClassInstance($c->get('env.value'));
});
$setup->entry('composed.factory')->compose(ComposedClass::class, 'direct.object', 'deferred');
$setup->entry('factory.product')->create('composed.factory', 'create', 'env.domain');

$container = $setup->container();
```
Note additional path prefixes for `value` and `domain` within `deferred` and `factory.product`
definitions compared to records used in first example. These values are now fetched from
`ConfigContainer` accessed with `env` prefix, so values can be retrieved from both config
and record containers:
```php
...
echo $container->get('env.value'); // Hello world!
echo $container->get('env.pdo.user'); // root
$object = $container->get('factory.product');
```
Object created with `$container->get('factory.product')` will be the same as:
```php
$factory = new ComposedClass(new ClassInstance(), new DeferredClassInstance('Hello World!'));
$object  = $factory->create('http://api.example.com');
```

### Recommended use

#### Read and Write separation
Calling `Setup::entry($name)` returns write-only `Entry` helper object. Beside providing
methods to inject new instances of `Record` implementations into container's `Records` it
also can isolate `Container` from scopes that should not be allowed to peek inside container
while allowing for its configuration.

To make use of this separation `Setup` should be encapsulated within object, that
allows its clients to configure container and grants its dependencies access only to container
instance. For example, if you have front controller bootstrap class similar to...
```php
class App implements RequestHandlerInterface
{
    private $containerSetup;

    public function __construct(...)
    {
        $this->containerSetup = Setup::withData(...);
    }

    public function config(string $name): Entry
    {
        return $this->containerSetup->entry($name);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface {
        $container = $this->containerSetup->container();
        ...
    }

    ...
}
```
Now You can push values into container from the scope of `App` class object, but cannot
access them afterwards:
```php
$app = new App(parse_ini_file('pdo.ini'));
$app->set('database')->invoke(function (ContainerInterface $c) {
    return new PDO(...$c->get('env.pdo'));
});
```
Nothing in outer scope will be able to use instance of container created within `App`.
It is possible to achieve with some configuration efforts, but this is not recommended
though, so it won't be covered in details.

#### Real advantage of container
##### Containers vs direct instantiation
Instantiating container with setup commands used in [first example](README.md#container-setup) and
getting `factory.product` object will be equivalent to factory instantiated directly with
`new` operator and calling `create()` method with `http://api.example.com` parameter:
```php
$factory = new ComposedClass(new ClassInstance(), new DeferredClassInstance('Hello world!'));
$object  = $factory->create('http://api.example.com');
```
As you can see the container does not give any visible advantage when it comes to creating object
directly. Assuming this object is used only in single use-case scenario there won't be any, but
for libraries used in various request contexts or reused in the structures where the same instance
should be passed (like database connection) having it configured in one place saves lots of trouble
and repetition. Suppose that class is some api library that requires configuration and composition
used by a few endpoints of your application - you would have to repeat this instantiation for each
endpoint.

##### Containers vs decomposed factories
Mentioned repetitiveness still can be avoided without reaching for container by encapsulating
instantiation within hardcoded factory class and single (static) call instead calling container;
However, you might not be able to tell up front which individual component of created object might
be needed elsewhere and it would require to extract it from existing factory - with container you
don't need to do that, because all components are also container's entries, and this flexibility
is the only advantage of containers that cannot be compensated.

##### Containers and Service locator anti-pattern
Factories mentioned above often utilize created object memoization (_Singleton Pattern_) and their
use should be limited to certain scope, but same applies to containers.
Containers shouldn't be injected as a wrapper providing direct (objects that will be called int the
same scope) dependencies of the object, because that will expose dependency on container while hiding
types of objects we really depend on.
It may seem appealing that we can freely inject lazily invoked objects with possibility of not using
them, but these unused objects, in vast majority of cases, should denote that our object's scope
is too broad. Branching that leads to skipping method call (OOP message sending) on one of dependencies
should be handled up front, which would make our class easy to read and test. Making exceptions for
sake of easier implementation will quickly turn into standard practice (especially within larger
or remote working teams), because consistency (with other bad code) will replace reasoning.

##### Container in factory is harmless
Dependency injection container should help with dependency injection, but not replace it.
It's fine to **inject container into factory objects**, because factory itself does not make calls on
objects container provides and it doesn't matter what objects factory is coupled to. Treat application
objects composition as a form of configuration.

#### Why no auto-wiring (yet)?
Explicitly hardcoded class compositions whether instantiated directly or indirectly through container
might be traded for convenient auto-wiring, but in my opinion its price includes important part of
polymorphism, which is resolving preconditions. This is not the price you pay up front, and while debt
itself is not inherently bad, forgetting you have one until you can't pay it back definitely is.
