<?php
/**
 * jtlwawi_connector/dbeS/getCountArtikel.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.0 / 15.06.06
*/
require_once("syncinclude.php");
$return=3;
$anzahlArt=0;
//Auth
if (auth())
{
	$return=0;	
	//hole anzahl zu versendender Artikel
	$cur_query = eS_execute_query("select count(*) from products LEFT JOIN eazysales_martikel ON products.products_id=eazysales_martikel.products_id where eazysales_martikel.products_id is NULL");
	if ($anzahl = mysql_fetch_row($cur_query))
	{
		if ($anzahl>0)
		{
			$anzahlArt = $anzahl[0];
		}
	}
}
mysql_close();
echo($return.";".$anzahlArt);
//logge($return);
?>
