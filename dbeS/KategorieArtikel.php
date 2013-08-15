<?php
/**
 * jtlwawi_connector/dbeS/KategorieArtikel.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.0 / 16.06.06
*/
require_once("syncinclude.php");

$return=3;
if (auth())
{
	if (intval($_POST["action"]) == 1 && intval($_POST['KeyKategorieArtikel']))
	{
		$return = 0;
		$KategorieArtikel = new stdClass();
		$KategorieArtikel->kArtikel = intval($_POST["KeyArtikel"]);
		$KategorieArtikel->kKategorie = intval($_POST["KeyKategorie"]);
		$products_id = getFremdArtikel($KategorieArtikel->kArtikel);
		$categories_id = getFremdKategorie($KategorieArtikel->kKategorie);
		if ($products_id && $categories_id)
			eS_execute_query("insert into products_to_categories (products_id, categories_id) values ($products_id, $categories_id)");
 	}
	else
		$return=5;

	if (intval($_POST["action"]) == 3 && intval($_POST['KeyKategorieArtikel']))
	{
		$return = 0;
	}
}

mysql_close();
echo($return);
logge($return);
?>