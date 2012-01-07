<?php 

class Browser {
	public static function IE() {
		$matches = array();
		preg_match('/MSIE ([^;]+);/i', $_SERVER['HTTP_USER_AGENT'], $matches );
		return floor($matches[1]);
	}
	
	public static function Opera() 		{return (stripos($_SERVER['HTTP_USER_AGENT'], 'Opera'	)!==FALSE);}
	public static function Webkit() 	{return (stripos($_SERVER['HTTP_USER_AGENT'], 'WebKit'	)!==FALSE);}
	public static function Firefox() 	{return (stripos($_SERVER['HTTP_USER_AGENT'], 'Firefox'	)!==FALSE);}
	public static function iPhone() 	{return (stripos($_SERVER['HTTP_USER_AGENT'], 'iPhone'	)!==FALSE);}
	public static function iPad() 		{return (stripos($_SERVER['HTTP_USER_AGENT'], 'iPad'	)!==FALSE);}
	public static function iPodTouch() 	{return (stripos($_SERVER['HTTP_USER_AGENT'], 'iPod'	)!==FALSE);}
}

