<?php
	error_reporting(-1);
	ini_set('display_errors', 'On');

	function pr($data) {
		echo "<pre>";
		print_r($data);
		echo "</pre>";
	}

	function vd($data) {
		echo "<pre>";
		var_dump($data);
		echo "</pre>";
	}


	$brand = "RE";
	$model = "817";
	$page = 1;

	$car = array();

	$link = mysql_connect('mysql.montpellier.epsi.fr:5206', 'cars_user', 'cars34')
    	or die('Impossible de se connecter : ' . mysql_error());
	mysql_select_db('cars') or die('Impossible de sélectionner la base de données');


	function curl_call($url) {
		$curl_handle=curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $url);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; fr; rv:1.9.2) Gecko/20100115 Firefox/3.6');
		$content = curl_exec($curl_handle);
		curl_close($curl_handle);
		return $content;
	}

	function find_elements_by_class($element_name, $class_name, $dom_document) {
		$result;
		foreach ($dom_document->getElementsByTagName($element_name) as $node) {

			$class_value = $node->getAttribute("class");

			if(!empty($class_value) && strpos($class_value, $class_name) !== FALSE) {
				$result[] = $node;
			}
		}
		return $result;
	}

	function insertInDb($array, $table_name) {
		$query = 'INSERT INTO '.$table_name.' ('.implode(", ", array_keys($array)).') VALUES ("'.implode('", "', $array).'")';
		mysql_query($query) or die('Échec de la requête : ' . mysql_error());
	}

	$previous_element = 0;
	$current_element = 1;
	$current = 10;
	$i =1;
// while($previous_element != $current)
// {
		$previous_element = $current;
		$url = "http://sra.asso.fr/zendsearch/automobiles/recherche?identifiant&marque=$brand&modele=$model&energie&carrosserie&puissance&form_submit=1&url_recherche=%2Finformations-vehicules%2Fautomobiles%2Frecherche&url_fiche=%2Finformations-vehicules%2Fautomobiles%2Ffiche&itemPerPage=99&f_p=1&page=$i";
		$result = curl_call($url);

		$doc = new DOMDocument();
		$doc->loadHTML($result);

		$title = find_elements_by_class("h1", "titre", $doc);
		$titleValue = trim(find_elements_by_class("span", "orange", $title[0])[0]->nodeValue);
		$explodedTitle = explode(" - ", $titleValue);
		$car["brand"] = $explodedTitle[0];
		$car["model"] = $explodedTitle[1];
		$car["version"] = $explodedTitle[3];

		$caracts = find_elements_by_class("td", "bandeau", $doc);
		foreach ($caracts as $caract) {
			$valueRaw = find_elements_by_class("span", "bold", $caract);
			$value = trim($valueRaw[0]->nodeValue);

			if (strpos($caract->nodeValue, "Carrosserie") !== FALSE){
				$car["doors"] = $value;
			} else if (strpos($caract->nodeValue, "Énergie") !== FALSE){
				$car["fuel_type"] = $value;
			} else if (strpos($caract->nodeValue, "Génération") !== FALSE){
				$car["generation"] = $value;
			} else if (strpos($caract->nodeValue, "Puiss. admin.") !== FALSE){
				$car["rated_hp"] = $value;
			}
		}

		$gearboxCaracts = find_elements_by_class("td", "w50", $doc);
		foreach ($gearboxCaracts as $gearboxCaract) {
			$valueRaw = find_elements_by_class("span", "bold", $gearboxCaract);
			$value = trim($valueRaw[0]->nodeValue);

			if (strpos($gearboxCaract->nodeValue, "Type Mines") !== FALSE){
				$car["mines_type"] = $value;
			} else if (strpos($gearboxCaract->nodeValue, "Type") !== FALSE){
				$car["gearbox"] = $value;
			} else if (strpos($gearboxCaract->nodeValue, "Nombre de rapports") !== FALSE){
				$car["gearbox"] .= " - ".$value." rapports";
			}
		}

		pr($car);
		insertInDb($car, "cars");
		//$i++;
	//}




