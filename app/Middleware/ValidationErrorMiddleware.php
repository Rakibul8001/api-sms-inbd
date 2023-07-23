<?php 

namespace App\Middleware;


class ValidationErrorMiddleware extends Middleware
{
	
	public function __invoke($request, $response, $next)
	{

		if (!isset($_SESSION['errors'])) {
			
			$_SESSION['errors'] = null;

		}

		$this->container->view->addAttribute('errors', $_SESSION['errors']);

		unset($_SESSION['errors']);
		

		
		$response = $next($request, $response);
		return $response;
	}
}