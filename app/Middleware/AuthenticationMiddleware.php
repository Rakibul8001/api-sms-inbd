<?php 

namespace App\Middleware;

use App\Auth\Auth as Auth;

class AuthenticationMiddleware extends Middleware
{
	
	public function __invoke($request, $response, $next)
	{

		
		//api request
		if (!$request->getParam('api_token')) {
			
			return $response->withJson(['errormsg' => 'Authentication Error!'],406);
			
		}

		//start of modem authentication
        $uri = $request->getUri()->getPath();
        $uriArray = explode('/', $uri);
        
        if ($uriArray[1] == 'modem-api') {

        	$auth = Auth::attempModem(
			
				$request->getParam('api_token')
			);

			if (!is_numeric($auth)) {
			
				// return $response->withJson(['errmsg' => $auth],407);
				return $response->withJson([
	                'status'        => "error",
	                'message'       => $auth,
	                'StatusCode'    => '0',
	            ], 407);

			}
			//end of modem authentication

        } else {
        	$auth = Auth::attemp(
			
				$request->getParam('api_token')
			);

			if (!is_numeric($auth)) {
				
				return $response->withJson(['errmsg' => $auth],407);

			}
        }

		$response = $next($request, $response);
		return $response;
	}
}