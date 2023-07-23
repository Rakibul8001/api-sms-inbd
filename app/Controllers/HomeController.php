<?php 

namespace App\Controllers;

use App\Helper\Includes\BanglaConverter;

use App\Models\Users; //this is important to load the model by this

// use \Slim\Views\Twig as View; //this is also important

use App\Models\Admin\AdminResource;

class HomeController extends Controller
{

	
	
	public function indexAction($request, $response)
	{

		// if(!$this->auth->check())
		// {

		// 	return $response->withRedirect($this->router->pathFor('auth.login'));
		// }
		
		// $this->container->view->addAttribute('Title', "RetaDesk - Silicon Computer");

		// return $this->view->render($response, "modules/home/home.phtml");
		var_dump($_SESSION);
		echo "Hello API";
		die();


		
	}

	public function allaccounts($request, $response)
	{

		return include_once 'modules/banking/allaccount.php';
		//return $this->view->render($response, 'banking/allaccounts.twig');
	}

	public function testAction($request, $response, $app)
	{
		$route = $request->getAttribute('route')->getName();

		var_dump($route);

		// $routes = $app->getContainer()->get('router')->getRoutes();

		// var_dump($routes);
	// $rt[0] ='dashboard.schedules.datewise';

	// 	foreach ($rt as $resource) {
			
	// 		AdminResource::create([

	// 			'module' =>		'dashboard',
	// 			'resource' => 	$resource,
	// 			'active' => 1,
	// 		]);

	// 	}

	}

	public function accessDeniedAction($request, $response)
	{

		if(!$this->auth->check())
		{

			return $response->withRedirect($this->router->pathFor('auth.login'));
		}

		$this->container->view->addAttribute('Title', "Access Denied!");

		return $this->view->render($response, "modules/home/accessDenied.phtml");
	}

	
}