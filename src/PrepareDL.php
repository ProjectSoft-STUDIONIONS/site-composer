<?php
namespace ProjectSoft;

class PrepareDL {

	public static function prepareItem(array $data, \DocumentParser $modx, $_DL, \prepare_DL_Extender $_extDocLister)
	{
		$month = array(
			'1' =>  'января',
			'2'	=>  'февраля',
			'3' =>  'марта',
			'4' =>  'апреля',
			'5' =>  'мая',
			'6' =>  'июня',
			'7' =>  'июля',
			'8' =>  'августа',
			'9' =>  'сентября',
			'10' => 'октября',
			'11' => 'ноября',
			'12' => 'декабря'
		);
		$data['out_date'] = "";
		$data['seo_date'] = "";
		$date = trim($data['news_date']);
		if(($date = strtotime($data['news_date']))):
			$newsdate = date("j.n.Y", $date);
			$list = explode('.', $newsdate);
			$arr = array(
				$list[0],
				$month[$list[1]],
				$list[2]
			);
			$data['out_date'] = implode(' ', $arr)." года";
			$data['seo_date'] = date('c', $date);
		else:
			$date = intval($data['news_date']);
			$newsdate = date("j.n.Y", $date);
			$list = explode('.', $newsdate);
			$arr = array(
				$list[0],
				$month[$list[1]],
				$list[2]
			);
			$data['out_date'] = implode(' ', $arr)." года";
			$data['seo_date'] = date('c', $date);
		endif;
		$data['alt'] = Util::hsc($modx, $data['pagetitle']);
		//$data['introtext'] = nl2br($data['introtext']);
		//$data['imgSoc']
		return $data;
	}

	public static function prepareUrlDLMenu(array $data, \DocumentParser $modx, $_DL, \prepare_DL_Extender $_extDocLister)
	{
		$url = $data["url"];
		$site_url = $modx->config['site_url'];
		$reg = '@^https?://@';
		if(!preg_match($reg, $url)){
			$url = $site_url . ltrim($modx->rewriteUrls($url), "\s\/");
		}
		$data["url"] = $url;
		return $data;
	}

	public static function prepareDLMenu(array $data, \DocumentParser $modx, $_DL, \prepare_DL_Extender $_extDocLister)
	{
		$url = $data["url"];
		$site_url = $modx->config['site_url'];
		$reg = '@^https?://@';
		if(!preg_match($reg, $url)){
			$url = $site_url . $url;
		}
		$data["url"] = $url;
		$data["news_content"] = $modx->runSnippet(
			"DocLister",
			array(
				"parents" => $data["id"],
				"display" => 3,
				"tvList" => 'imageSoc, news_date',
				"tvPrefix" => "",
				"orderBy" => "news_date DESC",
				"sortBy" => "news_date",
				"sortDir" => "DESC",
				"tvSortType" => "TVDATETIME",
				"prepare" => "\ProjectSoft\PrepareDL::prepareItem",
				"noneTPL" => "",
				"ownerTPL" => "@FILE:projectsoft/tpl/news",
				"tpl" => "@FILE:projectsoft/tpl/news_tpl",
				"showParent" => "0",
				"urlScheme" => "full",
				"noneWrapOuter" => "0",
			)
		);
		$data["news_content"] = $data["news_content"] . '
		<p class="text-center">
			<a class="btn" href="' . $modx->makeUrl($data['id'], '', '', 'full') . '">Все новости</a>
		</p>';
		return $data;
	}
}
