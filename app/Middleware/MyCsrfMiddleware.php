<?php

namespace App\Middleware;

use Slim\Csrf\Guard;
use App\Models\AccessTrackerRD;

class MyCsrfMiddleware extends Guard
{
    // This method is processing every request in our application
    public function processRequest($request, $response, $next) {
        $route = $request->getAttribute('route');

        $uri = $request->getUri()->getPath();
        $uriArray = explode('/', $uri);

        //test---- exclude

        $uri = $request->getUri();
            $usr = null;

            if (isset($_SESSION['user'])) {
                $usr = 14;
            }
            // $ip = $request->getAttribute('ip_address');
            // if ($uri->getPath()!='/public/notifications/check') {
            //     AccessTrackerRD::create([
            //             'user'       => $usr,
            //             'ip'         => $ip,
            //             'uri'        => $uri->getPath(),
            //             'method'     => $_SERVER['REQUEST_METHOD'],

            //     ]);
            // }
        
        //exclude--
        if ($uriArray[1] == 'sms-api') {

            // If it is - just pass request-response to the next callable in chain
            return $next($request, $response);

        } else if ($uriArray[1] == 'public') {

            // If it is - just pass request-response to the next callable in chain
            return $next($request, $response);

        } else {

            // else apply __invoke method that you've inherited from \Slim\Csrf\Guard
            return $this($request, $response, $next);
        
        }
    }
}