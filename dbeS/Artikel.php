<?php
/**
 * jtlwawi_connector/dbeS/Artikel.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.12 / 13.03.07
*/

require_once("syncinclude.php");

$return=3;
if (auth())
{
	if (intval($_POST["action"]) == 1 && intval($_POST['KeyArtikel']))
	{
		$return = 0;
		//hole einstellungen
		$cur_query = eS_execute_query("select * from eazysales_einstellungen");
		$einstellungen = mysql_fetch_object($cur_query);
		
		$artikel = new stdClass();
		$artikel->kArtikel = realEscape($_POST["KeyArtikel"]);
		$artikel->cArtNr = realEscape($_POST["ArtikelNo"]);
		$artikel->cName = realEscape($_POST["ArtikelName"]);
		$artikel->cBeschreibung = realEscape($_POST["ArtikelBeschreibung"]);
		$artikel->fVKBrutto = realEscape($_POST["ArtikelVKBrutto"]);
		$artikel->fVKNetto = realEscape($_POST["ArtikelVKNetto"]);
		$artikel->fMwSt = realEscape($_POST["ArtikelMwSt"]);
		$artikel->cAnmerkung = realEscape($_POST["ArtikelAnmerkung"]);
		$artikel->nLagerbestand = max(realEscape($_POST["ArtikelLagerbestand"]),0);
		$artikel->cEinheit = realEscape($_POST["ArtikelEinheit"]);
		$artikel->nMindestbestellmaenge = realEscape($_POST["ArtikelMindBestell"]);
		$artikel->cBarcode = realEscape($_POST["ArtikelBarcode"]);
		$artikel->fVKHaendlerBrutto = realEscape($_POST["ArtikelVKHaendlerBrutto"]);
		$artikel->fVKHaendlerNetto = realEscape($_POST["ArtikelVKHaendlerNetto"]);
		$artikel->cTopArtikel = realEscape($_POST["TopAngebot"]);
		$artikel->fGewicht = realEscape($_POST["Gewicht"]);
		$artikel->cNeu = realEscape($_POST["Neu"]);
		$artikel->cKurzBeschreibung = realEscape($_POST["ArtikelKurzBeschreibung"]);
		$artikel->fUVP = realEscape($_POST["ArtikelUVP"]);
		$artikel->cHersteller = realEscape($_POST["Hersteller"]);
			
		$startseite=0;
		if ($artikel->cTopArtikel=="Y")
			$startseite=1;
		$shipping_status=0;
		if ($GLOBALS['einstellungen']->shipping_status_id>0)
			$shipping_status=$GLOBALS['einstellungen']->shipping_status_id;
			
		//existiert products_startpage in products?
		$products_startpage_exists = false;
		$result = mysql_query("SHOW COLUMNS FROM products");
		if (mysql_num_rows($result) > 0) {
			while ($row = mysql_fetch_object($result)) 
			{
				if ($row->Field == 'products_startpage')
					$products_startpage_exists = true;
			}
		}
			
		//update oder insert?
		$products_id = getFremdArtikel($artikel->kArtikel);
		if ($products_id>0)
		{
			//update

			//attribute lÃÂ¶schen
			eS_execute_query("delete from products_attributes where products_id=".$products_id);
			//KategorieArtikel lÃÂ¶schen
			eS_execute_query("delete from products_to_categories where products_id=".$products_id);
			
			//evtl. andere MwSt?
			$products_tax_class_id = holeSteuerId($artikel->fMwSt);
			//evtl. neuer Hersteller?
			$manufacturers_id = holeHerstellerId($artikel->cHersteller);
			//update products
			$products_statpage_piece="";
			if ($products_startpage_exists)
				$products_statpage_piece ="products_startpage=$startseite,";
			eS_execute_query("update products set products_fsk18=0, products_shippingtime=$shipping_status, ".$products_statpage_piece." products_model=\"$artikel->cArtNr\", products_price=\"".$artikel->fVKNetto."\", products_tax_class_id=\"$products_tax_class_id\", products_quantity=\"$artikel->nLagerbestand\", products_ean=\"$artikel->cBarcode\", products_weight=\"$artikel->fGewicht\", manufacturers_id=\"$manufacturers_id\", products_status=1, products_last_modified=now(), product_template=\"".$einstellungen->prod_product_template."\", options_template=\"".$einstellungen->prod_options_template."\" where products_id=".$products_id);
			//update products_description
			eS_execute_query("update products_description set products_name=\"$artikel->cName\", products_description=\"$artikel->cBeschreibung\", products_short_description=\"$artikel->cKurzBeschreibung\", products_keywords=\"\", products_meta_title=\"\", products_meta_description=\"\", products_meta_keywords=\"\", products_url=\"\" where products_id=".$products_id." and language_id=".$einstellungen->languages_id);
			//kundengrp preise
			insertPreise($products_id);
		}
		else 
		{
			//insert
			//hole Mwst classId
			$products_tax_class_id = holeSteuerId($artikel->fMwSt);
			//setze Hersteller, falls es ihn noch nicht gibt
			$manufacturers_id = holeHerstellerId($artikel->cHersteller);	
			
			$products_statpage_piece="";
			$products_statpage_piece_value="";
			if ($products_startpage_exists)
			{
				$products_statpage_piece ="products_startpage,";
				$products_statpage_piece_value=$startseite.",";
			}
				
			eS_execute_query("insert into products (products_shippingtime, $products_statpage_piece products_model, products_price, products_tax_class_id, products_quantity, products_ean, products_weight, manufacturers_id, product_template, options_template, products_status, products_date_added) values ($shipping_status,".$products_statpage_piece_value."\"".$artikel->cArtNr."\",".$artikel->fVKNetto.",$products_tax_class_id,$artikel->nLagerbestand,\"".$artikel->cBarcode."\",$artikel->fGewicht,$manufacturers_id,\"".$einstellungen->prod_product_template."\",\"".$einstellungen->prod_options_template."\",1,now())");
			//hole id
			$query = eS_execute_query("select LAST_INSERT_ID()");
			$products_id_arr = mysql_fetch_row($query);
			if ($products_id_arr[0]>0)
			{
				//mÃÂ¼ssen Preise in spezielle tabellen?
				$products_id=$products_id_arr[0];
				insertPreise($products_id_arr[0]);
				eS_execute_query("insert into products_description (products_id, products_name, products_description, products_short_description, language_id) values (".$products_id_arr[0].",\"".$artikel->cName."\", \"".$artikel->cBeschreibung."\", \"".$artikel->cKurzBeschreibung."\", $einstellungen->languages_id)");
				setMappingArtikel($artikel->kArtikel,$products_id_arr[0]);
				
				//erstelle leere description fÃÂ¼r alle anderen Sprachen
				$sonstigeSprachen = getSonstigeSprachen($einstellungen->languages_id);
				if (is_array($sonstigeSprachen))
				{
					foreach ($sonstigeSprachen as $sonstigeSprache)
					{
						//eS_execute_query("insert into products_description (products_id, products_name, language_id) values (".$products_id_arr[0].",\"".$artikel->cName."\", $sonstigeSprache)");
						eS_execute_query("insert into products_description (products_id, products_name, products_description, products_short_description, language_id) values (".$products_id_arr[0].",\"".$artikel->cName."\", \"".$artikel->cBeschreibung."\", \"".$artikel->cKurzBeschreibung."\", $sonstigeSprache)");
					}
				}
			}
			else 
			{
				//Fehler aufgetreten
				$return=1;
			}
		}
		
		if ($products_id>0)
		{
			//setze Kundengruppenerlaubnis fÃÂ¼r alle gruppen
			//Attribute.php bearbeitet Ausnahmen der Kundengruppenerlaubnis
			setzeKundengruppenerlaubnis("", $products_id);
			
			//VPE
			$products_vpe_id=0;
			//gibt es schon so einen products_vpe?
			$cur_query = eS_execute_query("select products_vpe_id from products_vpe where language_id=".$einstellungen->languages_id." and  products_vpe_name=\"".$artikel->cEinheit."\"");
			$products_vpe_id_arr = mysql_fetch_row($cur_query);
			if ($products_vpe_id_arr[0]>0)
			{
				$products_vpe_id=$products_vpe_id_arr[0];
			}
			else 
			{
				//fÃÂ¼ge neuen Shippingstatus ein
				$cur_query = eS_execute_query("select max(products_vpe_id) from products_vpe");
				$max_shipping_products_vpe_arr = mysql_fetch_row($cur_query);
				$products_vpe_id = $max_shipping_products_vpe_arr[0]+1;
				eS_execute_query("insert into products_vpe (products_vpe_id, language_id, products_vpe_name) values ($products_vpe_id, $einstellungen->languages_id, \"$artikel->cEinheit\")");
			}
			eS_execute_query("update products set products_vpe=".$products_vpe_id." where products_id=".$products_id);
			
			//erstelle Dummy fÃÂ¼r Sonderpreise, falls es keinen Sonderpreis fÃÂ¼r diesen Artikel gibt
			$spe_query = eS_execute_query("select specials_id from specials where products_id=".$products_id);
			$special_obj = mysql_fetch_object($spe_query);
			if (!$special_obj->specials_id)
			{
				eS_execute_query("insert into specials (products_id, status) values ($products_id,0)");
			}
		}
 	}
	else
		$return=5;

	if (intval($_POST["action"]) == 3 && intval($_POST['KeyArtikel']))
	{
		$products_id = getFremdArtikel(intval($_POST['KeyArtikel']));
		if ($products_id>0)
			eS_execute_query("update products set products_status=0 where products_id=".$products_id);
		$return = 0;
	}
}

mysql_close();
echo($return);
logge($return);

function insertPreise($products_id)
{
	$personalOfferTable = "personal_offers_by_customers_status_";
	$endKunden_arr = explode(";",$GLOBALS['einstellungen']->mappingEndkunde);
	foreach ($endKunden_arr as $customers_status_id)
	{
		if ($customers_status_id>=0 && strlen($customers_status_id)>0)
		{
			$table = $personalOfferTable.$customers_status_id;
			eS_execute_query("delete from $table where products_id=".$products_id);
			eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,1,".floatval($_POST["ArtikelVKNetto"]).")");
			if (intval($_POST["PAnz1"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["PAnz1"]).",".floatval($_POST["PPreis1"]).")");
			if (intval($_POST["PAnz2"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["PAnz2"]).",".floatval($_POST["PPreis2"]).")");
			if (intval($_POST["PAnz3"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["PAnz3"]).",".floatval($_POST["PPreis3"]).")");
			if (intval($_POST["PAnz4"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["PAnz4"]).",".floatval($_POST["PPreis4"]).")");
			if (intval($_POST["PAnz5"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["PAnz5"]).",".floatval($_POST["PPreis5"]).")");
		}
	}
	$haendlerKunden_arr = explode(";",$GLOBALS['einstellungen']->mappingHaendlerkunde);
	foreach ($haendlerKunden_arr as $customers_status_id)
	{
		if ($customers_status_id>=0 && strlen($customers_status_id)>0)
		{
			$table = $personalOfferTable.$customers_status_id;
			eS_execute_query("delete from $table where products_id=".$products_id);
			eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,1,".floatval($_POST["ArtikelVKHaendlerNetto"]).")");
			if (intval($_POST["HAnz1"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["HAnz1"]).",".floatval($_POST["HPreis1"]).")");
			if (intval($_POST["HAnz2"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["HAnz2"]).",".floatval($_POST["HPreis2"]).")");
			if (intval($_POST["HAnz3"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["HAnz3"]).",".floatval($_POST["HPreis3"]).")");
			if (intval($_POST["HAnz4"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["HAnz4"]).",".floatval($_POST["HPreis4"]).")");
			if (intval($_POST["HAnz5"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["HAnz5"]).",".floatval($_POST["HPreis5"]).")");
		}
	}
}

function holeHerstellerId($cHersteller)
{
	if (strlen($cHersteller)>0)
	{
		//ex. dieser Hersteller?
		$cur_query = eS_execute_query("select manufacturers_id from manufacturers where manufacturers_name=\"".$cHersteller."\"");
		$manu = mysql_fetch_object($cur_query);
		if ($manu->manufacturers_id>0)
			return $manu->manufacturers_id;
		else 
		{
			//erstelle diesen Hersteller
			eS_execute_query("insert into manufacturers (manufacturers_name, date_added) values (\"".$cHersteller."\", now())");
			$query = eS_execute_query("select LAST_INSERT_ID()");
			$manu_id_arr = mysql_fetch_row($query);
			eS_execute_query("insert into manufacturers_info (manufacturers_id, languages_id) values (".$manu_id_arr[0].", ".$GLOBALS['einstellungen']->languages_id.")");
			return $manu_id_arr[0];
		}
	}
	return 0;
}

function holeSteuerId($MwSt)
{
	//existiert so ein Steuersatz ?
	$cur_query = eS_execute_query("select tax_class_id from tax_rates where tax_zone_id=".$GLOBALS['einstellungen']->tax_zone_id." and tax_rate=".$MwSt);
	$tax = mysql_fetch_object($cur_query);
	if ($tax->tax_class_id>0)
		return $tax->tax_class_id;
	else 
	{
		//erstelle klasse
		eS_execute_query("insert into tax_class (tax_class_title, date_added) values (\"JTL-Wawi Steuerklasse ".$MwSt."%\", now())");
		$query = eS_execute_query("select LAST_INSERT_ID()");
		$tax_class_id_arr = mysql_fetch_row($query);
		//fÃÂ¼ge diesen Steuersatz ein
		eS_execute_query("insert into tax_rates (tax_zone_id, tax_class_id, tax_priority, tax_rate, date_added) values (".$GLOBALS['einstellungen']->tax_zone_id.",".$tax_class_id_arr[0].", ".$GLOBALS['einstellungen']->tax_priority.", ".$MwSt.", now())");
		return $tax_class_id_arr[0];
	}
}


?>