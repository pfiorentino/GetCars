<?php
	// error_reporting(-1);
	// ini_set('display_errors', 'On');

	// error_reporting(0);
	// ini_set('display_errors', 'Off');

	error_reporting(E_ERROR | E_PARSE);

	function pr($data) {
		//echo "<pre>";
		print_r($data);
		//echo "</pre>";
	}

	function vd($data) {
		echo "<pre>";
		var_dump($data);
		echo "</pre>";
	}


	// $brand = "RE";
	// $model = "817";
	$brand = $argv[1];
	$model = $argv[2];

	if (empty($brand) || empty($model)){
		die("invalid params\n");
	}

	$car = array();

	$link = mysql_connect('mysql.montpellier.epsi.fr:5206', 'cars_user', 'cars34')
    	or die('Impossible de se connecter : ' . mysql_error());
	mysql_select_db('cars') or die('Impossible de sélectionner la base de données');


	function curl_call($url) {
		$curl_handle=curl_init();
		$defaults = array(
		        CURLOPT_POST => 1,
		        CURLOPT_HEADER => 0,
		        CURLOPT_URL => $url,
		        CURLOPT_FRESH_CONNECT => 1,
		        CURLOPT_RETURNTRANSFER => 1,
		        CURLOPT_FORBID_REUSE => 1,
		        CURLOPT_TIMEOUT => 4,
				CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
		    );
		curl_setopt_array($curl_handle, $defaults);


		// curl_setopt($curl_handle, CURLOPT_URL, $url);
		// curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
		// curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		// curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
		$content = curl_exec($curl_handle);
		curl_close($curl_handle);
		return $content;
	}

	function find_elements_by_class($element_name, $class_name, $dom_document) {
		foreach ($dom_document->getElementsByTagName($element_name) as $node) {

			$class_value = $node->getAttribute("class");

			if(!empty($class_value) && strpos($class_value, $class_name) !== FALSE) {
				$result[] = $node;
			}
		}
		return $result;
	}

	function insertInDb($array, $table_name) {
		$clean_array = array();
		foreach ($array as $key => $value) {
			$clean_array[] = mysql_real_escape_string($value);
		}
		$query = 'INSERT IGNORE INTO '.$table_name.' ('.implode(", ", array_keys($array)).') VALUES ("'.implode('", "', $clean_array).'")';
		mysql_query($query) or die('Échec de la requête : ' . mysql_error());
	}

	function microtime_float() {
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}

	$previous_car = "";
	$current_car = "";
	$page = 1;
	do {
		$begin_time = microtime_float();
		usleep(500000);
		$previous_car = $current_car;
		$url = "http://sra.asso.fr/zendsearch/automobiles/recherche?identifiant&marque=$brand&modele=$model&energie&carrosserie&puissance&form_submit=1&url_recherche=%2Finformations-vehicules%2Fautomobiles%2Frecherche&url_fiche=%2Finformations-vehicules%2Fautomobiles%2Ffiche&itemPerPage=99&f_p=1&page=$page";
		$time1 = microtime_float();
		$result = curl_call($url);
		$time2 = microtime_float();
		echo "CURL query: ".($time2-$time1)."s\n";

		if ($result){
			$time3 = microtime_float();
			$doc = new DOMDocument();
			$doc->loadHTML($result);
			$time4 = microtime_float();

			echo "DOM loading: ".($time4-$time3)."s\n";

			$time5 = microtime_float();
			$title = find_elements_by_class("h1", "titre", $doc);
			$titleValue = trim(find_elements_by_class("span", "orange", $title[0])[0]->nodeValue);
			$explodedTitle = explode(" - ", $titleValue);
			$car["brand"] = $explodedTitle[0];
			$car["model"] = $explodedTitle[1];
			$car["version"] = $explodedTitle[count($explodedTitle)-1];

			$caracts = find_elements_by_class("td", "bandeau", $doc);
			foreach ($caracts as $caract) {
				$valueRaw = find_elements_by_class("span", "bold", $caract);
				$value = trim($valueRaw[0]->nodeValue);

				if (strpos($caract->nodeValue, "Code identifiant") !== FALSE){
					$car["internal_id"] = $value;
					$current_car = $value;
				} else if (strpos($caract->nodeValue, "Carrosserie") !== FALSE){
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
			$time6 = microtime_float();
			echo "DOM parsing: ".($time6-$time5)."s\n";

			pr($car);

			$time7 = microtime_float();
			insertInDb($car, "cars");
			$time8 = microtime_float();
			echo "DB insertion: ".($time8-$time7)."s\n\n";
		} else {
			echo "ERROR result is empty for page ".$url."\n\n";
			$current_car = "rand_".rand();
		}

		$page++;
	} while($previous_car != $current_car);

	$query = "UPDATE `tasks` SET status = 'DONE' WHERE brand = '$brand' AND model = '$model'";
	mysql_query($query);

	echo "\n";
	echo "====================";
	echo " JOB ENDED ";
	echo "====================";
