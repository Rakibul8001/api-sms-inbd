<?php


namespace App\Controllers;

/**
*  this is the main controller whict all other controler will extend
*/

class Controller
{
	
	protected $container;

	function __construct($container)
	{
		
		$this->container = $container;
	}

	public function __get($property)
	{

		if ($this->container->{$property}) {
		
			return $this->container->{$property};	

		}

	}
}