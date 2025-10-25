php-simple-router
=================

A simple, nearly unopinionated routing approach.

Workflow:

1.) Define some routes. Routes are some arrays identified by a unique key. The data-part is completely up to you. If you want something special in you data-part, go on and define it there.

2.) Instantiate [Router](./src/Router.php) and pass the routes-array to it. The router does nothing more than lookup a data-array who's key is matching the incoming REQUEST_URI (for example). So the return-value of $router->lookup() is the value-part of the matching key.

3.) Do something with the data-array. For example, you can merge the $_GET- or $_POST-params into some data-key, do some post-processing, whatever.

4.) Optionally utilize a Dispatcher to call some arbitrary class.

This is what the bootstrap could look like:

```php
// Setup some test-request-parameters
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Should get overwritten by the /10 above
$_GET['start'] = 20;

$_SERVER = array_merge(['REQUEST_URI' => '/', 'REQUEST_METHOD' => 'GET'], $_SERVER);

$router = new Router($routes);
$router->get('start', '/', ['entryPoint' => [IndexCtrl::class, 'show']]);
$router->post('login', '/login', ['entryPoint' => [LoginCtrl::class, 'login']]);

$request = $router::createServerRequestFromEnv();
$route = $router->lookup($request);

print_r($route); 

// Kir\Http\Routing\Common\Route Object
// (
//     [name] => start
//     [method] => GET
//     [attributes] => Array
//         (
//         )
// 
//     [params] => Array
//         (
//             [entryPoint] => Array
//                 (
//                     [0] => OpenSearchTools\Controllers\SearchCtrl
//                     [1] => show
//                 )
// 
//         )
// 
// )
```
