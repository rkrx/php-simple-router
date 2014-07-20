php-simple-router
=================

A simple, unopinionated routing approach.

Workflow:

1.) Define some routes. Routes are some arrays identified by a unique key. The data-part is completely up to you. If you want something special in you data-part, go on and define it there.

2.) Call the [Router](./src/Router.php) and pass the routes-array to it. The router does nothing more than lookup a data-array who's key is matching the incoming REQUEST_URI (for example). So the return-value of $router->resolve() is the value-part of the matching key.

3.) Do something with the data-array. For example, you can merge the $_GET- or $_POST-params into some data-key, do some post-processing, whatever.

4.) Optionally utilize the [Dispatcher](./src/Dispatcher.php) to call some arbitary class. The dispatcher does not require the class to implement a specific class nor does it require special parameters for the constructor or the targeted method.

This is what the bootstrap could look like:
```PHP
// Setup some test-request-parameters
$_SERVER['REQUEST_URI'] = '/some/path/10';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Should get overwritten by the /10 above
$_GET['start'] = 20;

/*
 * <?php return [
 *     'GET /some/path' => ['class' => 'Test\\Main', 'method' => 'test'],
 *     'GET /some/path/:start' => ['class' => 'Test\\Main', 'method' => 'test'],
 * ];
 */
$router = new Router(require '../config/routes.php');
$data = $router->lookup($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

// Use $_GET and override it with the contents of $data['params']
$data['params'] = array_merge($_GET, $data['params']);

// Call a controller specified in the matching route
$dispatcher = new Dispatcher($sl);
$data = $dispatcher->invoke($data['class'], $data['method'], $data['params']);

// The output of the controller's method
echo $data;
```

This is what the controller could look like:
```PHP
namespace Test;
class Main {
	public function test($start = 0) {
		return $start;
	}
}
```
