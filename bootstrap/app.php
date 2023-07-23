<?php 

use Dotenv\Dotenv;
session_start();

require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$baseUrl = 'http://'.$_SERVER['SERVER_NAME'].'/'; //------------ set the base url here ------------
// dd($baseUrl);
$apiToken = "2021RrrDTHstDkRnVFkPlPrurhT611DthstInternalAPI";  //------ Set Internal Requests API Token Here -----



$app = new \Slim\App([

	'settings' => [
        // Slim Settings
        'displayErrorDetails' => true,
        'determineRouteBeforeAppMiddleware' => true,

        'db' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'],
            'database' => $_ENV['DB_NAME'],
            'username' => $_ENV['DB_USER'],
            'password' => $_ENV['DB_PASSWORD'],
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
    	],
        
    ],
]);

    
$container = $app->getContainer();

//database
$capsule = new \Illuminate\Database\Capsule\Manager;

$capsule->addConnection($container['settings']['db']);

$capsule->setAsGlobal();

$capsule->bootEloquent();

$container['db'] = function($container) use ($capsule){

	return $capsule;

};

//database end




$container['auth'] = function($container){
    
    return new \App\Auth\Auth;
};
$container['baseUrl'] = $baseUrl;
$container['apiToken'] = $apiToken;

//-------------- setting up php view ----------------
// Register component on container

$container['view'] = new \Slim\Views\PhpRenderer("../templates/",[
        'baseUrl' => $baseUrl,
        'auth' => [

                'check' => $container->auth->check($api_token = null),
                'user'  => $container->auth->user($api_token = null)


                    ]
    ]);

//---------------- end php view ---------------------

//---------------- validator setup ------------------

$container['validator'] = function($container){

    return new App\Validation\Validator;

};

//---------------- end validator ---------------------





//-------------setting up controllers--------

//database SQL
$container['DbConnect'] = function($container){
    
    return new \App\Database\DbConnect($container);
};

$container['AuthController'] = function($container){
    
    return new \App\Controllers\Auth\AuthController($container);
};

$container['HomeController'] = function($container){
    
    return new \App\Controllers\HomeController($container);
};
$container['DeliveryReportController'] = function($container){
    
    return new \App\Controllers\DeliveryReportController($container);
};

$container['SmsApiController'] = function($container){
    
    return new \App\Controllers\SmsApiController($container);
};
$container['ApiIntegrationController'] = function($container){
    
    return new \App\Controllers\ApiIntegrationController($container);
};

$container['ModemController'] = function($container){
    
    return new \App\Controllers\ModemController($container);
};
//-------------setting up controllers END--------



//-------------Adding Middleware --------

$app->add(new \App\Middleware\ValidationErrorMiddleware($container));
$app->add(new \App\Middleware\OldInputMiddleware($container));

// $app->add(new \App\Middleware\CsrfViewMiddleware($container));

//authentication middleware
$app->add(new \App\Middleware\AuthenticationMiddleware($container));


require __DIR__ . '/../app/routes.php';


