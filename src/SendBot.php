<?php
namespace ProjectSoft;

/**
 * Отправка сообщения на канал Telegram
 * 
	$modx->invokeEvent('onSendBot', array(
		'types' => {
			'date':		'Дата',
			'theme':	'Тема',
			'name':		'Имя',
			'phone':	'Телефон',
			'email':	'Электронная почта',
			'message':	'Сообщение'
			// .....
		},
		'fields' => {
			'date':		'01.01.2023',
			'theme':	'Закакз звонка',
			'name':		'Иван',
			'phone':	'+7(999)999-99-99',
			'email':	'exemple@exemple.com',
			'message':	'Сообщение для вывода на канале'
			// .....
		},
		'before_msg' => 'Вступение сообщения',
		'after_msg' => 'Конец сообщения',
		'bot_token' => 'bot<API:TOKEN>',
		'chat_id' => 'chat_id_Identification',
		'parse_mode' => 'Markdown', // Or HTML 
		'disable_web_page_preview' => 'true' // Or 'false'
	));
**/
class SendBot {

	private const API = 'https://api.telegram.org/bot';

	private $modx;
	private $types;
	private $fields;
	private $before_msg = '';
	private $after_msg = '';
	private $bot_token;
	private $chat_id;
	private $parse_mode = 'MarkdownV2';
	private $msg = '';
	private $url = '';
	private $disable_web_page_preview = '';

	public function __construct($params)
	{
		$this->modx = evolutionCMS();
		$this->parse_mode = is_string($params['parse_mode']) ? ($params['parse_mode'] == 'Markdown' || $params['parse_mode'] == 'MarkdownV2' ? 'Markdown' : 'HTML') : 'MarkdownV2';
		$this->types = is_array($params['types']) ? $params['types'] : array();
		$this->fields = is_array($params['fields']) ? $params['fields'] : array();
		$this->before_msg = is_string($params['before_msg']) ? $params['before_msg'] : "";
		$this->after_msg = is_string($params['after_msg']) ? $params['after_msg'] : "";
		$this->disable_web_page_preview = is_string($params['disable_web_page_preview']) ? ($params['disable_web_page_preview'] == 'false' ? '&disable_web_page_preview=false' : '&disable_web_page_preview=true') : '&disable_web_page_preview=true';
		$this->bot_token = is_string($params['bot_token']) ? $params['bot_token'] : $this->modx->config['bot_token'];
		$this->chat_id = is_string($params['chat_id']) ? $params['chat_id'] : $this->modx->config['chat_id'];
		$this->msg = $this->setData();
		$this->url = $this->formatUrl();
	}

	private function setData()
	{
		$msg = '';
		$sep = "\n";
		foreach($this->types as $key => $value)
		{
			$val = trim($this->fields[$key]);
			if(mb_strlen($val) > 1)
			{
				
				$msg .= '*' . $value . ':* ' . $val . "\n";
			}
		}
		$this->before_msg = trim($this->before_msg);
		if(mb_strlen($this->before_msg)>1)
		{
			$this->before_msg .= "\n\n";
		}
		$this->after_msg = trim($this->after_msg);
		if(mb_strlen($this->after_msg)>1)
		{
			$this->after_msg = "\n\n" . $this->after_msg;
		}
		return $this->before_msg . trim($msg) . $this->after_msg;
	}

	private function formatUrl()
	{
		$parse_mode = '&parse_mode=' . $this->parse_mode;
		$url = self::API . $this->bot_token . '/sendMessage?chat_id=' . $this->chat_id . '&text=' . urlencode($this->msg) . $parse_mode . $this->disable_web_page_preview;
		return $url;
	}

	public function send(){
		$url = $this->url;
		file_put_contents('0002-result.txt', print_r($url, true));
		$ch = curl_init();
		$optArray = array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true
		);
		curl_setopt_array($ch, $optArray);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
}