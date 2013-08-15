<?php
/**
 * jtlwawi_connector/dbeS/setArtikel.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.01 / 06.06.07
*/

require_once("syncinclude.php");

$return=3;
if (auth())
{
	$return=5;
	if (intval($_POST['KeyBestellPos']))
	{		
		$return = 0;
		//hole einstellungen
		$cur_query = eS_execute_query("select languages_id from eazysales_einstellungen");
		$einstellungen = mysql_fetch_object($cur_query);

		//hole orders_products_id
		$orders_products_id = getFremdBestellPos(intval($_POST['KeyBestellPos']));
			
		//hole alle Eigenschaften, die ausgewählt wurden zu dieser bestellung
		$cur_query = eS_execute_query("select orders_products_attributes.*, orders_products.products_tax, orders_products.products_id from orders_products_attributes, orders_products where orders_products_attributes.orders_products_id=".$orders_products_id." and orders_products.orders_products_id=orders_products_attributes.orders_products_id order by orders_products_attributes.orders_products_id");
		while ($WarenkorbPosEigenschaft = mysql_fetch_object($cur_query))
		{
			$preisprefix=1;
			if ($WarenkorbPosEigenschaft->price_prefix=="-")
				$preisprefix=-1;

			//hole attribut
			$attribut_query = eS_execute_query("select products_attributes.products_attributes_id from products_attributes, products_options, products_options_values where products_attributes.products_id=".$WarenkorbPosEigenschaft->products_id." and products_attributes.options_id=products_options.products_options_id and products_attributes.options_values_id=products_options_values.products_options_values_id and products_attributes.options_values_price=".$WarenkorbPosEigenschaft->options_values_price." and products_options.products_options_name=\"".mysql_real_escape_string($WarenkorbPosEigenschaft->products_options)."\" and products_options.language_id=".$einstellungen->languages_id." and products_options_values.products_options_values_name=\"".mysql_real_escape_string($WarenkorbPosEigenschaft->products_options_values)."\" and products_options_values.language_id=".$einstellungen->languages_id);
			
			$attribut_arr = mysql_fetch_row($attribut_query);
			
			echo(CSVkonform($WarenkorbPosEigenschaft->orders_products_attributes_id).';');
			echo(CSVkonform(intval($_POST['KeyBestellPos'])).';');
			echo(';');
			echo(CSVkonform(getEsEigenschaftsWert($attribut_arr[0],getEsArtikel($WarenkorbPosEigenschaft->products_id))).';');
			echo(CSVkonform(($WarenkorbPosEigenschaft->options_values_price+$WarenkorbPosEigenschaft->options_values_price*$WarenkorbPosEigenschaft->products_tax/100)*$preisprefix).';');
			echo("\n");
		}
	}
}

mysql_close();
echo($return);
logge($return);
?>