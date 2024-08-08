<?php
namespace ProjectSoft;

class Video {

	/** Ссылка на ролик */
	private $link;
	
	/** Evolution CMS */
	private $modx;
	
	/** Видеохостинг */
	private $hosting;

	/** данные видео */
	private $videoInfo = array();

	/** Автоматическое сохранение изображения */
	private $autosave = false;
	
	/** Суфикс респонсивной вставки */
	private $sufix = '16by9';

	/** Ссылка на каталог перевьюшек */
	private $dir_images = "assets/images/video/";

	const YOUTUBE = 'youtube';
	const RUTUBE  = 'rutube';
	const VIEMO   = 'viemo';
	const DEF     = 'default';

	/**
	 * @param string|null $link ссылка на видео
	 */
	public function __construct(string $link = null, bool $autosave = false, string $sufix = '16by9', array &$videoInfo = array())
	{
		$this->modx = EvolutionCMS();
		$this->autosave = $autosave ? true : false;
		$this->sufix = is_string($sufix) ? $sufix : '16by9';
		if (!empty($link)) {
			$videoInfo = $this->cleanLink($link)->getVideoInfo();
		}
	}


	/** Проверка и подготовка ссылки и частей */
	private function cleanLink($link)
	{
		if (!preg_match('/^(http|https)\:\/\//i', $link)) {
			$this->link = 'https://' . $link;
		}else{
			$this->link = preg_replace('/^(?:https?):\/\//i', 'https://', $link, 1);
		}
		return $this;
	}

	/** Определяем хостинг и получаем информацию о видео */
	private function getVideoInfo()
	{
		$re_youtube = '/^(?:https?\:\/\/(?:[w]{3}\.)?)(youtu(?:\.be|be\.com))\//i';
		$re_rutube  = '/^(?:https?\:\/\/(?:[w]{3}\.)?)(rutube\.ru)/i';
		$re_viemo   = '/^(?:https?\:\/\/(?:[w]{3}\.)?)(vimeo\.com)/i';
		if(preg_match($re_youtube, $this->link)){
			$this->hosting = self::YOUTUBE;
			return $this->getYouTubeInfo();
		}elseif(preg_match($re_rutube, $this->link)){
			$this->hosting = self::RUTUBE;
			return $this->getRuTubeInfo();
		}elseif(preg_match($re_viemo, $this->link)){
			$this->hosting = self::VIEMO;
			return $this->getViemoInfo();
		}
		$this->hosting = self::DEF;
		return array();
	}
	
	/** Получение информации с Viemo */
	private function getViemoInfo()
	{
		$re = '/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|)(\d+)(?:|\/\?)/';
		preg_match($re, $this->link, $match);
		if(count($match)){
			$id = $match[2];
			$link = 'https://vimeo.com/api/oembed.json?url=https://vimeo.com/' . $id;
			$str = $this->fetchPage($link);
			if($str){
				$json = json_decode($str, true);
				if($json["type"]=="video"){
					$this->videoInfo['id'] = $json['video_id'];
					$this->videoInfo['link'] = 'https://viemo.com/' . $this->videoInfo['id'];
					$this->videoInfo['video'] = '<div class="embed"><div class="embed-responsive embed-responsive-' . $this->sufix . '"><iframe src="https://player.vimeo.com/video/' . $this->videoInfo['id'] . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture; clipboard-write" loading="lazy" width="100%" height="100%"></iframe></div></div>';
					/** Скачать и сохранить если сохраняется документ */
					@mkdir(MODX_BASE_PATH . $this->dir_images . $this->hosting . '/', 0755, true);
					$img_file = $this->dir_images . $this->hosting . '/' . $this->videoInfo['id'] . '.jpg';
					if($this->autosave){
						@unlink(MODX_BASE_PATH . $img_file);
						$img = $this->fetchPage($json['thumbnail_url']);
						if($img){
							@file_put_contents(MODX_BASE_PATH . $img_file, $img);
							if(is_file(MODX_BASE_PATH . $img_file)){
								$image = $this->modx->runSnippet('phpthumb', array(
									'input' => $img_file,
									'options' => 'w=680,h=360,zc=C,f=jpg'
								));
								@copy(MODX_BASE_PATH . $image, MODX_BASE_PATH . $img_file);
							}
						}
					}
					if(is_file(MODX_BASE_PATH . $img_file)){
						$this->videoInfo['image'] = $img_file;
					}else{
						$this->videoInfo['image'] = $json['thumbnail_url'];
					}
					return $this->videoInfo;
				}else{
					return array();
				}
			}else{
				return array();
			}
		}else{
			return array();
		}
		return $this->videoInfo;
	}
	
	/** Получение информации с RuTube */
	private function getRuTubeInfo()
	{
		$re = '/\/video\/([\w\-_]+)/i';
		preg_match($re, $this->link, $match);
		if(count($match)){
			$id = $match[1];
			$link = "https://rutube.ru/api/video/" . $id . "/?format=json";
			$str = $this->fetchPage($link);
			$json = json_decode($str, true);
			// <iframe width="720" height="405" src="https://rutube.ru/play/embed/748b8fd0491c7ba687e04d95ab1ea187" frameBorder="0" allow="clipboard-write" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
			if($json['track_id']){
				$this->videoInfo['id'] = $json['track_id'];
				$this->videoInfo['link'] = $this->link;
				//$json['track_id']
				$this->videoInfo['video'] = '<div class="embed"><div class="embed-responsive embed-responsive-' . $this->sufix . '"><iframe src="https://rutube.ru/play/embed/' . $this->videoInfo['id'] . '" frameborder="0" allow="clipboard-write" loading="lazy" width="100%" height="100%" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div></div>';
				/** Скачать и сохранить если сохраняется документ */
				@mkdir(MODX_BASE_PATH . $this->dir_images . $this->hosting . '/', 0755, true);
				$img_file = $this->dir_images . $this->hosting . '/' . $json['track_id'] . '.jpg';
				if($this->autosave){
					@unlink(MODX_BASE_PATH . $img_file);
					$img = $this->fetchPage($json['thumbnail_url']);
					if($img){
						@file_put_contents(MODX_BASE_PATH . $img_file, $img);
						if(is_file(MODX_BASE_PATH . $img_file)){
							$image = $this->modx->runSnippet('phpthumb', array(
								'input' => $img_file,
								'options' => 'w=680,h=360,zc=C,f=jpg'
							));
							@copy(MODX_BASE_PATH . $image, MODX_BASE_PATH . $img_file);
						}
					}
				}
				if(is_file(MODX_BASE_PATH . $img_file)){
					$this->videoInfo['image'] = $img_file;
				}else{
					$this->videoInfo['image'] = $json['thumbnail_url'];
				}
				return $this->videoInfo;
			}else{
				return array();
			}
		}else{
			return array();
		}
		return $this->videoInfo;
	}

	/** Получение информации с YouTube */
	private function getYouTubeInfo()
	{
		$re = '#(?<=(?:v|i)=)[a-z0-9-_]+(?=&)|(?<=(?:v|i)\/)[^&\n]+|(?<=embed\/)[^"&\n]+|(?<=(?:v|i)=)[^&\n]+|(?<=youtu.be\/)[^&\n]+#i';
		preg_match($re, $this->link, $match);
		if(count($match)){
			$this->videoInfo['id'] = $match[0];
			$this->videoInfo['link'] = $this->link;
			$embed = 'https://www.youtube.com/embed/' . $match[0] . '?';
			parse_str(parse_url($this->link, PHP_URL_QUERY), $params);
			if($params['list']){
				$embed .= 'list=' . $params['list'] . '&';
			}
			$embed .= 'showinfo=0&modestbranding=1&rel=0';
			$this->videoInfo['video'] = '<div class="embed"><div class="embed-responsive embed-responsive-' . $this->sufix . '"><iframe src="' . $embed . '" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture" loading="lazy" width="100%" height="100%" webkitAllowFullScreen mozallowfullscreen allowfullscreen></iframe></div></div>';

			/** Скачать и сохранить если сохраняется документ */
			$image = "https://img.youtube.com/vi/" . $match[0] . "/sddefault.jpg";
			@mkdir(MODX_BASE_PATH . $this->dir_images . $this->hosting . '/', 0755, true);
			$img_file = $this->dir_images . $this->hosting . '/' . $match[0] . '.jpg';
			if($this->autosave){
				$img = $this->fetchPage($image);
				if($img){
					@file_put_contents(MODX_BASE_PATH . $img_file, $img);
					if(is_file(MODX_BASE_PATH . $img_file)){
						$image = $this->modx->runSnippet('phpthumb', array(
							'input' => $img_file,
							'options' => 'w=680,h=360,zc=C,f=jpg'
						));
						@copy(MODX_BASE_PATH . $image, MODX_BASE_PATH . $img_file);
					}
				}
			}
			if(is_file(MODX_BASE_PATH . $img_file)){
				$this->videoInfo['image'] = $img_file;
			}else{
				$this->videoInfo['image'] = $image;
			}
			return $this->videoInfo;
		}else{
			return array();
		}
		return $this->videoInfo;
	}

	/** Скачивание с помощью CURL */
	private function fetchPage($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($httpCode >= 400){
			return false;
		}
		return $result;
	}
	
	public function setLink(string $link = null)
	{
		$videoInfo = array();
		if (!empty($link)) {
			$videoInfo = $this->cleanLink($link)->getVideoInfo();
		}
		return $videoInfo;
	}
}
