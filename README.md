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
use Polymorphine\Container\Setup;

$setup = Setup::production();
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
$container->get('factory.product'); // return result of ComposedClass::create() call
```
Instead configuring each entry using builder methods you can pass arrays of [`Record`](src/Records/Record.php)
instances to one of `Setup` constructors:
```php
$setup = Setup::production([
   'value'            => new Record\ValueRecord('Hello world!'),
   'domain'           => new Record\ValueRecord('http://api.example.com'),
   'direct.object'    => new Record\ValueRecord(new ClassInstance()),
   'deferred.object'  => new Record\CallbackRecord(function (ContainerInterface $c) {
                             return new DeferredClassInstance($c->get('env.value'));
                         }),
   'composed.factory' => new Record\InstanceRecord(Factory::class, 'direct.object', 'deferred.object'),
   'factory.product'  => new Record\ProductRecord('composed.factory', 'create', 'domain')
]);

// add more entries here with set() methods
// and instantiate container...

$container = $setup->container();
```
Of course if all entries will be added with constructor `Setup` instantiation is not even necessary,
and [Instantiating Container directly](#direct-instantiation--container-composition) might be better
idea.

`Setup::container()` may be called again after more entries were added, but the call will return new,
independent container instance. Container entries stored in Setup instance may not be removed, but
can be changed or decorated with [explicit method calls](#secure-setup--circular-reference-detection).
It is also recommended to encapsulate [`Setup`](src/Setup.php) within controlled scope ad described in
section on [read and Write separation](#read-and-write-separation).

#### Records decide how it works internally
Values returned from Container are initially wrapped into [`Record`](src/Records/Record.php) abstraction
that allows for different strategies to produce them - it may be either returned directly or internally
created by calling its (lazy) initialization procedure. Here's short explanation of package's Record
implementations:

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

Custom `Record` implementations might be mutable, return different values on subsequent calls
or introduce various side effects, but it is not recommended.

#### Overwriting setup entries
Calling `Setup::set()` method will throw exception when given identifier is already defined.
This way it will be assured that no unused entries were defined, and depending on config
definitions are consistent with the ones used later in code.

It is possible to overwrite existing entry with explicit `Setup::replace()` method. You can also
redefine setup entry with `Setup::decorate()` method that will wrap it with an object decorating it.
The second feature is described in [decorating defined records](#decorating-defined-records) section.
These methods will throw exception when replaced entry is not yet defined and although there might be
some use cases where object decoration needs to be resolved in configuration scope, both are meant to
be used primarily in **development environment**. As soon final entry value can be established it
should be passed to constructor or defined with `Setup::set()` method.

The only method that is safe to work with unknown configuration state is `Setup::fallback()`. It will
ignore given entry definition if its identifier was already defined. This method might be used in
production environment or live server testing stage where config changes are being made, and application
errors (using error handlers) need to be avoided by replacing it with some information output or simplified
version.

#### Composed entries
##### Record composition using Wrapper
Entry may be built with multiple instance descriptors (same parameters as `InstanceRecord` uses)
created in chained [`Wrapper`](src/Setup/Wrapper.php) calls:
```php
$setup->set('A')
      ->wrappedInstance(SomeClass::class, 'B', 'C')
      ->with(AnotherClass::class, 'A', 'D')
      ->with(AndAnother::class, 'A')
      ->compose();
``` 
Notice that _wrapper definition contains reference to wrapped entry_ as one of its dependencies.
Without it exception will be thrown because it wouldn't constitute composition but definition of
different instance that should've been defined with `Entry::instance()` method. This self-reference
will not cause circular calls because it isn't used as standalone container entry (as identifiers for
other dependencies), but a placeholder pointing wrapped instance in composition process.

##### Decorating defined Records
`Wrapper` can be also used to decorate existing record by calling `Setup::decorate()` method and, as
previously, using self-reference as one of its constructor parameters. Let's assume for example that
in development environment we want to log events of passing/returning messages by a library defined
as 'my.library' record - here's how adding such feature might look like:
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
Of course it should return the same type as overwritten record would - otherwise all clients
currently using it would crash (fail on type-checking). Unfortunately due to lazy instantiation
container can't ensure correct decorator use and errors caused by hacks will emerge at runtime.


#### Composite Container
`Entry::container()` method can be used to add another ContainerInterface instances and create
composite container by wrapping multiple sub-containers which values (or containers themselves)
may be accessed with container's id prefix (dot notation):
```php
$subContainer = new PSRContainerImplementation();
$setup->set('env')->container($subContainer);

$container = $setup->container();
$container->get('env') === $subContainer; //true
$container->has('env.some.id') === $subContainer->has('some.id'); //true
```
Passing array of ContainerInterface instances together with records `Setup` will also build
composite container:
```php
$setup = Setup::production($records, ['env' => new PSRContainerImplementation()]);
```

#### Secure setup & circular reference detection
Secure setup is designed as a development tool that helps with setup debugging. It is instantiated
either with `development` static constructor:
```php
$setup = Setup::development($records, $containers);
```
or with [`ValidatedBuild`](src/Setup/Build/ValidatedBuild.php) instance passed to default constructor:
```php
$setup = new Setup(new Setup\Build\ValidatedBuild($records, $containers));
```

##### Naming rules and inaccessible entries
Because the way enclosed containers are accessed and because they're stored separately from
Record instances some naming constraints are required:

>_Sub-container identifier MUST be a string, MUST NOT contain separator (`.` by default)
and MUST NOT be used as id prefix for stored `Record`._

Having container stored with `foo` identifier would make `foo.bar` record inaccessible, because
this value would be assumed to come from `foo` container. The rules might be hard to follow with
multiple entries and sub-containers, so runtime checks were implemented.

Basic (production) `Setup` instantiated directly or with `Setup::production()` method won't check
whether given identifiers are already defined or whether they will cause name collision that would
make some entries inaccessible (sub-containers with identifier used record entry prefix).

Instantiating validated `Setup::development()` or directly with `ValidatedBuild` instance will enable
runtime integrity checks for container configuration, and make sure that all defined identifiers
can be accessed with `ContainerInterface::get()` method.

##### Circular references
Because Records may refer to other container entries to be built (instantiated) a hard to spot
bug might be introduced where entry `A` in order to be resolved will need to retrieve itself during
build process starting endless loop and eventually blowing up the stack. For example:
```php
$setup->set('A')->instance(SomeClass::class, 'B');
$setup->set('B')->instance(AnotherClass::class, 'C', 'A');
```
Both entries `A` and `B` refer each other, so instantiating `B` would need `A` that will attempt to
instantiate `B` in nested context. Neither class can be instantiated, because its dependencies cannot
be fully resolved (are currently being resolved on higher context level) - without detection the
instantiation process would continue until call stack is overflown.

Container able to detect those circular references and append call stack information to exceptions being
thrown (for both circular references and missing entries) is another feature that `development` setup comes
with.  `ContainerInterface::get()` would throw `CircularReferenceException` immediately after recursive
container call on deeper context level would try to retrieve currently resolved record, which will allow
to exit the endless loop.

> These checks are not included in `Setup::production()`, because they should not be required in production
environment. Although it is recommended to use them during **development**.

Integration tests are necessary in development, because misconfigured container will most likely crash
the application, and it cannot be controlled by code in reliable way. Development setup will not prevent
all the bugs that might happen, so it becomes needless performance overhead in production environment.
It's worth noticing however, that visible drop in performance by using those checks in development stage
will most likely mean that container is used too extensively - see [recommended use](#recommended-use) section.

#### Direct instantiation & container composition
`Setup` provides helper methods to create `Record` instances and collect them together, optionally with
sub-container entries and additional validation checks creating immutable container composition.
Creating container directly is also possible - for example simple container containing only `Record`
entries would be instantiated with as flat `Record[]` array (here stored in `$records` variable) this way:
```php
$container = new RecordContainer(new Records($records));
``` 
When container needs circular reference checking and encapsulate some sub-containers stored in `$containers`
variable as flat `ContainerInterface[]` array its instantiation would change into this composition:
```php
$container = new CompositeContainer(new TrackedRecords($records), $containers);
```

### Configuration Container

[`ConfigContainer`](src/ConfigContainer.php) that comes with this package is a convenient
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
As it was described in [composite container](#composite-container) section you can use both
record-based and config container as a single Container using `Entry::container()` method.
Having above `$container` defined, you can recreate main example which uses its `value` and
`domain` entries:
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
definitions compared to records used in original example. These values are still fetched from
`ConfigContainer`, but accessed through composite container using `env` prefix. This way values from
both config and record containers encapsulated inside composite container can be retrieved:
```php
...
echo $container->get('env.value'); // Hello world!
echo $container->get('env.pdo.user'); // root
$object = $container->get('factory.product');
```
Object created with `$container->get('factory.product')` will be the same as instantiated objects
directly using `new` operator shown in [Containers vs direct instantiation](#containers-vs-decomposed-factories)
section with extended take on the subject.

### Recommended use

#### Read and Write separation
`Setup` builder-like API allows for setting up container and creating its instance, but it
would result in cleaner design to have container encapsulated and still be able to configure it
from outside scope. This could be achieved by proxy object exposing only setup methods.

Calling `Setup::set()` returns write-only `Entry` helper object. Beside providing methods to
define various implementations of `Record` or sub-containers for configured _container_ it allows
to implement proxy with single method instead polluting its interface with multiple setup methods.
For example, if you have front controller bootstrap class similar to...
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
...you can still use all helper methods provided by `Entry` object. Now You can push values into
container from the scope of `App` class object, but cannot access container afterwards. `App`
controls the `Setup` and will call `Setup::container()` to use on its own terms.
```php
$app = new App(parse_ini_file('pdo.ini'));
$app->config('database')->callback(function (ContainerInterface $c) {
    return new PDO(...$c->get('env.pdo'));
});
```
Nothing in outer scope will be able to use instance of container created within `App`.
It is possible to achieve with some configuration efforts, but this is not recommended,
so details won't be explained here.

#### Real advantage of container
##### Containers vs direct instantiation
Instantiating container with setup commands used in [main example](README.md#container-setup) and
getting `factory.product` object will be equivalent to factory instantiated directly with
`new` operator and calling `create()` method on it with `http://api.example.com` parameter:
```php
$factory = new ComposedClass(new ClassInstance(), new DeferredClassInstance('Hello world!'));
$object  = $factory->create('http://api.example.com');
```
As you can see the container does not give any visible advantage here over creating object directly,
and assuming this object is used only in single use-case scenario there won't be any.

For libraries used in various request contexts or reused in the structures where the same instance
should be passed (like database connection) having it configured in one place saves lots of trouble
and repetition. Suppose that class is some api library that requires configuration and composition
used by a few endpoints of your application - you would have to repeat this instantiation for each
endpoint. You can still solve this problem encapsulating instantiation within hardcoded factory
class and replace `$container->get()` with single (static) call (and type-hinted result!)

##### Containers vs decomposed factories and Singleton pattern
Mentioned factories introduce another problem though. You might not be able to tell up front which
individual component of created object might be needed elsewhere and it would be necessary to extract
it from existing factory into another factory and call it in both places - the factory it was extracted
from and the other part that needs this component. When the same instance is needed such factory in
some cases would need to cache created object - most probably using [_Singleton pattern_](https://en.wikipedia.org/wiki/Singleton_pattern).

_Singleton pattern_ is needed when same object needs to be provided in different scopes of the code.
When only single factory uses it there's no need for singleton. The number of injection points doesn't
matter since it can be passed in all of them as previously created local variable. Singleton pattern
objects are available in global scope for any part of the code and this makes them hard to maintain
since you don't really know where it is or will be used.

For example authentication service might use session, so it's not enough to write factory for auth
service, but one for session is also needed, because session might be used not only in many different
contexts, but also in different application scopes (building both middleware and use case compositions).
Having a number of singleton factories called in multiple places results in hard to comprehend code
even when they're used in disciplined manner - that is only in composition layer ("main" partition).

Container solves both decomposition and scope control problem, because all components can also be
container entries and it's usage scope is strictly limited to the places it was injected. This
flexibility is the only advantage of (standard) containers that cannot be easily replaced in other
way. However some discipline regarding containers is also required. 

##### Containers and Service locator anti-pattern
Containers shouldn't be injected as a wrapper providing direct (objects that will be called in the
same scope) dependencies of the object, because that will expose dependency on container while hiding
types of objects we really depend on.
It may seem appealing that we can freely inject lazily invoked objects with possibility of not using
them, but these unused objects, in vast majority of cases, should denote that our object's scope
is too broad. Branching that leads to skipping method call (OOP message sending) on one of dependencies
should be handled up front, which would make our class easy to test and read. Making exceptions for
sake of easier implementation will quickly turn into standard practice (especially within larger
or remote working teams), because consistency seems plausible even when it concerns bad practices.
Healthy constraints are more reliable than expected reasoning.

##### Container in factory is harmless
Dependency injection container should help with dependency injection, but not replace it. It's fine to
**inject container into main factory objects** in framework controlled scope, because factory itself
does not make calls on objects container provides and it doesn't matter what objects factory is coupled to.
Treat application objects composition as a form of configuration.

#### Why no auto-wiring (yet)?
Explicitly hardcoded class compositions whether instantiated directly or indirectly through container
might be traded for convenient auto-wiring, but in my opinion its cost includes important part of
polymorphism, which is resolving preconditions. This is not the price you pay up front, and while debt
itself is not inherently bad, forgetting you have one until you can't pay it back definitely is.
