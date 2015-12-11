<?php
	$brand = "RE";
	$model = "817";
	$page = 1;



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


function testPrint_r($element)
{
	echo "<pre>";
	print_r($element);
	echo "</pre>";
}


function find_elements_by_class($element_name, $class_name, $dom_document)
{
	$result;
	foreach ($dom_document->getElementsByTagName($element_name) as $node) {

		$class_value = $node->getAttribute("class");

		if(!empty($class_value) && $class_value==$class_name)
		{
			$result[] = $node;
		}
	}
	return $result;
}


$previous_element = 0;
$current_element = 1;
$current = 10;
$i =1;
// while($previous_element != $current)
// {
	$previous_element = $current;
	$uri = "http://sra.asso.fr/zendsearch/automobiles/recherche?identifiant&marque=$brand&modele=$model&energie&carrosserie&puissance&form_submit=1&url_recherche=%2Finformations-vehicules%2Fautomobiles%2Frecherche&url_fiche=%2Finformations-vehicules%2Fautomobiles%2Ffiche&itemPerPage=99&f_p=1&page=$i";
	$return = curl_call($uri);
	$doc = new DOMDocument();
	$doc->loadHTML($return);
	$current_element = find_elements_by_class("span", "orange", $doc)[0]->nodeValue;
	$element[$i][] = trim($current_element);
	$bandeau_elements = find_elements_by_class("td", "bandeau", $doc);
	foreach ($bandeau_elements as $key => $value) {
		$element[$i][] = trim($value->nodeValue);
	}
	$current = $element[1][3];
	//$i++;
//}
print_r($previous_element);
print_r($current);
testPrint_r($element);


