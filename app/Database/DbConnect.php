<?php

namespace App\Database;

use App\Database\DbCon;

class DbConnect extends DbCon
{

	protected $container;

	function __construct($container)
	{
		
		$this->container = $container;
	}

	public  function connect()
	{

		$config = $this->db->getConnection();

		
		$db_conx = mysqli_connect(

			$config->getConfig('host'),
			$config->getConfig('username'),
			$config->getConfig('password')
		);

		mysqli_query($db_conx, "SET character_set_results=utf8");
		mb_language('uni'); 
		mb_internal_encoding('UTF-8');
		mysqli_select_db($db_conx, $config->getConfig('database'))or die(mysqli_error());
		mysqli_query($db_conx, "set names 'utf8'")or die(mysqli_error());
		mysqli_query($db_conx, "SET character_set_client=utf8")or die(mysqli_error());
		mysqli_query($db_conx, "SET character_set_connection=utf8")or die(mysqli_error());



		return $db_conx;
	}


}