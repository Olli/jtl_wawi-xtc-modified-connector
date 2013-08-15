<?php
/**
 * jtlwawi_connector/dbeS/syncinclude.php
 * Tools fÃÂ¼r Sync
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.05 / 06.06.07
*/

require_once("../paths.php");

//get DB Connecion
// include server parameters
require_once (DOCROOT_PATH.'admin/includes/configure.php');
require_once (DIR_FS_INC . 'xtc_db_connect.inc.php');
require_once (DIR_FS_INC . 'xtc_db_query.inc.php');

xtc_db_connect() or die('Kann Datenbankverbindung nicht herstellen! ÃÂberprÃÂ¼fen Sie den DOCROOT_PATH im jtlwawi_connector/paths.php Script Zeile 15. Der Pfad muss entweder relativ oder absolut auf das Rootverzeichnis Ihres Shops zeigen (meist <i>xtcommerce</i>).');

define ('ES_ENABLE_LOGGING',0);
define ('ES_ATTRIBUTE_AN_BESCHREIBUNG_ANHAENGEN',1);

function eS_execute_query($query)
{	
	//return xtc_db_query($query);
	return mysql_query($query);
}

/**
 * Authentifiziert die Anfrage
 *
 * @return Bool true, wenn Auth ok, sonst false
 */
function auth()
{
	$cName = $_POST["userID"];
	$cPass = $_POST["userPWD"];

	$cur_query = eS_execute_query("select * from eazysales_sync");
	$loginDaten = mysql_fetch_object($cur_query);
	if ($cName == $loginDaten->cName && $cPass == $loginDaten->cPass)
		return true;

	return false;
}

/**
 * Gibt einen vardump eines Objekts aus, der sich besser loggen lÃÂ¤sst.
 *
 * @param Object $vardump Objekt, das gedumpt werden soll
 * @param int $key SchlÃÂ¼ssel
 * @param int $level Aktuellw Tiefe
 * @param String $return RÃÂ¼ckgabestring
 * @return String verbesserten Vardump
 */
function Dump($vardump)
{
	if (gettype($vardump)!="object" && gettype($vardump)!="array")
		$return.= $vardump;
	elseif (gettype($vardump)=="object")
	{
		foreach(get_object_vars($vardump) as $key => $value)
		{
			$return.= $key." => ".Dump($value).", ";
		}
	}
	elseif (gettype($vardump)=="array")
	{
		foreach ($vardump as $key => $value)
			$return.= $key." => ".Dump($value).", ";
	}
	if ($return{strlen($return)-2}==',')
		return substr($return,0,strlen($return)-2)." ";
	else 
		return $return;
}

/**
 * FÃÂ¼gt AnfÃÂ¼hrungszeichen vorne und am Ende an, sobald die Variable nicht leer.
 *
 * @param mixed $value
 * @return $value mit AnfÃÂ¼hrungszeichen vorne und hinten. Falls $value leer, werden diese Zeichen nicht hinzugefÃÂ¼gt.
 */
function CSVkonform($value)
{
	if (strlen($value)>0)
		return '"'.str_replace('"','""',$value).'"';
}

function datetime2germanDate($datetime)
{
	list ($datum, $uhrzeit) = explode(" ", $datetime);
	list ($jahr, $monat, $tag) = explode("-", $datum);
	list ($std, $min, $sec) = explode(":", $uhrzeit);
	return $tag.'.'.$monat.'.'.$jahr.' '.$std.':'.$min.':'.$sec;
}

function unhtmlentities($string)
{
   // replace numeric entities
   $string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
   $string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
   // replace literal entities
   $trans_tbl = get_html_translation_table(HTML_ENTITIES);
   $trans_tbl = array_flip($trans_tbl);
   return strtr($string, $trans_tbl);
}

function setMappingArtikel ($eS_key, $mein_key)
{
	$eS_key = intval($eS_key);
	$mein_key = intval($mein_key);
	if ($mein_key && $eS_key)
	{
		//ist mein_key schon drin?
		$cur_query = eS_execute_query("select products_id from eazysales_martikel where products_id=".$mein_key);
		$prod = mysql_fetch_object($cur_query);
		if ($prod->products_id>0)
			return "";
		else 
		{
			eS_execute_query("insert into eazysales_martikel (products_id, kArtikel) values ($mein_key,$eS_key)");
		}
	}
}

function setMappingKategorie ($eS_key, $mein_key)
{
	$eS_key = intval($eS_key);
	$mein_key = intval($mein_key);
	if ($mein_key && $eS_key)
	{
		//ist mein_key schon drin?
		$cur_query = eS_execute_query("select categories_id from eazysales_mkategorie where categories_id=".$mein_key);
		$prod = mysql_fetch_object($cur_query);
		if ($prod->categories_id>0)
			return "";
		else 
		{
			eS_execute_query("insert into eazysales_mkategorie (categories_id, kKategorie) values ($mein_key,$eS_key)");
		}
	}
}

function setMappingEigenschaft ($eS_key, $mein_key, $kArtikel)
{
	$eS_key = intval($eS_key);
	$mein_key = intval($mein_key);
	if ($mein_key && $eS_key && $kArtikel)
	{
		eS_execute_query("delete from eazysales_mvariation where kEigenschaft=".$eS_key);
		eS_execute_query("insert into eazysales_mvariation (kEigenschaft,products_options_id,kArtikel) values ($eS_key, $mein_key, $kArtikel)");
	}
}

function setMappingBestellPos ($mein_key)
{
	$mein_key = intval($mein_key);
	eS_execute_query("delete from eazysales_mbestellpos where orders_products_id=".$mein_key);
	eS_execute_query("insert into eazysales_mbestellpos (orders_products_id) values ($mein_key)");
	$query = eS_execute_query("select LAST_INSERT_ID()");
	$id_arr = mysql_fetch_row($query);
	return $id_arr[0];
}

function setMappingEigenschaftsWert ($eS_key, $mein_key, $kArtikel)
{
	$eS_key = intval($eS_key);
	$mein_key = intval($mein_key);
	if ($mein_key && $eS_key)
	{
		eS_execute_query("delete from eazysales_mvariationswert where kEigenschaftsWert=".$eS_key);
		//ist mein_key schon drin?
		$cur_query = eS_execute_query("select products_attributes_id from eazysales_mvariationswert where kArtikel=$kArtikel and products_attributes_id=".$mein_key);
		$prod = mysql_fetch_object($cur_query);
		if ($prod->products_id>0)
			return "";
		else 
		{
			eS_execute_query("insert into eazysales_mvariationswert (products_attributes_id, kEigenschaftsWert, kArtikel) values ($mein_key,$eS_key,$kArtikel)");
		}
	}
}

function getFremdArtikel($eS_key)
{
	if ($eS_key>0)
	{
		$cur_query = eS_execute_query("select products_id from eazysales_martikel where kArtikel=".$eS_key);
		$prod = mysql_fetch_object($cur_query);
		return $prod->products_id;
	}
	return 0;
}

function getEsArtikel($mein_key)
{
	if ($mein_key>0)
	{
		$cur_query = eS_execute_query("select kArtikel from eazysales_martikel where products_id=".$mein_key);
		$prod = mysql_fetch_object($cur_query);
		return $prod->kArtikel;
	}
	return 0;
}

function getFremdKategorie($eS_key)
{
	if ($eS_key>0)
	{
		$cur_query = eS_execute_query("select categories_id from eazysales_mkategorie where kKategorie=".$eS_key);
		$prod = mysql_fetch_object($cur_query);
		return $prod->categories_id;
	}
	return 0;
}

function getEsKategorie($mein_key)
{
	if ($mein_key>0)
	{
		$cur_query = eS_execute_query("select kKategorie from eazysales_mkategorie where categories_id=".$mein_key);
		$prod = mysql_fetch_object($cur_query);
		return $prod->kKategorie;
	}
	return 0;
}

function getFremdBestellPos($eS_key)
{
	$cur_query = eS_execute_query("select orders_products_id from eazysales_mbestellpos where kBestellPos=".$eS_key);
	$prod = mysql_fetch_object($cur_query);
	return $prod->orders_products_id;
}

function getEsEigenschaft($mein_key, $kArtikel)
{
	if ($mein_key>0 && $kArtikel>0)
	{
		$cur_query = eS_execute_query("select kEigenschaft from eazysales_mvariation where kArtikel=".$kArtikel." and products_options_id=".$eS_key);
		$prod = mysql_fetch_object($cur_query);
		return $prod->kEigenschaft;
	}
}

function getFremdEigenschaft($eS_key)
{
	if ($eS_key>0)
	{
		$cur_query = eS_execute_query("select products_options_id from eazysales_mvariation where kEigenschaft=".$eS_key);
		$prod = mysql_fetch_object($cur_query);
		return $prod->products_options_id;
	}
	return 0;
}

function getEigenschaftsArtikel($eS_key)
{
	if ($eS_key>0)
	{
		$cur_query = eS_execute_query("select kArtikel from eazysales_mvariation where kEigenschaft=".$eS_key);
		$prod = mysql_fetch_object($cur_query);
		return $prod->kArtikel;
	}
	return 0;
}

function getFremdEigenschaftsWert($eS_key)
{
	if ($eS_key>0)
	{
		$cur_query = eS_execute_query("select products_attributes_id from eazysales_mvariationswert where kEigenschaftsWert=".$eS_key);
		$prod = mysql_fetch_object($cur_query);
		return $prod->products_attributes_id;
	}
	return 0;
}

function getEsEigenschaftsWert($mein_key, $kArtikel)
{
	if ($mein_key>0 && $kArtikel>0)
	{
		$cur_query = eS_execute_query("select kEigenschaftsWert from eazysales_mvariationswert where kArtikel=$kArtikel and products_attributes_id=".$mein_key);
		$prod = mysql_fetch_object($cur_query);
		return $prod->kEigenschaftsWert;
	}
	return 0;
}

/**
 * real mysql escape mysql escape
 * @access public
 * @param string $ausdruck Ausdruck, der escaped fÃÂ¼r mysql werden soll
 * @return escaped expression
 */
function realEscape ($ausdruck)
{
	if (get_magic_quotes_gpc())
		return mysql_real_escape_string(stripslashes($ausdruck));
	else
		return mysql_real_escape_string($ausdruck);
}

function logExtra($entry)
{
	if (ES_ENABLE_LOGGING!=1)
		return "";
	$logfilename = "logs/".basename($_SERVER['REQUEST_URI'],".php").".log";
	$logfile = fopen($logfilename, 'a');
	fwrite($logfile,"\n[#######Extra Log##########] [".date('m.d.y H:i:s')."]\n".$entry);
	fclose($logfile);
}

function logge($return)
{
	if (ES_ENABLE_LOGGING!=1)
		return "";
	$logfilename = "logs/".basename($_SERVER['REQUEST_URI'],".php").".log";
	$logfile = fopen($logfilename, 'a');
	fwrite($logfile,"\n[".date('m.d.y H:i:s')."] - Ret: $return\n".Dump($_POST));
	fclose($logfile);
}

//get tax 4 product
function get_tax($products_tax_class_id, $tax_zone_id=0)
{
	if (!$tax_zone_id)
	{
		$tax_zone_id= $GLOBALS['einstellungen']->tax_zone_id;
	}
	if (!$products_tax_class_id || !$tax_zone_id)
		return 0;
	//get tax class
	$taxclass_query = eS_execute_query("select * from tax_rates where tax_class_id=".$products_tax_class_id." and tax_zone_id=".$tax_zone_id);
	$tax = mysql_fetch_object($taxclass_query);
	return ($tax->tax_rate);
}

function getSonstigeSprachen($auszuschliessendeLangId)
{
	$langIds = array();
	//hol alle Sprachen
	$cur_query = eS_execute_query("select languages_id from languages");
	while ($res = mysql_fetch_object($cur_query))
	{
		if ($auszuschliessendeLangId!=$res->languages_id)
			array_push($langIds,$res->languages_id);
	}
	return $langIds;
}

function setzeKundengruppenerlaubnis($customer_status_id_csv, $products_id)
{	
	if (!$products_id)
		return "";
		
	$cur_query = xtc_db_query("select configuration_value from configuration where configuration_key=\"GROUP_CHECK\"");
	$GROUP_CHECK = mysql_fetch_object($cur_query);
	if ($GROUP_CHECK->configuration_value=="false")
		return "";
	
		
	$customer_status_id_all_arr = array();
	$customer_status_id_arr;
	
	//nicht gesetzt -> setze fÃÂ¼r alle Kundengruppen als erlaubt
	$query = eS_execute_query("select distinct(customers_status_id) from customers_status");
	while ($cust_id_row = mysql_fetch_row($query))
	{
		if ($cust_id_row[0]>=0)
			array_push($customer_status_id_all_arr,$cust_id_row[0]);
	}
	if (!$customer_status_id_csv)
	{
		//fÃÂ¼r alle setzen
		$customer_status_id_arr = $customer_status_id_all_arr;
	}
	else 
	{
		$customer_status_id_arr = explode(",",$customer_status_id_csv);
		if (is_array($customer_status_id_arr) && count($customer_status_id_arr)>0)
		{
			for ($i=0;$i<count($customer_status_id_arr);$i++)
			{
				$customer_status_id_arr[$i]=intval($customer_status_id_arr[$i]);
			}
		}
	}
	if (is_array($customer_status_id_arr) && count($customer_status_id_arr)>0)
	{
		foreach ($customer_status_id_all_arr as $i => $customer_status_id)
		{
			if (in_array($customer_status_id,$customer_status_id_arr))
			{
				//setze status auf 1
				eS_execute_query("update products set group_permission_".$customer_status_id."=1 where products_id=".$products_id);
			}
			else 
			{
				//setze status auf 0
				eS_execute_query("update products set group_permission_".$customer_status_id."=0 where products_id=".$products_id);				
			}
		}
	}
}
?>