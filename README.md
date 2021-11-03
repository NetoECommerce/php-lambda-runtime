# PHP Lambda Runtime library

## Requirements

* PHP 7.3+
* Composer

## Installation

Run `composer require netodev/php-lambda-runtime` from the root of your project.

You'll need two files in root of your project for your application to work in Lambda, `bootstrap` and `app.php`.

* `bootstrap` can be copied directly from this library (see `bin/bootstrap`) and should not require any modification.

* `app.php` is where you instantiate the App object and define the middleware your application requires.
Since the `bootstrap` script sets up the autoloader, you *do not* need to do this in your `app.php`.

Also required is a PHP binary compiled to run in the AWS Lambda environment.
Documentation on how to build your own can be found
[here](https://aws.amazon.com/blogs/apn/aws-lambda-custom-runtime-for-php-a-practical-example/).

## Deploying to AWS

When you're ready to ship your new function you'll need to create a Zip file containing
`app.php`, `bootstrap`, `src`, `vendor` and your PHP binary in `bin/php` (or use a [Lambda layer](https://docs.aws.amazon.com/lambda/latest/dg/configuration-layers.html)).
The Zip file can then be uploaded via the AWS console or your favourite IaC tool.

## Using Middleware

This library uses [PSR-15](https://www.php-fig.org/psr/psr-15/) compliant Middleware.
If you want to learn more about how to write and use middleware, have a look at these resources:

 * https://mwop.net/blog/2018-01-23-psr-15.html
 * http://www.slimframework.com/docs/v4/concepts/middleware.html
 * https://github.com/middlewares/awesome-psr15-middlewares

While reusable middleware is great for application logic such as authentication, logging, exception handling and such,
it's generally not advisable to put business logic in your middleware.

## Examples

These are some basic examples of an `app.php` file.

### Anonymous function

In this example we use an anonymous function to output "hello world" in the response body.

    <?php
    $app = \Neto\Lambda\Application\AppFactory::create();
    $app->addCallable(function() {
        return 'hello world';
    });
    $app->run();

### Anonymous function using Response object

You can also return a Response object if you'd like to manipulate the headers, status code, etc.
This example has a header of `foo: bar` and a response body of `baz`.

    <?php
    $app = \Neto\Lambda\Application\AppFactory::create();
    $app->addCallable(function() {
        return new \GuzzleHttp\Psr7\Response(200, [ 'foo' => 'bar' ], 'baz');
    });
    $app->run();

### Adding middleware

This example uses the provided HelloWorld middleware to return a response with modified headers and body.

    <?php
    $app = \Neto\Lambda\Application\AppFactory::create();
    $app->addMiddleware(new \Neto\Lambda\Middleware\HelloWorld())
        ->run();

If invoked from the command-line, you would expect to see the following

    Status code 200
    
    Headers
    hello: world
    
    Response body
    {"success":true,"message":"Hello world!"}
    
    Duration: 0.006911039352417ms

## Testing locally

### Invoking from the command line

To run your lambda from the command line, you can simply run `vendor/bin/invoke`.
There are two optional parameters, handler name (`-h`) and request body data (`-d`).

### Running a local server

You can also use PHPs built-in web server to test and send requests to your lambda.
Simply start the server by running `vendor/bin/start_server handler.name [hostname] [port]`.
You can then send requests to your function via curl, eg: `curl --data '{"foo":"bar"}' localhost`

## License

The MIT License (MIT). Please see the [License File](https://github.com/NetoECommerce/php-lambda-runtime/blob/master/LICENSE) for more information.
