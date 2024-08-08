<?php
class VkApiWallPost {
	/*
	public $owner_id = -987654321;
	public $group_id = 987654321;
	public $idApp = 1234567;
	*/
	public $versionVk = "5.131";
	
	public function __construct(protected string $token, protected int $idApp, protected int $group_id, protected int $owner_id ) {
		/**
		 * Code for creating a slate...
		**/
	}
	/* ДЛЯ ОТПРАВКИ POST ЗАПРОСОВ */
	/*
		method - название метода (информация в документации Вконтакте)
		dataQuery - массив с параметрами запроса
	*/
	public function sendQueryPost($method, $dataQuery = array()) {
		/* добавляем токен в массив запроса */
		$dataQuery["access_token"] = $this->token;
		/* добавляем версию API вконтакте */
		$dataQuery["v"] = $this->versionVk;

		$ch = curl_init("https://api.vk.com/method/{$method}");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $dataQuery);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$html = curl_exec($ch);
		curl_close($ch);

		if($html === false){
			//echo "Ошибка отправки запроса: " . curl_error($curl);
			return false;
		}
		else{
			return json_decode($html, true);
		}
	}
	/* -------------------- */


	/* ДЛЯ ОТПРАВКИ GET ЗАПРОСОВ */
	public function sendQueryVk_GET($method, $dataQuery = array()) {
		$dataQuery["access_token"] = $this->token;
		$dataQuery["v"] = $this->versionVk;
		$ch = curl_init("https://api.vk.com/method/{$method}?" . http_build_query($dataQuery));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$html = curl_exec($ch);
		curl_close($ch);
		return $html;
	}
	/* -------------------- */


	/* ДЛЯ ПОСТИНГА ЗАПИСИ */
	public function send_wall_post($dataQuery) {
		$dataQuery["from_group"] = "1";
		$dataQuery["owner_id"] = $this->owner_id;

		return $this->sendQueryPost("wall.post", $dataQuery);
	}
	/* -------------------- */


	/* ДЛЯ ПОЛУЧЕНИЯ URL ДЛЯ ЗАГРУЗКИ */
	public function send_photos_getWallUploadServer($dataQuery) {
		$dataQuery["from_group"] = "1";
		$dataQuery["owner_id"] = $this->owner_id;

		return $this->sendQueryPost("photos.getWallUploadServer", $dataQuery);
	}
	/* -------------------- */


	/* ДЛЯ СОХРАНЕНИЯ ИЗОБРАЖЕНИЯ НА СЕРВЕРЕ VK */
	public function send_photos_saveWallPhoto($dataQuery) {
		return $this->sendQueryVk_GET("photos.saveWallPhoto", $dataQuery);
	}
	/* -------------------- */


	/* ПОЛУЧЕНИЕ КОДА ДЛЯ ПОСТИНГА ИЗОБРАЖЕНИЙ */
	/*
		urlFile - ссылка на файл изображения на хостинге
	*/
	public function sendPhotoInVk($urlFile) {
		/* отправка запроса для получения ссылки на загрузку файла */
		$arrQuery = [
			"group_id" => $this->group_id,
		];
		$dataUploadParams = $this->send_photos_getWallUploadServer($arrQuery);
		$uploadUrl = $dataUploadParams["response"]["upload_url"];
		/* -------------------- */

		/* отправка изображения на сервер */
		$curl_photo = curl_file_create($urlFile);
		$arrQuery = [
			"photo" => $curl_photo
		];
		$ch = curl_init($uploadUrl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $arrQuery);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$dataUploadParams = curl_exec($ch);
		curl_close($ch);
		$dataImage = json_decode($dataUploadParams, true);
		/* -------------------- */

		/* получение информации о картинге с сервера */
		$dataImage["group_id"] = $this->group_id;
		$dataSaveParams = $this->send_photos_saveWallPhoto($dataImage);
		$dataImage = json_decode($dataSaveParams, true);

		$codeQueryImage = "photo{$dataImage['response'][0]['owner_id']}_{$dataImage['response'][0]['id']}";
		return $codeQueryImage;
	}
}