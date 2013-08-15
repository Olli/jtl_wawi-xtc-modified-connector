<?php
/**
 * jtlwawi_connector/dbeS/Attribute.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.08 / 13.03.07
*/

require_once("syncinclude.php");

$return=3;
if (auth())
{
	if (intval($_POST["action"]) == 1 && intval($_POST['KeyAttribut']))
	{
		$return = 0;
		$Attribut = new stdClass();		
		$Attribut->products_id = getFremdArtikel(intval($_POST["KeyArtikel"]));
		$Attribut->name = $_POST["Name"];
		$Attribut->content = $_POST["StringWert"];
		if (strlen($_POST["TextWert"])>0)
			$Attribut->content = $_POST["TextWert"];
		attributBearbeiten ($Attribut);
	}
}

mysql_close();
echo($return);
logge($return);

//Attribut wird verarbeitet / in DB insertet
function attributBearbeiten ($Attribut)
{
	if ($Attribut->products_id>0)
	{
		//hole einstellungen
		$cur_query = eS_execute_query("select * from eazysales_einstellungen");
		$einstellungen = mysql_fetch_object($cur_query);
		
		switch (strtolower($Attribut->name))
		{
			case 'reihung':
				if ($Attribut->content)
					eS_execute_query("update products set products_sort=".intval($Attribut->content)." where products_id=".$Attribut->products_id);
				break;
			case 'reihung startseite':
				if ($Attribut->content)
					eS_execute_query("update products set products_startpage_sort=".intval($Attribut->content)." where products_id=".$Attribut->products_id);
				break;
			case 'suchbegriffe':				
				eS_execute_query("update products_description set products_keywords=\"".realEscape($Attribut->content)."\" where language_id=".$einstellungen->languages_id." and products_id=".$Attribut->products_id);
				break;
			case 'meta title':
				eS_execute_query("update products_description set products_meta_title=\"".realEscape($Attribut->content)."\" where language_id=".$einstellungen->languages_id." and products_id=".$Attribut->products_id);
				break;
			case 'meta description':
				eS_execute_query("update products_description set products_meta_description=\"".realEscape($Attribut->content)."\" where language_id=".$einstellungen->languages_id." and products_id=".$Attribut->products_id);
				break;
			case 'meta keywords':
				eS_execute_query("update products_description set products_meta_keywords=\"".realEscape($Attribut->content)."\" where language_id=".$einstellungen->languages_id." and products_id=".$Attribut->products_id);
				break;
			case 'herstellerlink':
				eS_execute_query("update products_description set products_url=\"".realEscape($Attribut->content)."\" where language_id=".$einstellungen->languages_id." and products_id=".$Attribut->products_id);
				break;
			case 'lieferstatus':
				if ($Attribut->content)
				{
					$shipping_id=0;
					//gibt es schon so einen shipping status?
					$cur_query = eS_execute_query("select shipping_status_id from shipping_status where language_id=".$einstellungen->languages_id." and shipping_status_name=\"".realEscape($Attribut->content)."\"");
					$shipping_status_id_arr = mysql_fetch_row($cur_query);
					if ($shipping_status_id_arr[0]>0)
					{
						$shipping_id=$shipping_status_id_arr[0];
					}
					else 
					{
						//fÃÂ¼ge neuen Shippingstatus ein
						$cur_query = eS_execute_query("select max(shipping_status_id) from shipping_status");
						$max_shipping_status_id_arr = mysql_fetch_row($cur_query);
						$shipping_id = $max_shipping_status_id_arr[0]+1;
						eS_execute_query("insert into shipping_status (shipping_status_id, language_id, shipping_status_name) values ($shipping_id, $einstellungen->languages_id, \"$Attribut->content\")");
					}
					eS_execute_query("update products set products_shippingtime=".$shipping_id." where products_id=".$Attribut->products_id);
				}
				break;
			case 'fsk 18':
				if ($Attribut->content=="ja")
				{
					eS_execute_query("update products set products_fsk18=1 where products_id=".$Attribut->products_id);
				}
				break;
			case 'rabatt erlaubt':
				eS_execute_query("update products set products_discount_allowed=".floatval($Attribut->content)." where products_id=".$Attribut->products_id);
				break;
			case 'vpe wert':
				if ($Attribut->content)
					eS_execute_query("update products set products_vpe_value=".floatval($Attribut->content)." where products_id=".$Attribut->products_id);
				break;
			case 'vpe name':
				if ($Attribut->content)
				{
					$products_vpe_id=0;
					//gibt es schon so einen products_vpe?
					$cur_query = eS_execute_query("select products_vpe_id from products_vpe where language_id=".$einstellungen->languages_id." and  products_vpe_name=\"".$Attribut->content."\"");
					$products_vpe_id_arr = mysql_fetch_row($cur_query);
					if ($products_vpe_id_arr[0]>0)
					{
						$products_vpe_id=$products_vpe_id_arr[0];
					}
					else 
					{
						$cur_query = eS_execute_query("select max(products_vpe_id) from products_vpe");
						$max_shipping_products_vpe_arr = mysql_fetch_row($cur_query);
						$products_vpe_id = $max_shipping_products_vpe_arr[0]+1;
						eS_execute_query("insert into products_vpe (products_vpe_id, language_id, products_vpe_name) values ($products_vpe_id, $einstellungen->languages_id, \"$Attribut->content\")");
					}
					eS_execute_query("update products set products_vpe=".$products_vpe_id." where products_id=".$Attribut->products_id);
				}
				break;
			case 'vpe anzeigen':
				if ($Attribut->content=="ja")
				{
					eS_execute_query("update products set products_vpe_status=1 where products_id=".$Attribut->products_id);
				}
				elseif ($Attribut->content=="nein") 
				{
					eS_execute_query("update products set products_vpe_status=0 where products_id=".$Attribut->products_id);
				}
				break;
			case 'produktvorlage':
				if ($Attribut->content)
				{
					eS_execute_query("update products set product_template=\"".realEscape($Attribut->content)."\" where products_id=".$Attribut->products_id);
				}
				break;
			case 'variationsvorlage':
				if ($Attribut->content)
				{
					eS_execute_query("update products set options_template=\"".realEscape($Attribut->content)."\" where products_id=".$Attribut->products_id);
				}
				break;
			case 'produktstatus':
				if ($Attribut->content==0 || $Attribut->content==1)
					eS_execute_query("update products set products_status=".$Attribut->content." where products_id=".$Attribut->products_id);
				break;
			case 'erscheinungsdatum':
				if ($Attribut->content)
				{
					list ($tag,$monat,$jahr)= preg_split('\.',$Attribut->content);
					$date = $jahr."-".$monat."-".$tag." 00:00:00";
					eS_execute_query("update products set products_date_available=\"".realEscape($date)."\" where products_id=".$Attribut->products_id);
				}
				break;
			case 'gruppenerlaubnis':
				setzeKundengruppenerlaubnis($Attribut->content, $Attribut->products_id);
				break;
			//
			//Sonderangebote BEGIN
			//
			case 'sonder_preis':
				if ($Attribut->content>0)
				{
					//umrechnen auf Netto
					//hol steuerklasse zum produkt
					$cur_query = eS_execute_query("select products_tax_class_id from products where products_id=".$Attribut->products_id);
					$products_tax_arr = mysql_fetch_row($cur_query);
					$tax = get_tax($products_tax_arr[0], $einstellungen->tax_zone_id);
					if ($tax>0)
					{
						$Attribut->content = $Attribut->content/(($tax+100)/100.0);
					}
					eS_execute_query("update specials set specials_new_products_price=\"".$Attribut->content."\" where products_id=".$Attribut->products_id);
				}
				break;
			case 'sonder_menge':
					eS_execute_query("update specials set specials_quantity=\"".$Attribut->content."\" where products_id=".$Attribut->products_id);
				break;
			case 'sonder_enddatum':
				if ($Attribut->content)
				{
					list ($tag,$monat,$jahr)= preg_split('\.',$Attribut->content);
					$date = $jahr."-".$monat."-".$tag." 00:00:00";
					eS_execute_query("update specials set expires_date=\"".realEscape($date)."\" where products_id=".$Attribut->products_id);
				}
				break;
			case 'sonder_aktiv':
				if ($Attribut->content==0 || $Attribut->content==1)
				{
					eS_execute_query("update specials set status=".$Attribut->content.", date_status_change=now(), specials_last_modified=now()  where products_id=".$Attribut->products_id);
				}
				break;
			//
			//Sonderangebote END
			//
			default:
				if ($Attribut->content && ES_ATTRIBUTE_AN_BESCHREIBUNG_ANHAENGEN==1)
				{
					//an description anhÃÂ¤ngen
					$cur_query = eS_execute_query("select products_description from products_description where products_id=".$Attribut->products_id." and language_id=".$einstellungen->languages_id);
					$product_desc = mysql_fetch_row($cur_query);
					$desc = $product_desc[0]."<br><br><b>".$Attribut->name."</b>: ".$Attribut->content;
					eS_execute_query("update products_description set products_description=\"".$desc."\" where products_id=".$Attribut->products_id." and language_id=".$einstellungen->languages_id);
				}
				break;
		}
	}
}


?>