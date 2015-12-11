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

	function curl_call($url, $save = false, $file_name = "") {
		$curl_handle=curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $url);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; fr; rv:1.9.2) Gecko/20100115 Firefox/3.6');
		$content = curl_exec($curl_handle);
		curl_close($curl_handle);

		file_put_contents($file_name, $content);

		return $content;
	}

	$link = mysql_connect('mysql.montpellier.epsi.fr:5206', 'cars_user', 'cars34')
    	or die('Impossible de se connecter : ' . mysql_error());
	mysql_select_db('cars') or die('Impossible de sélectionner la base de données');

	$models = array();

	$file = 'page.html';
	$content = curl_call('http://www.sra.asso.fr/zendsearch/automobiles/recherche?identifiant=&marque=&modele=&energie=&carrosserie=&puissance=&form_submit=1&url_recherche=/informations-vehicules/automobiles/recherche&url_fiche=/informations-vehicules/automobiles/fiche&itemPerPage=99', true, $file);
	//$content = file_get_contents($file);

	$doc = new DOMDocument();
	$doc->loadHTML($content);

	$count = 0;

	$options = $doc->getElementById("marque")->getElementsByTagName("option");
	foreach ($options as $option) {
		if ($option->getAttribute("value") != "") {
			$file = 'results.json';
			$result = curl_call('http://www.sra.asso.fr/zendsearch/automobiles/xhr-get-datas?type=modele&marque='.$option->getAttribute('value').'&modele=&energie=&carrosserie=&puissance=', true, $file);
			//$result = file_get_contents($file);
			$json_result = json_decode($result);

			foreach ($json_result->view as $json_model) {
				if ($json_model->key != ""){
					$model = array();
					$model["brand"] = $option->getAttribute("value");
					$model["brand_label"] = $option->nodeValue;
					$model["model"] = $json_model->key;
					$model["model_label"] = $json_model->value;
					$models[$model["brand"]][] = $model;

					pr($model);

					$query = 'INSERT INTO tasks (brand, brand_label, model, model_label, status) VALUES ("'.$model["brand"].'", "'.$model["brand_label"].'", "'.$model["model"].'", "'.$model["model_label"].'", "PENDING")';
					mysql_query($query) or die('Échec de la requête : ' . mysql_error());
				}
			}

			usleep(25000);

			$count++;

			// if ($count >= 3)
			// 	break;
		}
	}

	// pr($models);

	// $query = '';
	// $result = mysql_query($query) or die('Échec de la requête : ' . mysql_error());

	// mysql_close($link);
