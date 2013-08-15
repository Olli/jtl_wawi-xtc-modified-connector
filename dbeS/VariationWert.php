<?php
/**
 * jtlwawi_connector/dbeS/VariationsWert.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.01 / 17.09.06
*/

require_once("syncinclude.php");

$return=3;
if (auth())
{
	if (intval($_POST["action"]) == 1 && intval($_POST['KeyEigenschaftWert']))
	{
		$return = 0;
		$EigenschaftWert = new stdClass();
		$EigenschaftWert->kEigenschaftWert = intval($_POST["KeyEigenschaftWert"]);
		$EigenschaftWert->kEigenschaft = intval($_POST["KeyEigenschaft"]);
		$EigenschaftWert->fAufpreis = floatval($_POST["Aufpreis"]);
		$EigenschaftWert->cName = realEscape($_POST["Name"]);
		$EigenschaftWert->nSort = intval($_POST["Sort"]);
		$EigenschaftWert->nLager = intval($_POST["Lager"]);
		$EigenschaftWert->cArtikelNr = realEscape($_POST["ArtikelNr"]);
		$EigenschaftWert->fGewichtDiff = floatval($_POST["GewichtDiff"]);

		//hole einstellungen
		$cur_query = eS_execute_query("select languages_id, tax_class_id, tax_zone_id from eazysales_einstellungen");
		$einstellungen = mysql_fetch_object($cur_query);
		
		$products_options_id = getFremdEigenschaft($EigenschaftWert->kEigenschaft);
		if ($products_options_id>0)
		{
			//schaue, ob dieser EigenschaftsWert bereits global existiert fÃÂ¼r diese Eigenschaft!!
			$cur_query = eS_execute_query("select products_options_values.products_options_values_id from products_options_values, products_options_values_to_products_options where products_options_values_to_products_options.products_options_id=$products_options_id and products_options_values_to_products_options.products_options_values_id=products_options_values.products_options_values_id and products_options_values.language_id=$einstellungen->languages_id and products_options_values.products_options_values_name=\"$EigenschaftWert->cName\"");
			$options_values = mysql_fetch_object($cur_query);
			
			if (!$options_values->products_options_values_id)
			{
				//erstelle diesen Wert global
				//hole max PK
				$cur_query = eS_execute_query("select max(products_options_values_id) from products_options_values");
				$max_id_arr = mysql_fetch_row($cur_query);
				$options_values->products_options_values_id = $max_id_arr[0]+1;
				eS_execute_query("insert into products_options_values (products_options_values_id,language_id,products_options_values_name) values ($options_values->products_options_values_id,$einstellungen->languages_id,\"$EigenschaftWert->cName\")");			
				
				//erstelle leere description fÃÂ¼r alle anderen Sprachen
				$sonstigeSprachen = getSonstigeSprachen($einstellungen->languages_id);
				if (is_array($sonstigeSprachen))
				{
					foreach ($sonstigeSprachen as $sonstigeSprache)
					{
						eS_execute_query("insert into products_options_values (products_options_values_id,language_id,products_options_values_name) values ($options_values->products_options_values_id,$sonstigeSprache,\"$EigenschaftWert->cName\")");
					}
				}
				
				//erstelle verknÃÂ¼pfung zwischen wert und eig
				eS_execute_query("insert into products_options_values_to_products_options (products_options_id,products_options_values_id) values($products_options_id,$options_values->products_options_values_id)");
			}
		
			//erstelle product_attribute
			$kArtikel = getEigenschaftsArtikel($EigenschaftWert->kEigenschaft);
			if ($kArtikel>0)
			{
				$products_id = getFremdArtikel($kArtikel);
				if ($products_id>0)
				{
					//hole products_tax_class_id
					$cur_query = eS_execute_query("select products_tax_class_id from products where products_id=".$products_id);
					$cur_tax = mysql_fetch_object($cur_query);
					$Aufpreis = ($EigenschaftWert->fAufpreis/(100+get_tax($cur_tax->products_tax_class_id)))*100;
					$Aufpreis_prefix = "+";
					if ($Aufpreis<0)
					{
						$Aufpreis_prefix = "-";
						$Aufpreis*=-1;
					}
					$Gewicht_prefix = "+";
					if ($EigenschaftWert->fGewichtDiff<0)
					{
						$Gewicht_prefix = "-";
						$EigenschaftWert->fGewichtDiff*=-1;
					}
					eS_execute_query("insert into products_attributes (
													products_id,
													options_id,
													options_values_id,
													options_values_price,
													price_prefix,
													attributes_model,
													attributes_stock,
													options_values_weight,
													weight_prefix,
													sortorder) 
													values(
													$products_id,
													$products_options_id,
													$options_values->products_options_values_id,
													$Aufpreis,
													\"".$Aufpreis_prefix."\",
													\"".$EigenschaftWert->cArtikelNr."\",
													$EigenschaftWert->nLager,
													$EigenschaftWert->fGewichtDiff,
													\"".$Gewicht_prefix."\",
													$EigenschaftWert->nSort)");
					$query = eS_execute_query("select LAST_INSERT_ID()");
					$last_attribute_id_arr = mysql_fetch_row($query);					
					setMappingEigenschaftsWert($EigenschaftWert->kEigenschaftWert, $last_attribute_id_arr[0], $kArtikel);
				}
			}
		}
 	}
	else
		$return=5;
}

mysql_close();
echo($return);
logge($return);
?>