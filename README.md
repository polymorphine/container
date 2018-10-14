# Polymorphine/Container
[![Build Status](https://travis-ci.org/shudd3r/polymorphine-container.svg?branch=master)](https://travis-ci.org/shudd3r/polymorphine-container)
[![Coverage Status](https://coveralls.io/repos/github/shudd3r/polymorphine-container/badge.svg?branch=master)](https://coveralls.io/github/shudd3r/polymorphine-container?branch=master)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/polymorphine/container/dev-master.svg)](https://packagist.org/packages/polymorphine/container)
[![Packagist](https://img.shields.io/packagist/l/polymorphine/container.svg)](https://packagist.org/packages/polymorphine/container)
### PSR-11 Container for Dependencies and Configuration

#### Concept features: *Immutability & encapsulated configuration*
Until [`ContainerSetup`](src/ContainerSetup.php) is accessible new records can be added
to existing container. It's recommended to use [Config proxy](#recordsetup-as-configuration-proxy)
that allows for controlled scope for both configuration and early access to stored values.

Stateful nature of custom `Record` implementation might return different values on
subsequent calls or even have side effects - take some time to reconsider before you
decide on such feature.

### Installation with [Composer](https://getcomposer.org/)
    php composer.phar require polymorphine/container

### Container Setup

This example will show how to set up simple container. It will be continued later, but let's start with
instantiating [`ContainerSetup`](src/ContainerSetup.php) object:

    <?php
    
    use Polymorphine\Container\ContainerSetup;
    use Polymorphine\Container\Record;

    require_once __DIR__ . '/vendor/autoload.php';
    
    $setup = new ContainerSetup();
    ...

Now we need to set container values or give some clues how to resolve them.

#### Records decide how it works internally

Values returned from Container are all wrapped into [`Record`](src/Record.php) abstraction that
allows for different strategies of unwrapping them - it may be either returned directly or internally
created by calling its (lazy) initialization procedure. You may read DocBlock comments in the provided
default [records](src/Record) sourcecode to get more information. Here's short explanation of
package's Record implementations:

- `ValueRecord`: Just a value, that will be returned as it was passed (callbacks will be returned without
  evaluation as well). To push value record mapped to given `$id` into container with setup
  object use `set` command:
      
      $setup->entry(string $id)->set(mixed $value);

- `CallbackRecord`: Lazily invoked value cache. Takes callable that will be called with `ContainerInterface`
  as parameter, and value of this call will be stored and returned on subsequent calls. Setup with `invoke` command:
  
      $setup->entry(string $id)->invoke(callable $callback);
  
- `CompositeRecord`: Lazy instantiated object of given class. Constructor parameters are passed as aliases
  to other container entries. Setup - `compose` command:
  
      $setup->entry(string $id)->compose(string $class, string ...$dependencyRecords);

So our example might continue this way:

    ...
    $setup->entry('direct.object')->set(new ClassInstance());
    $setup->entry('direct.value')->set('Hello world!');

    $setup->entry('deferred')->invoke(function (ContainerInterface $c) {
        return new DeferredClassInstance($c->get('direct.value'));
    });

    $setup->entry('composed')->compose(ComposedClass::class, 'direct.object', 'deferred');
    ...

#### Container instance
Instantiating container with setup commands used in example and getting record stored under `composed` key.

    ...
    $container = $setup->container();
    $composedObject = $container->get('composed');
    
`$composedObject` will be equivalent to instantiated directly with `new` operator:

    $composedObject = new ComposedClass(new ClassInstance(), new DeferredClassInstance('Hello world!'));   

> **Decorator feature (ContainerSetup)**: `CompositeRecord` can reassign existing entry if it's also used as
one of the constructor parameters. Of course it should return the same type as overwritten record returns,
because all clients currently using it will fail. Example:
>
>     $setup->entry('html.response')->compose(HtmlResponse::class, 'auth.user', 'template.system');
>    
>     //suppose when we are in dev environment we want to add a diagnostic toolbar for all html views...
>     if ($env === 'develop') {
>         $setup->entry('html.response')->commpose(DevToolbarHtmlResponse::class, 'html.response', 'system.info');
>     }

#### Constructor instantiation
Container can't be instantiated with invalid state, because [`RecordCollection`](src/RecordCollection.php)
will throw [`Exception`](src/Exception) from constructor. It is immutable (but not necessary objects within it)
so all the data have to be passed at once.

There's also static named constructor `Container::fromRecordsArray()` that instantiates `Container` with given
array of Record instances.

    use Polymorphine\Container\Container;
    use Polymorphine\Container\Setup\Record;
    
    $records = [
        'direct.object' => new Record\DirectRecord(new ClassInstance()),
        'direct.value'  => new Record\DirectRecord('Hello world!'),
        'deferred'      => new Record\CallbackRecord(function (ContainerInterface $c) {
           return new DeferredClassInstance($c->get('direct.value'));
        }),
        'composed'      => new Record\CompositeRecord(ComposedClass::class, 'direct.object', 'deferred');
    ]
    $container = Container::fromRecordsArray($records);

#### ContainerSetup with constructor parameters
`ContainerSetup` takes the same array parameter as container's static method, but allows for adding new `Record`
instances before creating `Container`. Same result as above with different execution flow:

    $records = [
        'direct.object' => new Record\DirectRecord(new ClassInstance()),
        'direct.value'  => new Record\DirectRecord('Hello world!'),
        'deferred'      => new Record\CallbackRecord(function (ContainerInterface $c) {
            return new DeferredClassInstance($c->get('direct.value'));
        })
    ];

    $setup = new ContainerSetup($records);
    $setup->entry('composed')->compose(ComposedClass::class, 'direct.object', 'deferred');
    $container = $setup->container();

### RecordSetup as configuration proxy
Calling `ContainerSetup::entry($name)` returns `RecordSetup` helper object. Beside providing methods
to inject new instances of `Record` implementations into `RecordCollection` it also isolates `Container`
from scopes that should not be allowed to peek inside by calling `ContainerSetup::container()` method.

To make it possible `ContainerSetup` should be encapsulated in object, that allows for its configuration.
For example, if you have front controller bootstrap class similar to...

    class App
    {
        private $containerSetup;
        
        public function __construct(array $records = []) {
            $this->containerSetup = new ContainerSetup($records);
        }
        
        //...
        
        public function config(string $name): RecordSetup {
            return $this->containerSetup->entry($name);
        }
        
        public function handle(ServerRequestInterface $request): ResponseInterface {
            $container = $this->containerSetup->container();
            //...
        }
    }

...creating container from example will might go like this:

    $app = new App([
        'uriString' => new DirectRecord('www.example.com')
    ]);
    
    $app->config('Psr-uri')->set(function (string $x) {
        return new Psr\Implementation\Uri($x);
    });
    
    $app->config('https.uri')->invoke(function (ContainerInterface $c) {
        $callback = $this->get('Psr-uri');
        return $callback($this->get('uriString'))->withScheme('https');
    });
    
    //...
    
    $response = $app->handle($request);

Nothing in outer scope can use instance of `Container` created within `App`. It is possible to achieve,
but it needs to be done by explicitly passing stateful object identifier that can return container through
one of object's methods. This is not recommended though, so it won't be covered in details.

### Circular reference protection

Instantiation [`TrackingContainer`](src/TrackingContainer.php) directly or using
[`TrackingContainerSetup`](src/TrackingContainerSetup.php) will track called dependencies and throw
[`Exception`](src/Exception/CircularReferenceException.php) when record is called within the scope that
was created with that record.

This feature should be treated as **development tool** and self-constraint so that container was not overused.
It may help to locate an error in composition structure, but it comes with performance cost and also makes
a few legitimate cases harder to implement as object invoked from container may call for its instance at
runtime - for example Routing that finds endpoint & use router inside that endpoint's scope to produce urls.
To prevent circular reference detection router's endpoint should not use (tracking) container instance passed
to callback function used to instantiate router.
