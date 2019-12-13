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
- composition with sub-Containers
- optional path notation access to config values ([more](#configuration-container))
- dev mode for integrity checks, call stack tracking & circular reference protection ([more](#secure-setup--circular-reference-detection))
- explicit configuration by default - auto-wired dependencies can be resolved by custom strategies (not included)
- intended limited use for generic stuff: libraries, configuration and other context independent
  objects or functions ([more](#recommended-use))

### Installation with [Composer](https://getcomposer.org/)
    php composer.phar require polymorphine/container

### Container setup
This example will show how to set up simple container. It starts with instantiating
[`Setup`](src/Setup.php) type object, and using its methods to set container's entries:
```php
<?php
use Polymorphine\Container\Setup;

require_once 'vendor/autoload.php';

$setup = Setup::basic(); //or
$setup = new Setup\BasicSetup();

$setup->set('value')->value('Hello world!');
$setup->set('domain')->value('http://api.example.com');
$setup->set('direct.object')->value(new ClassInstance());

$setup->set('deferred.object')->callback(function (ContainerInterface $c) {
    return new DeferredClassInstance($c->get('value'));
});

$setup->set('composed.factory')->instance(ComposedClass::class, 'direct.object', 'deferred.object');
$setup->set('factory.product')->product('composed.factory', 'create', 'domain');

$container = $setup->container();
$container->has('composed.factory'); // true
$container->get('factory.product'); // return type of ComposedClass::create() method
```
`Setup::container()` may be called again with more entries added using `Setup` methods, but each time
new Container instance will be produced. Container entries stored in Setup instance may not be removed,
but can be changed preferably with [explicit method calls](#secure-setup--circular-reference-detection)
or decorated by [*composed record*](#composed-entries) described below. It is also recommended that
access to [`Setup`](src/Setup.php) was encapsulated within controlled scope - see:
[Read and Write separation](#read-and-write-separation).

Instead configuring each entry using builder methods you can pass [`Record`](src/Records/Record.php)
instance to `Setup::addRecord()` method or array of those to `Setup::records()`:
```php
<?php
use Polymorphine\Container\Setup;
use Polymorphine\Container\Records\Record;

$setup = Setup::basic();
$setup->addRecords([
    'value'            => new Record\ValueRecord('Hello world!'),
    'domain'           => new Record\ValueRecord('http://api.example.com'),
    'direct.object'    => new Record\ValueRecord(new ClassInstance()),
    'deferred.object'  => new Record\CallbackRecord(function (ContainerInterface $c) {
                              return new DeferredClassInstance($c->get('env.value'));
                          }),
    'composed.factory' => new Record\InstanceRecord(Factory::class, 'direct.object', 'deferred.object'),
    'factory.product'  => new Record\ProductRecord('composed.factory', 'create', 'domain')
]);

// ...and instantiate container identical to previous example
```

#### Records decide how it works internally
Values returned from Container are all wrapped into [`Record`](src/Records/Record.php) abstraction
that allows for different unwrapping strategies - it may be either returned directly or internally
created by calling its (lazy) initialization procedure. You may read DocBlock comments in provided
default [records](src/Records/Record) sourcecode to get more information. Here's short explanation of
package's Record implementations:

- `ValueRecord`: Direct value, that will be returned as it was passed (callbacks will be returned
  without evaluation as well). To push value record mapped to given string `identifier` into container
  with setup object use `Entry::value()` method:
  ```php
  $setup->set('identifier')->value($anything);
  ```
- `CallbackRecord`: Lazily invoked and cached value. Takes callback that will be given container
  as parameter, and value of this call will be cached and returned on subsequent calls. Records
  are added to setup with `Entry::callback()` method:
  ```php
  $setup->set('identifier')->callback(function ($container) { return ... });
  ```
- `InstanceRecord`: Lazy instantiated (and cached) object of given class. Constructor parameters
  are passed as resolved aliases to other container entries. Setup with `Entry::instance()` method:
  ```php
  $setup->set('identifier')->instance(Namespace\ClassName::class, 'dependency-identifier', 'another', ...);
  ```
- `ProductRecord`: Similar to instance method, but object is created (and cached) by
  calling method given as string on container provided instance of factory, and container
  identifiers will resolve into arguments for this method. Setup with `Entry::product()` method:
  ```php
  $setup->set('identifier')->create('factory.id', 'createMethod', 'container.param1', 'container.param2', ...);
  ```
- `ComposedInstanceRecord`: With `Entry::wrappedInstance()` method it is possible to build single
  entry in chained call allowing to compose multi layered structure of wrapped (decorated) instance
  entries ([read more](#composed-entries))

Custom `Record` implementations might be mutable, return different values on subsequent
calls or introduce various side effects, but such use of container is not recommended.

#### Composite Container
Both `Setup::addContainer()` and `Entry::container()` methods can be used to add another
ContainerInterface instances allowing to create composite container wrapping multiple
sub-containers which values (or containers themselves) may be accessed with container's
id prefix (dot notation):
```php
$subContainer = new PSRContainerImplementation();
$setup->set('env')->container($subContainer);

$container = $setup->container();
$container->get('env') === $subContainer; //true
$container->has('env.some.id') === $subContainer->has('some.id'); //true
```
Because the way enclosed containers are accessed and because they're stored separately
from Record instances some naming constraints are required:

>_Sub-container identifier MUST be a string, MUST NOT contain separator (`.` by default)
and MUST NOT be used as id prefix for stored `Record`._

Having container stored with `foo` identifier would make `foo.bar` record inaccessible.
The rules might be hard to follow with multiple entries and sub-containers, so runtime checks
were implemented. To instantiate `Setup` with integrity checks use another static constructor:
```php
$setup = Setup::validated(); //or
$setup = new Setup\ValidatedSetup();
```
More on that in following sections.

#### Preconfigured setup - constructor parameters
Implementations of abstract `Setup` may be instantiated with already configured records or
sub-containers as associative arrays passed to its constructors or static methods. Both will
take `Recrod[]` and `ContainerInetrface[]` arrays.

```php
$records = [
    'foo.bar' => new Record\ValueRecord(true),
    // ...
];
$containers = [
    'baz' => new PSRContainerImplementation(),
    // ...
];

$setup = Setup::basic($records, $containers);
```
Of course using `Setup` makes no sense when all the records and containers are already in
there and no new entries will be added (unless for runtime checks) - you may instantiate
container directly (see: [direct instantiation](#direct-instantiation--container-composition)).
Parameters for container won't be validated. This would be unnecessary performance cost,
because application wouldn't work with invalid configuration anyway. However, In development
environment those checks are valuable, because they allow to fail as early as possible and
help localize the source of an error.

#### Secure setup & circular reference detection
##### Inaccessible or accidentally overwritten entries
`BasicSetup` (instantiated directly or with `Setup::basic()` method) won't check if given
identifiers are already defined or whether they will cause name collision making some entries
inaccessible (sub-containers with identifier used record entry prefix). It is possible to
overwrite defined entries, but it is recommended to use explicit _replace_ methods like
`Setup::replace()`, `Setup::replaceRecord()` and `Setup::replaceContainer()` corresponding to
`Setup::add()`, `Setup::addRecord()` and `Setup::addContainer()`, respectively. That's because
it allows you to configure container using `ValidatedSetup`.

Instantiating `ValidatedSetup` - directly or with `Setup::validated()` method - will enable
runtime integrity checks for container configuration, and make sure that all defined identifiers
can be accessed with `ContainerInterface::get()` method. It will also detect potentially redundant
and suspicious usages like overwriting previously defined entry (passed through constructor) or
calling one of _replace_ methods overwriting entry when it wasn't yet defined.

##### Circular references
Records may refer to other container entries to be built (instantiated), but you could configure
entry `A` in a way that it will try to retrieve itself during build process starting endless loop
and eventually blowing up the stack - for example:
```php
$setup->set('A')->compose(SomeClass::class, 'B');
$setup->set('B')->compose(AnotherClass::class, 'C', 'A');
```
Another feature that `ValidatedSetup` comes with is building container able to detect those circular
references and append call stack information to exceptions being thrown (for both circular references
and missing entries). `ContainerInterface::get()` would throw `CircularReferenceException` immediately
after recursive container call on deeper level would try to retrieve currently resolved record, which
will allow to exit the endless loop.

> These checks are not included in `BasicSetup`, because they should not be required in production
environment. Although it is recommended to use them during **development**.

Integration tests are necessary in development, because misconfigured container will most likely crash
the application, and it cannot be controlled by code anyway. This way some needless performance overhead
might be eliminated from production, but if those checks are causing visible drop in performance you are
probably using container too extensively (see [recommended use](#recommended-use) section).

#### Composed entries
##### Record composition using Wrapper
Entry may be built with multiple instance descriptors (same parameters as `InstanceRecord` uses)
given in chained [`Wrapper`](src/Setup/Wrapper.php) calls:
```php
$setup->set('A')
      ->wrappedInstance(SomeClass::class, 'B', 'C')
      ->with(AnotherClass::class, 'A', 'D')
      ->with(AndAnother::class, 'A')
      ->compose();
``` 
Notice that _wrapper definition contains reference to wrapped entry_ as one of its dependencies.
Without it exception will be thrown because it wouldn't constitute composition - just a definition of
different instance that should be defined with `Entry::instance()` method. This self-reference will
not cause circular reference error because it isn't used as container call (as identifiers for other
dependencies), but a placeholder pointing wrapped entry in composition process.

##### Decorating defined Records
`Wrapper` can be also used to decorate existing record by calling `Setup::decorate()` method and, as
previously, using self-reference as one of its constructor parameters. Let's assume for example that
in dev environment we want to log messages passed/returned by a library defined as 'my.library' record:
```php
 $setup->set('my.library')->callback(function (ContainerInterface $c) {
     return new MyLibrary($c->get('myLib.dependency'), ...);
 });

 if ($env === 'develop') {
     $setup->decorate('my.library')
           ->with(LoggedMyLibrary::class, 'my.library', 'logger.object')
           ->compose();
 }
```
Of course it should return the same type as overwritten record would, because all clients
currently using it would crash (fail on type-checking). Unfortunately due to lazy instantiation
container can't ensure correct decorator use and errors caused by hacks will emerge at runtime.

#### Direct instantiation & container composition
All `Setup` does, beside ability to validate configuration with `ValidatedSetup`, is providing helper
methods creating `Record` and sub-container entries creating various container compositions based on called
methods. Container can also be instantiated directly - for example simple container containing only `Record`
entries would be instantiated with as flat `Record[]` array (stored in `$records`) this way:
```php
$container = new RecordContainer(new Records($records));
``` 
When container needs circular reference checking and encapsulate some sub-containers (stored in `$containers`
variable as flat `ContainerInterface[]` array) its instantiation would change into this composition:
```php
$container = new CompositeContainer(new TrackedRecords($records), $containers);
```

### Configuration Container

[`ConfigContainer`](src/ConfigContainer.php) that comes with this package is convenient
way to store and retrieve values from multidimensional associative arrays using path notation.
This container is instantiated directly with array passed to constructor, which values can be
accessed by dot-separated keys on consecutive nesting levels. Example:
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
record-based and config container as a single Container using `Entry::container()` or
`Setup::addContainer()` method:
```php
...
$setup = new Setup();
$setup->set('env')->container($conatiner);
$setup->set('direct.object')->value(new ClassInstance());
$setup->set('deferred.object')->callback(function (ContainerInterface $c) {
  return new DeferredClassInstance($c->get('env.value'));
});
$setup->set('factory.object')->instance(FactoryClass::class, 'direct.object', 'deferred.object');
$setup->set('factory.product')->product('factory.object', 'create', 'env.domain');

$container = $setup->container();
```
Note additional path prefixes for `value` and `domain` within `deferred.object` and `factory.product`
definitions compared to records used in previous example. These values are still fetched from
`ConfigContainer`, but accessed through composite container using `env` prefix. This way values from
both config and record containers encapsulated inside composite container can be retrieved:
```php
...
echo $container->get('env.value'); // Hello world!
echo $container->get('env.pdo.user'); // root
$object = $container->get('factory.product');
```
Object created with `$container->get('factory.product')` will be the same as instantiated objects
directly using `new` operator as shown in [Containers vs direct instantiation](#containers-vs-decomposed-factories)
section with some more extended take on the subject.

### Recommended use

#### Read and Write separation
`Setup` builder-like API allows for setting up container and creating its instance, but it
would result in cleaner design to encapsulate container, while giving possibility to configure
it. This could be achieved by proxy object exposing only setup methods, which would also allow to
limit unwanted _replace_ features.

Calling `Setup::add()` or `Setup::replace()` returns write-only `Entry` helper object. Beside
providing methods to define various implementations of `Record` or sub-containers for configured
_container_ it allows to implement proxy with single method for each helper instead polluting
top layer api with multiple setup methods. For example, if you have front controller bootstrap
class similar to...
```php
class App
{
    private $setup;
    ...
    public function config(string $name): Entry
    {
        return $this->setup->set($name);
    }
    ...
}
```
Now You can push values into container from the scope of `App` class object, but cannot
access container afterwards:
```php
$app = new App(parse_ini_file('pdo.ini'));
$app->config('database')->callback(function (ContainerInterface $c) {
    return new PDO(...$c->get('env.pdo'));
});
```
Nothing in outer scope will be able to use instance of container created within `App`.
It is possible to achieve with some configuration efforts, but this is not recommended
though, so it won't be covered in details.

Example above allows only for adding new entries. To enable App client to decorate or
replace stored entries `Setup::replace()` or `Setup::decorate()` methods should be added.

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
is the only advantage of containers that cannot be compensated. For example authentication service
might use session, so it's not enough to write factory for auth service, but one for session is
also needed, because the latter might be used in different context - bunch of singleton factories
would become hard to maintain.

##### Containers and Service locator anti-pattern
Factories mentioned above often utilize created object memoization (_Singleton Pattern_) and their
use should be limited to certain scope, but same applies to containers.
Containers shouldn't be injected as a wrapper providing direct (objects that will be called in the
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
