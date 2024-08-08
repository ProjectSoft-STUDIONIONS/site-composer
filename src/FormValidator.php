<?php
namespace ProjectSoft;

class FormValidator {
	
	// Validate Email
	public static function emailValid($fl, $value, $params = null)
	{
		if(empty($value)){
			return false;
		}
		$idn = new \ProjectSoft\idna_convert(array('idn_version'=>2008));
		$email = $idn->encode($value);
		file_put_contents('email_valid.txt', print_r((preg_match("/^([\w-._]+@[\w-._]+\.[\w-]{2,})$/i", $email)), true));
		return (preg_match("/^([\w._-]+@[\w._-]+\.[\w-]{2,})$/i", $email));
	}
	
	// Validate Phone
	public static function phoneValid($fl, $value, $params = null)
	{
		if(empty($value)){
			return false;
		}
		return (preg_match("/^(\+7\(\d{3}\)\d{3}-\d{2}-\d{2})$/", $value));
	}
	
	// Validate null
	public static function isNull($fl, $value, $params = null)
	{
		global $modx;
		$charset = $modx->config['modx_charset'];
		$value = trim($value."");
		return !(mb_strlen(trim($value), $charset) > 0);
	}
	
	// Validate string Length
	public static function strLength($fl, $value, $a)
	{
		global $modx;
		$charset = $modx->config['modx_charset'];
		if(empty($value)){
			return false;
		};
		return (mb_strlen(trim($value), $charset) >= $a);
	}
	
	// field police check
	public static function isPolicyCheck($fl, $value, $params = null)
	{
		$result = (strtolower($value)=="on");
		if($result){
			$fl->setField("policy_check", " checked=\"checked\"");
		}
		return $result;
	}
	
	// Validate no html tags, links
	public static function noTags($fl, $value, $params = null)
	{
		global $modx;
		$charset = $modx->config['modx_charset'];
		$value = trim(strip_tags($value));
		if(preg_match('/((?:h(?:[\s_-]+)?t(?:[\s_-]+)?t(?:[\s_-]+)?p(?:[\s_-]+)?(?:s)?|m(?:[\s_-]+)?a(?:[\s_-]+)?i(?:[\s_-]+)?l(?:[\s_-]+)?t(?:[\s_-]+)?o(?:[\s_-]+)?))(?:[\s_-]+)?:/i', $value)){
			return false;
		}
		return (mb_strlen(trim($value), $charset) >= $params);
	}
	
}
