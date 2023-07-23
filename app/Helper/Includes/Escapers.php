<?php

namespace App\Helper\Includes;

use Zend\Escaper\Escaper;

class Escapers
{
    public static function escapeHtml($input)
    {
    	$escaper = new Escaper('utf-8');

    	return $escaper->escapeHtml($input);
    }


    public static function escapeHtmlAttr($input)
    {
    	$escaper = new Escaper('utf-8');
		return $escaper->escapeHtmlAttr($input);
    }

    public static function escapeJs($input)
    {
    	$escaper = new Escaper('utf-8');
		return $escaper->escapeJs($input);
    }

    public static function escapeCss($input)
    {
    	$escaper = new Escaper('utf-8');
		return $escaper->escapeCss($input);
    }

    public static function escapeUrl($input)
    {
    	$escaper = new Escaper('utf-8');
		return $escaper->escapeUrl($input);
    }


}

