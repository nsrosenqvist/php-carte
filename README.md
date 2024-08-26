# Carte

*A declarative PSR-15 router*

This package is a PHP router library based on the [PSR-7](https://www.php-fig.org/psr/psr-7/), [PSR-15](https://www.php-fig.org/psr/psr-15/) and [PSR-17](https://www.php-fig.org/psr/psr-17/) standards. The idea is that for a smaller application you rarely need the flexibility of a programmable router class, but can often make due with mapping routes directly to certain resources.

Even though the main idea behind the router is to embrace its core concept's simplicity, it does allow for quite advanced configuration and custom usage that makes it an appropriate tool for a wide variety of use cases, especially flat-file content frameworks.

## Installation

The library requires PHP 8.2+ and can be installed using composer:

```sh
composer require nsrosenqvist/carte
```

Only PHP and Json manifest files are supported out of the box, but you can easily enable JsonC or Yaml support by also requiring either `adhocore/json-comment` or  `symfony/yaml` as an additional dependency.

### Compatibility

Currently we support PHP 8.2 and above, but we make no commitment to support certain PHP versions for future releases.

## Usage

One defines the routes of one's application using a structured data format such as Yaml or Json (We ship with built-in support for Yaml, Json, JsonC, and PHP array-files). Wildcard pattern matching, groups, middlewares and custom responses, can all be configured easily through its format.

```yaml
index:
  body: 'Welcome!'
blog/*:
  body: 'php://handler.php'
about:
  routes:
    me:
      body: 'md://who-am-i.md'
    writings:
      body: 'md://writings.md'
contact:
  - body: 'php://mailer.php'
    match:
      method: POST
  - body: 'file://contact.html'
    match:
      method: GET
subscribers:
    strategy: auth
    body: 'Thank you!'
```

> [!NOTE]  
> Further examples will be written as JsonC due to its greater data structure readability

Since this package is only a router and not a framework, one must implement the server request handling around it. The sample below is an example implementation, using Guzzle's PSR-17 factory implementation (`guzzlehttp/psr7`).

```php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Carte\Content\FileResolver;
use Carte\Content\PhpResolver;
use Carte\Content\RedirectResolver;
use Carte\Content\StreamResolver;
use Carte\Manifest;
use Carte\Router;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;

// Handle the request
$factory = new HttpFactory();
$manifest = new Manifest(__DIR__ . '/site.yml', __DIR__ . '/cache.php');
$router = new Router(
    responseFactory: $factory,
    streamFactory: $factory,
    manifest: $manifest,
    resolvers: [
        new StreamResolver(__DIR__ . '/content', $factory),
        new PhpResolver(__DIR__ . '/content'),
        new FileResolver(__DIR__ . '/content'),
        new RedirectResolver(),
    ],
);

$request = ServerRequest::fromGlobals();
$response = $router->dispatch($request);

// Emit the status code
http_response_code($response->getStatusCode());

// Emit the headers
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}

// Emit the body
echo $response->getBody();
```

### Configuring the router

When instancing the router, one can set all of its configuration options through the constructor. Alternatively one can also set them programmatically using their corresponding setters. The only non-optional parameters are the following:

```php
/**
 * @param ResponseFactoryInterface   $responseFactory PSR-17 response factory
 * @param StreamFactoryInterface     $streamFactory   PSR-17 stream factory
 * @param Manifest                   $manifest        Route manifest
 */
```

#### Content resolvers

Content resolvers are ways to define how a response is constructed from a matched route. A later section will describe these in detail.

#### Default strategy

A strategy is a collection of PSR-15 middleware. These can be defined on a per-route basis or on a group level, but if there are middleware one wants to run on all requests, the strategy can be defined globally here.

#### Default responses

In case no route is matched, one can choose to provide a default PSR-7 response. This can also be easily defined using the router manifest as a wildcard route.

#### Graceful failures

By default, the router will not throw any exceptions but instead translate these into appropriate HTTP responses. If one wants to handle these manually instead, one can set the router to not handle these and have the exceptions bubble up instead.

#### Root directory

If the application is not served at the application root, one can configure the root URI on the router, so that requests to `https://foo.bar/root/about` can be properly mapped to the `about` route defined in the manifest. If one is also using the redirect content resolver one must make sure to configure the root for it too.

### The manifest format

The router manifest defines what responses should be given to different requests. It's a quite flexible syntax that is designed to help you write as little configuration as possible. The basic syntax is as follows.

```jsonc
{
    // Request URI
    "foo/bar": {
        "code": 200,                     // Return code
        "body": "Lorem Ipsum",           // Response body
        "headers": {                     // Response headers
            "Content-Type": "text/plain" // This isn't required, we will attempt to set the mime type automatically
        },
        "reason": "OK",                  // Reason phrase
        "version": "1.1",                // Protocol version

        // Any extra parameters will be mapped to the matched route's "extra" property,
        // These can also be set more explicitly under a property named "extra"
        "foo": "bar"
    }
}
```

The property `body` can either be a simple string, or of any format that a registered content resolver can handle. The default content resolvers are all designed to use URIs to properly delegate content resolution, ie. `redirect://somewhere`.

A route definition can basically be empty, since the response will then be populated by defaults.

#### Grouped routes

One can group routes under a route-prefix, allowing the chosen strategy or extra properties to be propagated to all child routes. Nesting grouped routes is supported as well. A group is defined by the entry having a "routes" property containing child definitions.

```jsonc
{
    // Route group
    "about": {
        // Custom properties will be set on all children as well
        "extra": {
            "theme": "fun"
        },
        // Child routes
        "routes": {
            "history": {
                "body": "My background..."
            },
            "plans": {
                "body": "My future plans!"
            },
            "dogs": {
                "body": "My pets!"
            }
        }
    }
}
```

#### Variable matching

One can create multiple response definitions for the same route by wrapping them in an array and using a match statement. In the example below, a request to `foo/bar/lorem` would yield a 200 response, while a request to `foo/bar/ipsum` would yield a 202.

```jsonc
{
    // Named variable in route definition
    "foo/bar/{var}": [
        {
            "code": 200,
            "match": {
                "var": "lorem"
            }
        },
        {
            "code": 202,
            "match": {
                "var": "ipsum"
            }
        }
    ]
}
```

The variable names "query" and "method" are reserved, since they are used for defining conditions for method and query matching.

#### Wildcard patterns

You can even mix and match named variables with glob wildcards:

```jsonc
{
    // Named variable in route definition
    "foo/bar/{var}": [
        // ...
    ],
    "foo/bar/*": {
        "code": 404
    }
}
```

The routes will be matched according to specificity, so named variables will be prioritized before wildcards. Under the hood the wildcard matching uses `fnmatch` and therefore one could use more advanced patterns, but they are not officially supported.

#### Method matching

The method key in the match definition allows one to specify different responses for different methods.

```jsonc
{
    "foo/bar": [
        {
            "code": 200,
            "match": {
                "method": "GET"
            }
        },
        {
            "code": 202,
            "match": {
                "method": "POST"
            }
        }
    ]
}
```

#### Query conditions

In addition to variable matching, one can also test the query parameters (these will also be prioritized in order of specificity).

```jsonc
{
    "foo/bar": [
        {
            "code": 200,
            "match": {
                "query": {
                    "type": "foo"
                }
            }
        },
        {
            "code": 202,
            "match": {
                "query": {
                    "type": "bar"
                }
            }
        }
    ]
}
```

##### Advanced query matching

In addition to direct comparisons, one can instead set any of these special comparison operators:

- `__isset__`: Tests whether the parameter exist.
- `__missing__`: Tests whether the parameter does not exist.
- `__true__`: Tests whether the parameter is truthy (this includes, "yes", "y", 1, etc.).
- `__false__`: Tests whether the parameter is falsey (this includes, "no", "n", 0, etc.).
- `__bool__`: Tests whether the parameter is booly.
- `__string__`: Tests whether the parameter is a string.
- `__numeric__`: Tests whether the parameter is a numeric.
- `__int__`: Tests whether the parameter is an int.
- `__float__`: Tests whether the parameter is a float.
- `__array__`: Tests whether the parameter is an array.

#### Alternative syntaxes

In order to minimize required configuration, some alternative syntaxes are also supported. These will be normalized upon import.

##### Short syntax

```jsonc
{
    "alternative/syntax/short-code": 100,                // Will return a response with status 100
    "alternative/syntax/short-content": "Response body", // Will by default return a 200 response with the body "Response body"
}
```

###### REST syntax

The REST syntax allows one to define responses according to HTTP method (the method will be expanded into `match->method`, like a regular method match). This syntax also supports short syntax definitions.

```jsonc
{
    "alternative/syntax/rest": {
        "GET": "Response body",
        "POST": 204,
        "*": { // Catch-all definition is also supported
            "body": "How did you get here?",
            "code": 404 
        }
    }
}
```

#### Middleware strategy

The library also supports PSR-15 middlewares which are configured using strategy implementations. A "strategy" is a named set of middlewares that can be reused and its class need to implement `\Carte\Strategies\StrategyInterface`.

```php
declare(strict_types=1);

use Carte\Strategies\Strategy;
use MyFirstMiddleware;
use MySecondMiddleware;

class MyStrategy extends Strategy
{
    public function __construct() {
        parent::__construct([
            new MyFirstMiddleware(),
            new MySecondMiddleware(),
        ]);
    }
}
```

To define a strategy for a route, you either set it per-route or on a group level.

```jsonc
{
    // Route group
    "about": {
        "strategy": "custom",
        "routes": {
            // Strategy will be propagated to all children
            // ...
        }
    },
    // Single route
    "contact": {
        "strategy": "custom"
    }
}
```

### Content resolution

A content resolver returns the content of the response by processing the incoming request object and the matched route. This is where you'd make use of the "extra" property. There are several built-in ones that you can make use of that are used depending on how one specifies the "body" property in the route definition.

#### File resolver

The file resolver will be selected whenever you specify a path with the "file" URI scheme: `file://myfile.txt`. The resolver will try and find that file underneath the resource directory that the class instance is configured with, and automatically determine the response's content-type.

```php
$resolver = new \Carte\Content\FileResolver(__DIR__ . '/content');
```

#### PHP resolver

The PHP resolver will be selected whenever you specify a path with the "php" URI scheme: `php://handler.php`. The resolver will load that PHP file into the executing context of the content resolver. This is the most flexible default resolver since you yourself handle the executing logic. The PHP file that is executed will have the following variables defined in its environment that one make use of to process the request.

```php
/**
 * @var \Carte\Routes\RouteCase                  $route
 * @var \Psr\Http\Message\ServerRequestInterface $request
 * @var int                                      $httpCode
 * @var array<string, string>                    $httpHeaders
 */
```

Both `$httpCode` and `$httpHeaders` are passed by reference and can be used to alter the returned response. Whatever is outputted from the execution is what will populate the response's body.

```php
$resolver = new \Carte\Content\PhpResolver(__DIR__ . '/handlers');
```

#### Redirect resolver

The redirect resolver will be selected whenever you specify a path with the "redirect" URI scheme (`redirect://about`) or specify an external address and set the response code to a 30X.

```jsonc
{
    "code": 302,
    "body": "https://foo.bar/"
}
```

When using the redirect URI scheme, the resolver will process it as an internal (same-site) redirect, and redirect the user to another route by constructing the address with the host information coming from the current request. If the router is handling requests on a path underneath the web site root, this root must be configured when instancing the resolver.

```php
$resolver = new \Carte\Content\RedirectResolver('under/root');
```

#### Stream resolver

The stream resolver will be selected whenever you specify a path with the "stream" URI scheme: `stream://image.jpg`. It will create a PSR-7 stream response instead of a normal message. When instancing it, one must provide a PSR-17 stream factory implementation.

```php
$factory = new \GuzzleHttp\Psr7\HttpFactory();
$resolver = new \Carte\Content\StreamResolver(__DIR__ . '/content', $factory);
```

#### Custom resolver

It's easy to create your own if you'd like. For example, if you're building a site serving markdown, and you don't need to differentiate between different resolvers using a URI scheme, you could easily build a resolver that handles all of your matched routes and parses the specified files using [CommonMark](https://commonmark.thephpleague.com/2.5/).

```php
declare(strict_types=1);

namespace Carte\Content;

use Carte\Content\ContentResolverInterface;
use Carte\Exceptions\FileNotFoundException;
use Carte\Routes\RouteCase;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class FileResolver implements ContentResolverInterface
{
    protected $markdown;

    /**
     * @throws FileNotFoundException
     */
    public function __construct(
        protected string $resourceDirectory,
    ) {
        $this->markdown = new GithubFlavoredMarkdownConverter();
        $this->resourceDirectory = realpath($this->resourceDirectory) ?: '';

        if (! $this->resourceDirectory || ! is_dir($this->resourceDirectory)) {
            throw new FileNotFoundException("Resource directory not found: $resourceDirectory");
        }
    }

    public function resolve(RouteCase $route, ServerRequestInterface $request, int &$httpCode = 200, array &$httpHeaders = []): StreamInterface|string
    {
        $file = $route->body ?: '';
        $path = "{$this->resourceDirectory}/$file";

        if (! str_starts_with(realpath($path) ?: '', $this->resourceDirectory)) {
            throw new FileNotFoundException("File not found: $file");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new FileNotFoundException("File could not be read: $file");
        }

        return $this->markdown->convert($contents);
    }

    public function supports(RouteCase $route): bool
    {
        return true;
    }
}
```

### Manifest caching

For production use cases, the compiled manifest file should always be cached. When a manifest is loaded, it goes through several steps in order to expand alternative syntaxes, groups and normalizing the data format so that is faster to process for the router. Specify a cache path when instancing the manifest and the cache will be created automatically.

> [!NOTE]  
> When instancing a manifest with a cache path defined, that cache must be manually removed when the manifest file is updated, since no automatic cache invalidation is provided. A simple way to implement cache invalidation is detailed below.

```php
$path = __DIR__ . '/site.yml';
$cache = __DIR__ . '/cache.php';

if (filemtime($cache) < filemtime($path)) {
    unlink($cache);
}

$manifest = new \Carte\Manifest($path, $cache);
```

## Development

In order to set up your development environment, first make sure that you have docker installed, clone the repo, and then open start the development container by running:

```sh
./app up --detach
```

`./app` is a simple wrapper around Docker Compose, which makes it simpler to interface with the app container. The project source directory will be mapped to the working directory of the container. To enter into a development shell you run:

```sh
./app /bin/sh
```

From there you can run `composer install` and other defined commands. 

> [!NOTE]  
> Developing against a container allows us to easily verify that the library works as expected for the targeted PHP version.

When executing `composer install`, certain hooks should automatically be configured that make sure code standards are upheld before any changes can be pushed. Before submitting a PR, make sure that your linting, static analysis, and unit tests all pass. See [composer.json](https://raw.githubusercontent.com/nsrosenqvist/php-carte/main/composer.json) for configured commands (e.g. `test`, `lint`, `analyze`).

## License

This library is licensed under [MIT](https://raw.githubusercontent.com/nsrosenqvist/php-carte/main/LICENSE.md), except for [`src/Http/Method.php`](https://raw.githubusercontent.com/nsrosenqvist/php-carte/main/src/Http/Method.php) which is partly licensed under the [Boost Software License - Version 1.0](https://raw.githubusercontent.com/nsrosenqvist/php-carte/main/licenses/alexanderpas_http-enum.txt) due to its origin as part of the package `alexanderpas/http-enum`. All the file's additions are however licensed under MIT.
