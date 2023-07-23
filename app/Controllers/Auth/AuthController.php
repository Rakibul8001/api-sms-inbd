<?php 

namespace App\Controllers\Auth;


use App\Models\Users;
use App\Models\AdminLogin;

use App\Controllers\Controller;

use Respect\Validation\Validator as v;


class AuthController extends Controller
{

	
	
	public function getLogin($request, $response)
	{	
		if($this->auth->check())
		{
			
			return $response->withRedirect($this->router->pathFor('home'));
		}


		return $this->view->render($response, "modules/auth/login.phtml");
	}

	public function postLogin($request, $response)
	{

		$validation = $this->validator->validate($request, [

			'username' => v::noWhitespace()->notEmpty(),
			'password' => v::noWhitespace()->notEmpty(),

		]);

		if ($validation->failed()) {
			
			return $response->withRedirect($this->router->pathFor('auth.login'));
			
		}

		$auth = $this->auth->attemp(

			$request->getParam('username'),

			$request->getParam('password')

		);

		if (!$auth) {
			
			return $response->withRedirect($this->router->pathFor('auth.login'));

		}

		//update user data to database
		$db_conx = $this->DbConnect->connect();
		
		$userid = $_SESSION['user'];

		$sql = "UPDATE admin_user set logdate=now(), lognum= lognum + 1 where id=$userid ";
		$query=mysqli_query($db_conx, $sql) or die("ERROR");

		return $response->withRedirect($this->router->pathFor('home'));

	}

	public function getLogout($request, $response)
	{

		unset($_SESSION['user']);
		unset($_SESSION['admin_role']);

		$user = AdminLogin::find($_SESSION['last_login']);

		$thisTime = (new \DateTime())->format('Y-m-d H:i:s');

		$user->logout_time = $thisTime;

		$user->save();


		unset($_SESSION['last_login']);

		

		return $response->withRedirect($this->router->pathFor('auth.login'));

	}


	//--------------checkpass-------------------

	public function checkpassAction($request, $response)
	{
		$user = Users::find($_SESSION['user']);

		if (password_verify($request->getParam('pass'), $user->password)) {
			
			return 'valid';
		
		}

		return 'invalid';
	}


}