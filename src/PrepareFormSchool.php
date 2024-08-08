<?php

namespace ProjectSoft;

class PrepareFormSchool {

	public static function prepareProcess($modx, $data, $fl, $name)
	{
		$cfg = $fl->config->getConfig();
		$site = $modx->config['site_name'];
		$fl->mailConfig['subject']  = $cfg["subject"] = "Робот сайта «" . $site . "»";
		$fl->mailConfig['replyTo']  = $cfg["replyTo"] = $modx->config['email_bot'];
		$fl->mailConfig['fromName']  = $cfg["fromName"] = $modx->config['email_bot_name'];
		$fl->config->setConfig($cfg);
	}

	public static function prepare($modx, $data, $fl, $name)
	{
		$https_port = 443;
		$id = $modx->documentIdentifier;
		$url = $modx->makeUrl($id, '', '');
		$charset = $modx->config['modx_charset'];
		$secured = (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https');
		$port = ((isset ($_SERVER['HTTPS']) && ( (strtolower($_SERVER['HTTPS']) == 'on') || ($_SERVER['HTTPS']) == '1')) || $_SERVER['SERVER_PORT'] == $https_port || $secured) ? 'https://' : 'http://';
		$input = $_SERVER['HTTP_HOST'];
		$idna = new idna_convert();
		$host = $port . $idna->decode($input) . $url;
		$message = $fl->getField('message');
		$message = $message ? htmlspecialchars(trim(strip_tags($message)), ENT_COMPAT, $charset, true) : '';
		$fl->setField('message', $message);
		$fl->setField("pagetitle", $modx->documentObject["pagetitle"]);
		$fl->setField("url", $host);
	}

	public static function prepareAfterProcess($modx, $data, $fl, $name)
	{
		$theme = $fl->getField("formid");
		$theme_val = "Вопрос с сайта " . $modx->config['site_name'];
		$message = $fl->getField('message');
		$message = $message ? $message : '';
		$re = '/^(.*\:|(?:.*))(.*)/m';
		$subst = '*$1* $2';
		$re = '/([~>#+=|{}.!-])/i';
		$subst = "\\\\.";
		//$message = preg_replace($re, $subst, $message);

		$page = '' . $modx->documentObject["pagetitle"] . " _" . $fl->getField('url') . "_";
		//$page = preg_replace($re, $subst, $page);

		$first_name = $fl->getField('first_name');
		//$first_name = preg_replace($re, $subst, $first_name);

		$email = $fl->getField('email');
		//$email = preg_replace($re, $subst, $email);

		$phone = $fl->getField('phone');
		//$phone = preg_replace($re, $subst, $phone);

		$date = date('d.m.Y H:i:s', time() + $modx->config['server_offset_time']);
		//$date = preg_replace($re, $subst, $date);

		$arr = array(
			"types" => array(
				'date'		=> 'Дата',
				'theme'		=> 'Тема',
				'name'		=> 'Имя',
				'email'		=> 'Email',
				'phone'		=> 'Телефон',
				'message'	=> 'Сообщение',
				'url'		=> 'Страница отправки'
			),
			"fields" => array(
				'date'		=> date('d.m.Y H:i:s', time() + $modx->config['server_offset_time']),
				'theme'		=> $theme_val,
				'name'		=> $first_name,
				'email'		=> $email,
				'phone'		=> $phone,
				'message'	=> $message,
				'url'		=> $page
			),
			"parse_mode"	=> "Markdown",
			"bot_token"		=> $modx->config["bot_token"],
			"chat_id"		=> $modx->config["tlg_chanel"]
		);
		//file_put_contents('formsend.txt', print_r($arr, true));
		//$modx->invokeEvent('onSendBot', $arr);
		$bot = new \ProjectSoft\SendBot($arr);
		$result = $bot->send();
		file_put_contents('0001-result.txt', print_r($result, true));
		$json = json_decode($result);
		if(is_object($json)):
			if(!$json->ok):
				$fl->setFormStatus(false);
				$fl->addMessage($json->description);
				//$fl->setFormStatus(false);
			endif;
		else:
			$fl->setFormStatus(false);
			$fl->addMessage($result);
			//$fl->setFormStatus(false);
		endif;
	}
}