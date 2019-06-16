# Polymorphine/Container
[![Build status](https://travis-ci.org/shudd3r/polymorphine-container.svg?branch=master)](https://travis-ci.org/shudd3r/polymorphine-container)
[![Coverage status](https://coveralls.io/repos/github/shudd3r/polymorphine-container/badge.svg?branch=master)](https://coveralls.io/github/shudd3r/polymorphine-container?branch=master)
[![PHP version](https://img.shields.io/packagist/php-v/polymorphine/container.svg)](https://packagist.org/packages/polymorphine/container)
[![LICENSE](https://img.shields.io/github/license/shudd3r/polymorphine-container.svg?color=blue)](LICENSE)
### PSR-11 Container for Dependencies and Configuration

#### Concept features:
- immutability & PSR-11 compatibility
- encapsulated configuration ([more](#read-and-write-separation))
- abstract strategy to retrieve stored values ready to extend functionality with some original
  built-in strategies ([more](#records-decide-how-it-works-internally))
- optional path notation access to config values ([more](#configuration-container))
- dev mode for call stack tracking & circular reference protection ([more](#call-stack-tracking-and-circular-reference-protection))
- explicit configuration by default (auto-wired dependencies can be resolved by custom strategies)
- intended limited use for generic stuff: libraries, configuration and other context independent
  objects or functions ([more](#recommended-use))

### Installation with [Composer](https://getcomposer.org/)
    php composer.phar require polymorphine/container

### Container Setup
This example will show how to set up simple container. It starts with instantiating
[`ContainerSetup`](src/Setup/ContainerSetup.php) object, and using its methods to set
container's entries:

    <?php
    use Polymorphine\Container\Setup\ContainerSetup;

    require_once __DIR__ . '/vendor/autoload.php';
    
    $setup = new ContainerSetup();

Using `ContainerSetup::entry()` method:

    $setup->entry('value')->set('Hello world!');
    $setup->entry('domain')->set('http://api.example.com');
    $setup->entry('direct.object')->set(new ClassInstance());

    $setup->entry('deferred')->invoke(function (ContainerInterface $c) {
        return new DeferredClassInstance($c->get('value'));
    });

    $setup->entry('composed.factory')->compose(ComposedClass::class, 'direct.object', 'deferred');
    $setup->entry('factory.product')->create('composed.factory', 'create', 'domain');

Or passing array of [`Record`](src/Setup/Record.php) instances:

    // assumed Record namespace is also imported at the top
    use Polymorphine\Container\Setup\Record;
    ...
    
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

And instantiate container:

    $container = $setup->container();
    $container->has('composed.factory'); // true
    $container->get('factory.product'); // return type of ComposedClass::create() method

Container may be instantiated before adding any entries, and using `ContainerSetup` this
way Container instance will be mutable only through its setter methods and once stored values
will not be overwritten (except [*decorator feature*](#mutable-record---decorator-feature) described below).
It is recommended that access to [`ContainerSetup`](src/Setup/ContainerSetup.php) was encapsulated
within controlled scope - see: [Read and Write separation](#read-and-write-separation)

#### Records decide how it works internally
Values returned from Container are all wrapped into [`Record`](src/Setup/Record.php) abstraction
that allows for different unwrapping strategies - it may be either returned directly or internally
created by calling its (lazy) initialization procedure. You may read DocBlock comments in provided
default [records](src/Setup/Record) sourcecode to get more information. Here's short explanation of
package's Record implementations:

- `ValueRecord`: Just a value, that will be returned as it was passed (callbacks will be returned
  without evaluation as well). To push value record mapped to given `$id` into container with
  setup object use `set` method:
      
      $setup->entry(string $id)->set(mixed $value);

- `CallbackRecord`: Lazily invoked value cache. Takes callable that will be called with
  `ContainerInterface` as parameter, and value of this call will be stored and returned on
  subsequent calls. Setup with `invoke` method:
  
      $setup->entry(string $id)->invoke(callable $callback);
  
- `ComposeRecord`: Lazy instantiated object of given class. Constructor parameters are passed
  as aliases to other container entries. Setup - `compose` method:
  
      $setup->entry(string $id)->compose(string $class, string ...$dependencyRecords);

- `CreateMethodRecord`: Object created by calling method (given as string) on container provided
  instance of factory with container identifiers as arguments for this method. To configure this
  record with setup use `create` method:
  
      $setup->entry(string $id)->create(string $factoryId, string $factoryMethod, string ...$parameterIdentifiers);

Stateful nature of custom `Record` implementation might return different values on subsequent
calls or have various side effects, but it is advised against using container this way.

#### Mutable record - Decorator feature:
`ComposeRecord` can reassign existing entry if it's also used as
one of its constructor parameters. Let's assume for example that in dev environment we want
to log messages passed/returned by a library stored at container's 'my.library' id:

     $setup->entry('my.library')->invoke(function (ContainerInterface $c) {
         return new MyLibrary($c->get('myLib.dependency'), ...);
     });

     if ($env === 'develop') {
         $setup->entry('my.library')->commpose(LoggedMyLibrary::class, 'my.library', 'logger.object');
     }

Of course it should return the same type as overwritten record would, because all clients
currently using it would fail on type-checking, but due to lazy instantiation container
can't ensure valid use and possible errors will emerge at runtime.

#### Preconfigured instantiation methods
Using only `ContainerSetup` to set records Container will always be in valid state, because
method type hints and id validation will ensure that. Those checks can hit efficiency for
large number of records, so you might want to create instance directly or passing predefined
[`RecordCollection`](src/Setup/RecordCollection.php) into setup constructor (its good to have
some integration tests in place). This collection is the same instance that Container uses,
except setup has write access to it, and container is read only. Setup comes with two static
constructors that will instantiate collection for you: `RecordSetup::prebuilt()` and
`RecordSetup::withConfig()`. Both allow setting up secondary container (explained in next
section) access. Look at [`ContainerSetup`](src/Setup/ContainerSetup.php) static constructors
for details on composition of `RecordCollection` object.


### Secondary Container

Passing [`CombinedRecordCollection`](src/Setup/CombinedRecordCollection.php) to `ContainerSetup`
or `RecordContainer` constructor instead its base class (`RecordCollection`) allows accessing
Container with different behaviour than default one (using records) when its contents is
retrieved with defined id prefix. You may use (static) named constructor to pass both associated
array of `Record` instances and secondary container with its identifier prefix (default: `.`):

    $setup = ContainerSetup::prebuilt($records, ContainerInterface $container, string $prefix);
    
Assuming `env.` prefix, entries received with `$setup->container()->get('env.someId')` will be
delivered from passed `$container` instance with `someId` key.

#### Configuration Container
[`ConfigContainer`](src/ConfigContainer.php) that comes with this package is convenient
way to store and retrieve values from multidimensional associative arrays using path notation.
This container is instantiated directly with constructor, and values can be accessed by
separated keys on consecutive nesting levels. Example:

    $container = new ConfigContainer([
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
 
#### Combine RecordContainer with ConfigContainer
As it was described in introduction to this section `ContainerSetup` can be instantiated with
secondary container. Here's an example of container combined from previous examples:
    
    $config = [
        'value' => 'Hello world!',
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
    ];
    
    $records = [
        'direct.object' => new Record/VelueRecord(new ClassInstance()),
        'deferred' => new Record/CallbackRecord(function (ContainerInterface $c) {
            return new DeferredClassInstance($c->get('env.value'));
        },
        'composed.factory' => new Record/ComposeRecord(ComposedClass::class, 'direct.object', 'deferred'),
        'factory.product' => new Record/CreateMethodRecord('create@composed.factory', 'env.domain')                  
    ];

Note additional path prefixes for `value` and `domain` within `deferred` and `factory.product`
definitions compared to records used in first example. These values are now fetched from
`ConfigContainer`, and it's prefix will be defined as `env.`:

    $setup = ContainerSetup::withConfig($config, 'env.');
    $setup->records($records);
    
Alternatively, but without type and id collisions checking:

    $setup = ContainerSetup::prebuilt($records, new ConfigContainer($config), '.env');

Now values can be retrieved from both Config and Record containers:
     
    $container = $setup->container();
    $container->get('env.value'); // Hello world!
    $container->get('env.pdo.user'); // root
    $container->get('factory.product') // ComposedClass::create() method return type


### Read and Write separation

Calling `ContainerSetup::entry($name)` returns `RecordSetup` helper object. Beside providing
methods to inject new instances of `Record` implementations into `RecordCollection` it also
isolates `Container` from scopes that should not be allowed to peek inside container by
calling `ContainerSetup::container()` method. This way container instance stays read only,
and `RecordSetup` write only object.

To make use of this separation `ContainerSetup` should be encapsulated within object, that
allows its clients to configure container and grants its dependencies access only to container
instance. For example, if you have front controller bootstrap class similar to...

    class App implements RequestHandlerInterface
    {
        private $containerSetup;
        
        public function __construct(array $config = [])
        {
            $this->containerSetup = ContainerSetup::withConfig($config, 'env.');
        }
        
        public function set(string $name): RecordSetup
        {
            return $this->containerSetup->entry($name);
        }
        
        public function handle(ServerRequestInterface $request): ResponseInterface {
            $container = $this->containerSetup->container();
            //...
        }
        
        ...
    }

Now You can push values into container from the scope of `App` class object, but cannot
access them, which provides by-design separation of concerns:

    $app = new App(parse_ini_file('pdo.ini'));
    $app->set('database')->invoke(function (ContainerInterface $c) {
        return new PDO(...$c->get('env.pdo')); 
    });

Nothing in outer scope will be able to use instance of `Container` created within `App`.
It is possible to achieve, but it would need passing stateful object identifier that can
return container through one of its methods. This is not recommended though, so it won't
be covered in details.


### Call stack tracking and circular reference protection

Calling `ContainerSetup::container()` method with `true` parameter will instantiate
[`TrackingRecordContainer`](src/TrackingRecordContainer.php) that will track called
dependencies and append path of used references to exception messages and throw
`CircularReferenceException` when subsequent call would try to retrieve currently
created record.

This feature should be treated as **development tool** and self-constraint so that
container was not overused. It may help to locate an error in composition structure,
but it comes with performance cost.

Some legitimate cases might become tricky to implement because object invoked from
container may sometimes need to call for its instance at runtime - for example router
built using container endpoints that use same router to produce urls (producing following call
stack: `router->endpoint->view-model->router`). To prevent circular reference (detection)
router should be called directly in front controller layer (not retrieved from container)
or should be built using container instance from parent (setup) scope instead callback parameter.

    // anonymous function will be called with container parameter,
    // but we're using inherited $setup instead:
    $setup->entry('router')->invoke(function () use ($setup) {
        return new Router($setup->container(true));
    });

### Recommended use

#### Using container vs direct instantiation
Instantiating container with setup commands used in [first example](README.md#container-setup) and
getting `factory.product` object will be equivalent to factory instantiated directly with
`new` operator and calling `create()` method with `http://api.example.com` parameter:

    $factory = new ComposedClass(new ClassInstance(), new DeferredClassInstance('Hello world!'));
    $object  = $factory->create('http://api.example.com');
    
As you can see the container does not give any visible advantage when it comes to creating object
directly. Assuming this object is used only in single use-case scenario there won't be any, but
for libraries used in various request contexts or reused in the structures where the same instance
should be passed (like database connection) having it configured in one place saves lots of trouble
and repetition. Suppose that class is some api library that requires configuration and composition
used by a few endpoints of your application - you would have to repeat this instantiation for each
endpoint.

#### Containers and Service locator anti-pattern
Containers shouldn't be injected as a wrapper providing direct (objects that will be called int the
same scope) dependencies of the object, because that will expose dependency on container while hiding
types of objects we really depend on.
It may seem appealing that we can freely inject lazily invoked objects with possibility of not using
them, but these unused objects, in vast majority of cases, should denote that our object's scope
is too broad. Branching that leads to skipping method call (OOP message sending) on one of dependencies
should be handled up front, which would make our class easy to read and test. Making exceptions for
sake of easier implementation will quickly turn into standard practice (especially within larger
or remote working teams), because consistency (with other bad code) will replace reasoning.

#### Container in factory is harmless
Dependency injection container should help with dependency injection, but not replace it.
It's fine to **inject container into factory objects**, because factory itself does not make calls on
objects container provides and it doesn't matter what objects factory is coupled to. Treat application
objects composition as a form of configuration.

#### Why no auto-wiring (yet)?
Explicitly hardcoded class compositions whether instantiated directly or indirectly through container
might be traded for convenient auto-wiring, but in my opinion its price includes important part of
polymorphism, which is resolving preconditions. This is not the price you pay up front, and while debt
itself is not inherently bad, forgetting you have one until you can't pay it back definitely is.
