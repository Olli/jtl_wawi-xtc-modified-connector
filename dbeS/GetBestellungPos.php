<?php
/**
 * jtlwawi_connector/dbeS/GetBestellungPos.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.07 / 06.06.07
*/

require_once("syncinclude.php");

$return=3;
if (auth())
{
	$return=5;
	if (intval($_POST['KeyBestellung']))
	{
		//glob einstellungen
		$cur_query = eS_execute_query("select versandMwst,tax_zone_id from eazysales_einstellungen");
		$einstellungen = mysql_fetch_object($cur_query);
		$allowTax = 1;
		
		$return = 0;		
		//hole orderposes
		$cur_query = eS_execute_query("select * from orders_products where orders_id=".intval($_POST['KeyBestellung'])." order by orders_products_id");
		while ($BestellungPos = mysql_fetch_object($cur_query))
		{
			$allowTax = $BestellungPos->allow_tax;
			if ($BestellungPos->allow_tax==0) //bruttopreis daraus machen
			{
				$BestellungPos->products_price*=((100+$BestellungPos->products_tax)/100);
			}

			//hole etl aufpreise
			$aufpreis=0;
			$aufpreise_query = eS_execute_query("select options_values_price,price_prefix from orders_products_attributes where orders_id=".$BestellungPos->orders_id." and orders_products_id=".$BestellungPos->orders_products_id." and options_values_price!=0");
			while ($aufpreis_arr = mysql_fetch_row($aufpreise_query))
			{
				$aufp=0;
				$aufp=$aufpreis_arr[0];
				if (($aufpreis_arr[1])=="-")
					$aufp*=-1;
				$aufpreis+=($aufp*(100+$BestellungPos->products_tax))/100;				
			}
			
			//mappe bestellpos
			$kBestellPos = setMappingBestellPos($BestellungPos->orders_products_id);
			echo(CSVkonform($kBestellPos).';');
			echo(CSVkonform(intval($_POST['KeyBestellung'])).';');
			echo(CSVkonform(getEsArtikel($BestellungPos->products_id)).';');
			echo(CSVkonform($BestellungPos->products_name).';');
			echo(CSVkonform($BestellungPos->products_price-$aufpreis).';');
			echo(CSVkonform($BestellungPos->products_tax).';');
			echo(CSVkonform($BestellungPos->products_quantity).';');
			echo("\n");
		}
		
		//letzte Positionen wie Versand, Mindermengenzuschlag, Rabatt, Kupon etc.
		$cur_query = eS_execute_query("select * from orders_total where (class=\"ot_shipping\" OR class=\"ot_cod_fee\" OR class=\"ot_coupon\" OR class=\"ot_discount\" OR class=\"ot_orderdiscount\" OR class=\"ot_gv\" OR class=\"ot_loworderfee\" OR class=\"ot_ps_fee\" OR class=\"ot_payment\") and orders_id=".intval($_POST['KeyBestellung'])." order by sort_order");
		while ($total_pos = mysql_fetch_object($cur_query))
		{
			if ($total_pos->class=="ot_shipping" || $total_pos->value!=0)
			{
				//mappe bestellpos
				$kBestellPos = setMappingBestellPos(0);
	
				$steuersatz = 0;
				switch ($total_pos->class)
				{
					case 'ot_shipping':
						//hole versand mwst aus einstellungen 
						if (!$allowTax)
						{
							$total_pos->value*=((100+$einstellungen->versandMwst)/100);
						}
						$steuersatz = $einstellungen->versandMwst;
						break;
					case 'ot_cod_fee':
						$tax_query = eS_execute_query("select configuration_value from configuration where configuration_key=\"MODULE_ORDER_TOTAL_COD_FEE_TAX_CLASS\"");
						$tax_class = mysql_fetch_object($tax_query);
						$steuersatz = get_tax($tax_class->configuration_value);
						if (!$allowTax)
						{
							$total_pos->value*=((100+$steuersatz)/100);
						}
						break;
					case 'ot_coupon':
						$tax_query = eS_execute_query("select configuration_value from configuration where configuration_key=\"MODULE_ORDER_TOTAL_COUPON_TAX_CLASS\"");
						$tax_class = mysql_fetch_object($tax_query);
						$steuersatz = get_tax($tax_class->configuration_value);
						$total_pos->value*=-1;
						if (!$allowTax)
						{
							$total_pos->value*=((100+$steuersatz)/100);
						}
						break;
					case 'ot_gv':
						$tax_query = eS_execute_query("select configuration_value from configuration where configuration_key=\"MODULE_ORDER_TOTAL_GV_TAX_CLASS\"");
						$tax_class = mysql_fetch_object($tax_query);
						$steuersatz = get_tax($tax_class->configuration_value);
						$total_pos->value*=-1;
						if (!$allowTax)
						{
							$total_pos->value*=((100+$steuersatz)/100);
						}
						break;
					case 'ot_loworderfee':
						$tax_query = eS_execute_query("select configuration_value from configuration where configuration_key=\"MODULE_ORDER_TOTAL_LOWORDERFEE_TAX_CLASS\"");
						$tax_class = mysql_fetch_object($tax_query);
						$steuersatz = get_tax($tax_class->configuration_value);
						if (!$allowTax)
						{
							$total_pos->value*=((100+$steuersatz)/100);
						}
						break;				
					case 'ot_ps_fee':
						$tax_query = eS_execute_query("select configuration_value from configuration where configuration_key=\"MODULE_ORDER_TOTAL_PS_FEE_TAX_CLASS\"");
						$tax_class = mysql_fetch_object($tax_query);
						$steuersatz = get_tax($tax_class->configuration_value);
						if (!$allowTax)
						{
							$total_pos->value*=((100+$steuersatz)/100);
						}
						break;
					case 'ot_discount':
						$total_pos->value*=-1;
						$steuersatz = $einstellungen->versandMwst;
						break;
					case 'ot_orderdiscount':
						$total_pos->value*=-1;
						$steuersatz = $einstellungen->versandMwst;
						break;
					case 'ot_payment':
						$steuersatz = $einstellungen->versandMwst;
						break;						
				}
				echo(CSVkonform($kBestellPos).';');
				echo(CSVkonform(intval($_POST['KeyBestellung'])).';');
				echo(CSVkonform("0").';');
				echo(CSVkonform(unhtmlentities($total_pos->title)).';');
				echo(CSVkonform(unhtmlentities($total_pos->value)).';');
				echo(CSVkonform($steuersatz).';');
				echo(CSVkonform("1").';');
				echo("\n");
			}
		}
	}
}
mysql_close();
echo($return);
logge($return);
?>