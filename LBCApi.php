<?php 
namespace App\LBCAPI;

/**
* 
*/
class LBCApi 
{
	
	private $url		= '';
	private $rawContent	= false;

	function __construct() {
	}

	function url($url) {
		$this->url = $url;
		return $this;
	}

	function setOptions($options) {
		if (!is_array($options)) {
			return $this;
		}
		foreach ($options as $key => $value) {
			
		}
		return $this;
	}

	function getJSON() {
		if (!$this->content) {
			$this->content = $this->getContent();
		}

		return json_encode($this->content);
	}

	function getContent() {
		if (!$this->rawContent) {
			$this->rawContent = $this->getRawContent();
		}
		$annonces = $this->extractMetadata();
		return $annonces;
	}

	private function extractMetadata() {
		$annoncesHtml = $this->extractAnnoncesHTML();
		$metas = [];
		foreach ($annoncesHtml as $annonce) {
			$meta				= [];
			$meta['reference']	= $this->extractReference($annonce);
			if (! empty($meta['reference'])) {
				$meta['date']		= $this->extractDate($annonce);
				$meta['prix']		= $this->extractPrix($annonce);
				$meta['url']		= $this->extractUrl($annonce);
				$meta['titre']		= $this->extractTitre($annonce);
				$meta['image']		= $this->extractImage($annonce);
				$meta['lieu']		= $this->extractLieu($annonce);
				$meta['urgent']		= $this->extractUrgent($annonce);
				$meta['categorie']	= $this->extractCategorie($annonce);
				$metas[]			= $meta;
			}
		}
		return $metas;
	}

	private function extractAnnoncesHTML() {
		$subject = preg_replace("(\n|\r)", "", $this->rawContent);
		$subject = preg_replace('/\s+/', " ", $subject);
		$subject = iconv("Windows-1252","UTF-8//TRANSLIT",$subject);
		$subject = html_entity_decode($subject, ENT_QUOTES, 'UTF-8');

		preg_match_all('/<div class="list-lbc">(.*)<div (class="list-gallery"|id="categories_container")/s', $subject, $matches);
		if (empty($matches)) {
			return [];
		}
		preg_match_all('/<a(.*?)\/a>/se', $matches[0][0], $annonces);

		return $annonces[0];
	}


	function getRawContent() {
		if ($this->rawContent) {
			return $this->rawContent;
		}

		$ch = curl_init(); 
	    curl_setopt($ch, CURLOPT_URL, $this->url); 
	    curl_setopt($ch, CURLOPT_HEADER, FALSE); 
	    curl_setopt($ch, CURLOPT_NOBODY, FALSE); // remove body 
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
	    $head = curl_exec($ch); 
	    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
	    curl_close($ch);
	    if ($httpCode==200) {
	    	$this->rawContent = $head;
	    }
	    return $head;
	}	

	public function getHeader() {
		$ch = curl_init(); 
	    curl_setopt($ch, CURLOPT_URL, $this->url); 
	    curl_setopt($ch, CURLOPT_HEADER, TRUE); 
	    curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body 
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
	    $head = curl_exec($ch); 
	    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
	    curl_close($ch);
	    return $head;
	}	

	private function extractUrl($string) {
		preg_match_all('/<a href="(.*?)"/s', $string, $result);
		if (isset($result[1][0])) {
			return $result[1][0];
		}	
		return false;
	}

	private function extractDate($annonce) {
		preg_match_all('/<div class="date"> <div>(.*?)<\/div> <div>(.*?)<\/div> <\/div>/',$annonce,$result);
		$aujourdhui = date("d m");
		$hier = date("d m", strtotime( '-1 days' ));
		$mois = array("Aujourd'hui", "Hier", "jan", "fév", "mars", "avr", "mai", "juin", "juillet", "août", "sept", "oct", "nov", "déc", "<br>", ":");
		$moisdecimal = array($aujourdhui, $hier, "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", " ", " ");
		$d = explode(" ", str_replace($mois, $moisdecimal, ($result[1][0])));
		$dh = explode(":",strip_tags($result[2][0]));
		//$date = mktime(intval($dh[0]), intval($dh[1]), 0, intval($d[1]), intval($d[0]), intval(date("Y")));
		$month = intval($d[1]);
                $year = ($month>intval(date("m")))?intval(date("Y"))-1:intval(date("Y"));
                $date = mktime(intval($dh[0]), intval($dh[1]), 0, $month, intval($d[0]), $year);
		return $date;
	}

	private function extractPrix($annonce) {
		preg_match_all('/<div class="price">(.*?)<\/div>/s', $annonce, $result);
		if (isset($result[1][0])) {
			$result[1][0] = trim($result[1][0]);
			return (int)preg_replace('/\s/', '', $result[1][0]);
		}
		return ;
	}

	private function extractImage($annonce) {
		preg_match_all('/<img src="(.*?)"/s', $annonce, $result);
		if (isset($result[1][0])) {
			$image = str_replace("thumbs", "images", $result[1][0]);
			return $image;
		}
		return;
	}

	private function extractTitre($annonce) {
		preg_match_all('/<h2 class="title">(.*?)<\/h2>/s', $annonce, $result);
		return trim($result[1][0]);
	}

	private function extractLieu($annonce) {
		preg_match_all('/<div class="placement">(.*?)<\/div>/s', $annonce, $result);
		return trim($result[1][0]);
	}

	private function extractCategorie($annonce) {
		preg_match_all('/<div class="category">(.*?)<\/div>/s', $annonce, $result);
		$category = preg_replace('/[^a-z0-9\s]+/', '', $result[1][0]);
		return trim($category);
	}

	private function extractReference($annonce) {
		preg_match_all('/\/([0-9]*?).htm/s', $annonce, $result);
		return isset($result[1][0])?$result[1][0]:'';
	}

	private function extractUrgent($annonce) {
		preg_match_all('/<div class="urgent">(.*?)<\/div>/s', $annonce, $result);
		if (isset($result[1][0])) {
			return $result[1][0];
		}
		return;
	}


}

 ?>
