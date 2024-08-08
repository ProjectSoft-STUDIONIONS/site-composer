<?php
namespace ProjectSoft;

use Mimey\MimeTypes;
use Mpdf\Mpdf;
use Mpdf\HTMLParserMode;
use Mpdf\Output\Destination;

class PluginEvolution {

	// Создание дирректории по id документа c учётом родителей
	public static function createDocFolders(\DocumentParser $modx, $params)
	{
		$params = !is_array($params) ? array() : $params;
		
		$parent = 0;

		$params["pad"] = isset($params["pad"]) ? intval($params["pad"]) : 4;

		if($params["pad"]< 1)
			$params["pad"] = 4;

		$permsFolder = octdec($modx->config['new_folder_permissions']);
		$assetsPath = $modx->config['rb_base_dir'];

		$id = (isset($params['new_id'])) ? intval($params['new_id']) : intval($params["id"]);
		
		if(!$id){
			return;
		}
		
		$lists = array(str_pad($id, $params["pad"], "0", STR_PAD_LEFT));
		self::getParent($modx, $id, $lists, $params);
		
		$dir = implode('/', array_reverse($lists));
		$dirs = array("files", "images", "media");
		foreach ($dirs as $directory):
			if(!is_dir($assetsPath . $directory. "/" . $dir)):
				@mkdir($assetsPath . $directory. "/" . $dir, $permsFolder, true);
			endif;
		endforeach;
	}
	
	// Возвращение списка ID родителей
	private static function getParent(\DocumentParser $modx, $id, &$lists, $params)
	{
		$table_content = $modx->getFullTableName('site_content');
		$parent = $modx->db->getValue($modx->db->select('parent', $table_content, "id='{$id}'"));
		if($parent):
			$lists[] = str_pad($parent, $params["pad"], "0", STR_PAD_LEFT);
			self::getParent($modx, $parent, $lists, $params);
		endif;
	}
	
	// Получаем расширение файла
	private static function getFileExt($filename) {
		//получаем информацию о файле в ассоциативный массив
		$path_info = pathinfo($filename);
		//если информация есть
		if(isset($path_info)){
			//возвращаем расширение в строчном формате: txt, doc, png и т.п.
			return strtolower($path_info['extension']);
		}else{
			//иначе возвращаем пустую строку, или что-то своё
			return "";
		}
	}
	
	// Минификация HTML кода
	public static function minifyHTML(\DocumentParser $modx)
	{
		$minify = true;
		
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'):
			if(!empty($_POST["formid"])):
				$minify = false;
			endif;
		endif;
		
		if($modx->documentObject['minify'][1]==1 && $minify):
			$str = $modx->documentOutput;
			$re = '/((?:content=))(?:"|\')(.*)?(?:"|\')/U';
			$use______hash = md5(Util::has());
			$str = preg_replace_callback($re, function ($matches) use ($use______hash) {
				$res = preg_replace('(\r(?:\n)?)', $use______hash, $matches[2]);
				return $matches[1].'"'.$res.'"';
			}, $str);
			
			//$str = preg_replace("/<!(--)?(\s+)?(?!\[).*-->/", '', $str);
			$str = preg_replace("/(\s+)?\n(\s+)?/", '', $str);
			$str = preg_filter("/\s+/u", ' ', $str);
			
			$str = preg_replace("/(" . $use______hash . ")/", "\n", $str);
			$modx->documentOutput = $str;
		endif;
	}

	// Ведётся обработка на несуществующие файлы изображений и pdf
	public static function routeNotFound(\DocumentParser $modx, array $params)
	{
		//$arrReque = explode("?", $_SERVER['REQUEST_URI']);
		parse_str(htmlspecialchars_decode($_SERVER['QUERY_STRING'], ENT_HTML5), $arrQuery);
		$tmp_url = trim($arrQuery['q'], '/');
		$tmp_url = rtrim($tmp_url, $modx->config['friendly_url_suffix']);
		$url = ltrim($tmp_url, '/');
		/**
		 * Получаем расширение файла
		**/
		$ext = self::getFileExt($url);
		if($ext != "" && is_string($ext)):
			switch ($ext) {
				case 'pdf':
				case 'jpg':
				case 'jpeg':
				case 'png':
				case 'gif':
				//case 'bmp':
					$re = '@^.+((?:assets\/files|images).*$)@';
					//preg_match($re, $url, $matches);
					if(preg_match($re, $url, $matches)){
						//print_r($matches);
						$goto = $modx->config['site_url'] . $matches[1];
						$modx->sendRedirect($goto, 0, 'REDIRECT_HEADER', 'HTTP/1.1 301 Moved Permanently');
						die();
					}
					require_once(MODX_BASE_PATH.'assets/snippets/DocLister/lib/DLTemplate.class.php');
					$time = time() + (int)$modx->config['server_offset_time'];
					$date = date('d.m.Y H:i:s', $time);
					$lastModified = gmdate('D, d M Y H:i:s', $time) . ' GMT';
					$mimes = new MimeTypes;
					$modx->tpl = \DLTemplate::getInstance($modx);
					$css = is_file(dirname(__FILE__) . "/print.css") ? file_get_contents(dirname(__FILE__) . "/print.css") : "";
					$filename = pathinfo($tmp_url, PATHINFO_BASENAME);
					/**
					 * $header
					 * $footer
					 * Файлы printpage_header.html и printpage_footer.html должны лежать в директории шаблона. Путь до директории assets/templates/projectsoft/tpl/ не изменять. Он должен существовать обязательно.
					**/
					// Header
					$header = '@CODE: ' . (is_file(MODX_BASE_PATH . 'assets/templates/projectsoft/tpl/printpage_header.html') ? file_get_contents(MODX_BASE_PATH . 'assets/templates/projectsoft/tpl/printpage_header.html') : file_get_contents(dirname(__FILE__) . "/printpage_header.html"));
					// Footer
					$footer = '@CODE: ' . (is_file(MODX_BASE_PATH . 'assets/templates/projectsoft/tpl/printpage_footer.html') ? file_get_contents(MODX_BASE_PATH . 'assets/templates/projectsoft/tpl/printpage_footer.html') : file_get_contents(dirname(__FILE__) . "/printpage_footer.html"));
					// Body
					$html = "@CODE: <h1 class='text-center'>Файл<br>\"" . $url . "\"<br>по вашему запросу не найден</h1><h2 class='text-center'>Приносим свои извенения.</h2><p class='text-center'>Дата и время запроса: " . $date . "</p>";
					// Parse header
					$header = $modx->tpl->parseChunk($header, array(), true);
					// Parse footer
					$footer = $modx->tpl->parseChunk($footer, array(), true);
					// Parse body
					$html = $modx->tpl->parseChunk($html, array(), true);
					$mpdf = new Mpdf([
						'format' => [210, 180],
						'setAutoTopMargin' => 'pad',
						'setAutoBottomMargin' => 'pad'
					]);
					// Set headers Pragma: no-cache
					header('Pragma: no-cache');
					header('Cache-Control: no-store, no-cache, must-revalidate');
					header('Date: ' . $lastModified);
					// Set headers Expires
					header('Expires: ' . $lastModified);
					$type = $mimes->getMimeType($ext);
					// Set headers Content-type
					header("Content-type: " . $type);
					// Set title, creator, author, subject
					$mpdf->SetTitle("Файл \"" . $filename . "\" не найден");
					$mpdf->SetCreator($modx->config["site_name"]);
					$mpdf->SetAuthor($modx->config["site_name"]);
					$mpdf->SetSubject("Файл \"" . $filename . "\" по вашему запросу не найден");
					// write css
					$mpdf->WriteHTML($css, HTMLParserMode::HEADER_CSS);
					// set HTML header
					$mpdf->SetHTMLHeader($header);
					// set HTML footer
					$mpdf->SetHTMLFooter($footer);
					// write HTML body
					$mpdf->WriteHTML($html, HTMLParserMode::HTML_BODY);
					switch ($ext) {
						case 'pdf':
							// Set headers 302 Moved Temporarily
							//header('HTTP/1.1 302 Moved Temporarily');
							header('HTTP/1.1 503 Service Temporarily Unavailable');
							// return view pdf
							$mpdf->Output();
							die();
							break;
						default:
							// cache folder
							$dir = MODX_BASE_PATH . 'assets/cache/pdf';
							if(!is_dir($dir)):
								// create cache folder
								$permsFolder = octdec($modx->config['new_folder_permissions']);
								@mkdir($dir, $permsFolder, true);
							endif;
							// set FileName
							$filename = mktime() . "_" . $filename;
							$file = $dir . '/' . $filename . '.pdf';
							// save pdf file
							$mpdf->Output($file, 'F');
							// new Image
							$img = new \imagick($file);
							// set format
							$ext = $ext == 'jpg' ? 'jpeg' : $ext;
							$img->setImageFormat($ext);
							// write image to img file
							$img->writeImage($dir . '/' . $filename);
							// delete pdf file
							@unlink($file);
							// Set headers 404 Not Found
							header('HTTP/1.1 404 Not Found');
							// Return image
							echo file_get_contents($dir . '/' . $filename);
							// delete img file
							@unlink($dir . '/' . $filename);
							die();
							break;
					}
					break;
			}
		endif;
	}
	
	public static function notFound(\DocumentParser $modx, $params)
	{
		header("Content-type: text/plain; charset=" . $modx->config['modx_charset']);
		//exit();
		$params = !is_array($params) ? array() : $params;
		parse_str(htmlspecialchars_decode($_SERVER['QUERY_STRING'], ENT_HTML5), $arrQuery);
		$tmp_url = trim($arrQuery['q'], '/');
		$tmp_url = rtrim($tmp_url, $modx->config['friendly_url_suffix']);
		$url = ltrim($tmp_url, '/');
		$arr = explode('/', $url);
		// print_r($arr);
		if(isset($arr[0])):
			$arr[0] = intval($arr[0]);
		endif;
		if(is_int($arr[0])):
			$id = (int)$arr[0];
			$q = $modx->db->query("SELECT `id` FROM ".$modx->getFullTableName("site_content")." WHERE `id`='".$modx->db->escape($id)."'");
			$docid = (int)$modx->db->getValue($q);
			if($docid > 0):
				$arrQuery['hash'] = Util::has();
				unset($arrQuery['q']);
				$has = "?" . http_build_query($arrQuery);
				$url = $modx->makeUrl($docid);// . $has;
				//$modx->sendRedirect($url, 0, 'REDIRECT_HEADER', 'HTTP/1.1 301 Moved Permanently');
				die();
			endif;
		endif;
		exit();
	}

	// Полная Очистка директории
	public static function clearFolder(string $path = "assets/cache/css")
	{
		$dir = MODX_BASE_PATH . str_replace(MODX_BASE_PATH, "", $path);
		if(is_dir($dir) && is_writable($dir)):
			$directory = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
			$iteartion = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::CHILD_FIRST);
			foreach ( $iteartion as $file ) {
				$file->isDir() ?  @rmdir($file) : @unlink($file);
			}
			Util::setHtaccess($path);
			return true;
		endif;
		return false;
	}
	
	// Очистка пустых директорий
	public static function removeEmptyFolders(string $path = "") {
		$isFolderEmpty = true;
		$pathForGlob = (substr($path, -1) == "/") ? $path . "*" : $path . DIRECTORY_SEPARATOR . "*";
		foreach (glob($pathForGlob) as $file):
			if (is_dir($file)){
				if (!self::removeEmptyFolders($file)) {
					$isFolderEmpty = false;
				}
			}else{
				$isFolderEmpty = false;
			}
		endforeach;
		if ($isFolderEmpty){
			@rmdir($path);
		}
		return $isFolderEmpty;
	}
}
