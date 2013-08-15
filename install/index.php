<?php
/**
 * jtlwawi_connector/install/index.php
 * Datenbank installscript für JTL-Wawi Connector
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.04 / 26.10.06
*/

//hole pfad
require_once("../paths.php");

//get DB Connecion
// include server parameters
require_once (DOCROOT_PATH.'admin/includes/configure.php');
require_once (DIR_FS_INC . 'xtc_db_connect.inc.php');
require_once (DIR_FS_INC . 'xtc_db_query.inc.php');

xtc_db_connect() or die('Kann Datenbankverbindung nicht herstellen! Überprüfen Sie den DOCROOT_PATH im jtlwawi_connector/paths.php Script Zeile 15. Der Pfad muss entweder relativ oder absolut auf das Rootverzeichnis Ihres Shops zeigen (meist <i>xtcommerce</i>).');

$Con = 0;
//checke connection zur db
if ($_POST["DBhost"])
	$Con = pruefeConnection();

zeigeKopf();
if (schritt1EingabenVollstaendig())
	installiere();
else
	installSchritt1();

zeigeFuss();



function zeigeKopf()
{
	echo('
<html>
	<head>
		<meta http-equiv="content-type" content="text/html;charset=iso-8559-1">
		<meta http-equiv="language" content="deutsch, de">
		<meta name="author" content="JTL-Software, www.jtl-software.de">
		<title>JTL-Wawi Connector für XT-Commerce Installation</title>
		<link rel="stylesheet" type="text/css" href="../admin/jtlwawiConnectorAdmin.css">
	</head>
	<body>
	<center>
	<table cellspacing="0" cellpadding="0" width="770">
		<tr>
			<td><img src="../gfx/jtlwawi_Connector_head_XTC.jpg"></td>
		</tr>
		<tr>
			<td valign="top">
				<table cellspacing="0" cellpadding="0" width="100%">
					<tr>

	');
}

function zeigeFuss()
{
	echo('
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td bgcolor="#542A11" height="48" align="center"><span class="small" style="color:#ffffff">&copy; 2004-2006 JTL-Software</span></td>
		</tr>
	</table>
	<br>
	<a href="http://www.jtl-software.de/jtlwawi.php"><img src="../gfx/powered_by_jtlwawi.png"></a>
	</center>
	</body>
</html>
	');
}

function parse_mysql_dump($url) {
	$file_content = file($url);
	$errors="";
	//print_r($file_content);
	$query = "";
	foreach($file_content as $i => $sql_line) {
		$tsl = trim($sql_line);
		if (($sql_line != "") && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != "#")) {
			$query .= $sql_line;
			if(preg_match("/;\s*$/", $sql_line)) {
				$result = mysql_query($query);
				if (!$result) $errors.="<br>".mysql_error()." Nr: ".mysql_errno()." in Zeile ".$i."<br>".$query."<br>";
					$query = "";
			}
		}
	}
	return $errors;
}

function installSchritt1()
{
	//konfig
	$cur_query = xtc_db_query("select configuration_value from configuration where configuration_key=\"CURRENT_TEMPLATE\"");
	$cur_template = mysql_fetch_object($cur_query);
	
	//Templategeschichten
	$product_listing_template_arr = getTemplateArray($cur_template, "product_listing");
	$category_listing_template_arr = getTemplateArray($cur_template, "categorie_listing");
	$productinfo_template_arr = getTemplateArray($cur_template, "product_info");
	$productoptions_template_arr = getTemplateArray($cur_template, "product_options");
	
	$order_array=array(array('id' => 'p.products_price','text'=>'Artikelpreis'),
				array('id' => 'pd.products_name','text'=>'Artikelname'),
				array('id' => 'p.products_ordered','text'=>'Bestellte Artikel'),
				array('id' => 'p.products_sort','text'=>'Reihung'),
				array('id' => 'p.products_weight','text'=>'Gewicht'),
				array('id' => 'p.products_quantity','text'=>'Lagerbestand'));
				
	$order_array2=array(array('id' => 'ASC','text'=>'Aufsteigend'),
				array('id' => 'DESC','text'=>'Absteigend'));
				
	//Templatesachen für Produkte
	
				
	//defaultwerte setzen
	if (!$einstellungen->shopURL)
		$einstellungen->shopURL = HTTP_SERVER;
	if (!$einstellungen->tax_priority)
		$einstellungen->tax_priority = 1;
	if (!$einstellungen->versandMwst)
		$einstellungen->versandMwst = 19;
	if (!$einstellungen->tax_zone_id)
		$einstellungen->tax_zone_id = 5;
	if (!$einstellungen->languages_id)
	{
		$cur_query = xtc_db_query("select configuration_value from configuration where configuration_key=\"DEFAULT_LANGUAGE\"");
		$def_lang = mysql_fetch_object($cur_query);
		if ($def_lang->configuration_value)
		{
			$cur_query = xtc_db_query("select languages_id from languages where code=\"".$def_lang->configuration_value."\"");
			$langID = mysql_fetch_object($cur_query);			
			$einstellungen->languages_id = $langID->languages_id;
		}
		else 
		{
			//erstbeste Lang
			$cur_query = xtc_db_query("select languages_id from languages");
			$langID = mysql_fetch_object($cur_query);			
			$einstellungen->languages_id = $langID->languages_id;
		}
	}
	if (!$einstellungen->mappingEndkunde)
	{
		$cur_query = xtc_db_query("select configuration_value from configuration where configuration_key=\"DEFAULT_CUSTOMERS_STATUS_ID\"");
		$def_userstatus = mysql_fetch_object($cur_query);
		$einstellungen->mappingEndkunde=$def_userstatus->configuration_value;
		$cur_query = xtc_db_query("select configuration_value from configuration where configuration_key=\"DEFAULT_CUSTOMERS_STATUS_ID_GUEST\"");
		$def_userstatus_guest = mysql_fetch_object($cur_query);		
		$einstellungen->mappingEndkunde.=";".$def_userstatus_guest->configuration_value;
	}
	$mappingEndkunde_arr = explode (";",$einstellungen->mappingEndkunde);
	$mappingHaendlerkunde_arr = explode (";",$einstellungen->mappingHaendlerkunde);
	//ende konfig
	
	$hinweis="";
	if ($_POST["installiereSchritt1"]==1)
		$hinweis="Bitte alle Felder vollständig ausfüllen!";
	srand();
	$syncuser = generatePW(8);
	sleep(1);
	$syncpass = generatePW(8);
	echo('
						<td bgcolor="#ffffff" style="border-color:#222222; border-width:1px; border-style:solid; border-top-width:0px; border-bottom-width:0px;" valign="top" align="center"><br>
							<table cellspacing="0" cellpadding="0" width="96%">
								<tr><td class="content_header" align="center"><h3>JTL-Wawi Connector Installation</h3></td></tr>
								<tr><td class="content" ><br>
										Dieses Modul erlaubt es, Ihren xt:Commerce Shop mit der kostenlosen Warenwirtschaft <a href="http://www.jtl-software.de/jtlwawi.php">JTL-Wawi</a> zu betreiben. Dieses Modul ist kostenfrei, kann frei weitergegeben werden, Urheber ist <a href="http://www.jtl-software.de">JTL-Software</a>.<br><br>
										Den Funktionsumfang dieses Modul finden Sie unter <a href="http://www.jtl-software.de/jtlwawi_connector.php">http://www.jtl-software.de/jtlwawi_connector.php</a>.<br><br>
										Die Installation und Inbetriebnahme von JTL-Wawi Connector geschieht auf eigenes Risiko. Haftungsansprüche für evtl. entstandene Schäden werden nicht übernommen! <b>Sichern Sie sich daher vorher sowohl Ihre Shopdatenbank als auch die JTL-Wawi Datenbank.</b><br><br>

										<center>
										Für den reibungslosen Im-/ und Export von Daten zwischen <a href="http://www.jtl-software.de/jtlwawi.php">JTL-Wawi</a> und Ihrem Shop, müssen einige Einstellungen als Standard gesetzt sein.<br><br>
										<table cellspacing="0" cellpadding="0" width="580">
											<tr>
												<td class="unter_content_header">&nbsp;<b>Einstellungen</b></td>
											</tr>
											<tr>
												<td class="content" align="center">
													Hilfe zu den einzelnen Einstellungmöglichkeiten finden Sie unter <a href="http://www.jtl-software.de/jtlwawi_connector.php" target="_blank">JTL-Wawi Connector Konfigurationshilfe</a>.<br>
													<form action="index.php" method="post" name="konfig">
													<input type="hidden" name="install" value="1">
													<table cellspacing="0" cellpadding="10" width="100%">
														<tr>
															<td><b>Shop URL</b></td><td><input type="text" name="shopurl" size="50" class="konfig" value="'.$einstellungen->shopURL.'"></td>
														</tr>
														<tr>
															<td><b>Standardwährung</b></td><td><select name="waehrung">
	');
	$cur_query = xtc_db_query("select * from currencies");
	while ($currency = mysql_fetch_object($cur_query))
	{
		echo('<option value="'.$currency->currencies_id.'" ');if ($currency->currencies_id==$einstellungen->currencies_id) echo('selected'); echo('>'.$currency->title.'</option>');
	}
	echo('</select></td>
														</tr>
														<tr>
															<td><b>Standardsprache</b></td><td><select name="sprache">
	');
	$cur_query = xtc_db_query("select * from languages");
	while ($lang = mysql_fetch_object($cur_query))
	{
		echo('<option value="'.$lang->languages_id.'" ');if ($lang->languages_id==$einstellungen->languages_id) echo('selected'); echo('>'.$lang->name.'</option>');
	}
	echo('
															</select>														
															</td>
														</tr>
														<tr>
															<td><b>Standardliefertermin</b></td><td><select name="liefertermin">
	');
	$cur_query = xtc_db_query("select * from shipping_status where language_id=".$einstellungen->languages_id);
	while ($liefer = mysql_fetch_object($cur_query))
	{
		echo('<option value="'.$liefer->shipping_status_id.'" ');if ($liefer->shipping_status_id==$einstellungen->shipping_status_id) echo('selected'); echo('>'.$liefer->shipping_status_name.'</option>');
	}
	echo('
															</select>														
															</td>
														</tr>
														<tr>
															<td>Umsatzsteuer</td><td>&nbsp;</td>
														</tr>
														<tr>
															<td><b>Standard Steuerzone</b></td><td><select name="steuerzone">
	');
	$cur_query = xtc_db_query("select * from geo_zones");
	while ($zone = mysql_fetch_object($cur_query))
	{
		echo('<option value="'.$zone->geo_zone_id.'" ');if ($zone->geo_zone_id==$einstellungen->tax_zone_id) echo('selected'); echo('>'.$zone->geo_zone_name.'</option>');
	}
	echo('
															</select>														
															</td>
														</tr>
														<tr>
															<td><b>Standard Steuerklasse*</b></td><td><select name="steuerklasse">
	');
	$cur_query = xtc_db_query("select * from tax_class");
	while ($klasse = mysql_fetch_object($cur_query))
	{
		echo('<option value="'.$klasse->tax_class_id.'" ');if ($klasse->tax_class_id==$einstellungen->tax_class_id) echo('selected'); echo('>'.$klasse->tax_class_title.'</option>');
	}
	echo('
															</select>														
															</td>
														</tr>
														<tr>
															<td><b>Standard Steuersatzpriorität</b></td><td><input type="text" name="prioritaet" size="50" class="konfig" style="width:30px;" value="'.$einstellungen->tax_priority.'"></td>
														</tr>
														<tr>
															<td><b>Steuersatz für Versandkosten</b></td><td><input type="text" name="versandMwst" size="50" class="konfig" style="width:30px;" value="'.$einstellungen->versandMwst.'"> %</td>
														</tr>
														<tr>
															<td>Bestellstatusänderungen</td><td>&nbsp;</td>
														</tr>
														<tr>
															<td><b>Sobald Bestellung erfolgreich in JTL-Wawi übernommen wird, Status setzen auf:</b></td><td><select name="StatusAbgeholt"><option value="0">Status nicht ändern</option>
	');
	$cur_query = xtc_db_query("select * from orders_status where language_id=".$einstellungen->languages_id." order by orders_status_id");
	while ($status = mysql_fetch_object($cur_query))
	{
		echo('<option value="'.$status->orders_status_id.'" ');if ($status->orders_status_id==$einstellungen->StatusAbgeholt) echo('selected'); echo('>'.$status->orders_status_name.'</option>');
	}
	echo('
															</select>														
															</td>
														</tr>
														<tr>
															<td><b>Sobald Bestellung in JTL-Wawi versandt wird, Status setzen auf</b></td><td><select name="StatusVersendet"><option value="0">Status nicht ändern</option>
	');
	$cur_query = xtc_db_query("select * from orders_status where language_id=".$einstellungen->languages_id." order by orders_status_id");
	while ($status = mysql_fetch_object($cur_query))
	{
		echo('<option value="'.$status->orders_status_id.'" ');if ($status->orders_status_id==$einstellungen->StatusVersendet) echo('selected'); echo('>'.$status->orders_status_name.'</option>');
	}
	echo('
															</select>														
															</td>
														</tr>
													</table><br>
													JTL-Wawi kennt z.Zt. nur die Kundengruppen Endkunde und Händlerkunde. Hier können Sie Kundengruppen auf Ihren Shop zuweisen, welche die Händlerpreise zugewiesen bekommen sollen. Alle anderen Kundengruppen erhalten die Endkundenpreise aus JTL-Wawi.<br>
													<table cellspacing="0" cellpadding="10" width="100%">
														<tr>
															<td valign="top"><b>JTL-Wawi Händlerkunde</b></td><td>
	');
	$cur_query = xtc_db_query("select * from customers_status where language_id=".$einstellungen->languages_id." order by customers_status_id");
	while ($grp = mysql_fetch_object($cur_query))
	{
		echo('<input type="checkbox" name="haendlerkunde[]" value="'.$grp->customers_status_id.'"');if (in_array($grp->customers_status_id,$mappingHaendlerkunde_arr)) echo('checked'); echo('> '.$grp->customers_status_name.'<br>');
	}
															
	echo('
															</td>
														</tr>
													</table><br>
													Vorlagen für Kategorien und Artikel, die über JTL-Wawi eingestellt werden:
													<table cellspacing="0" cellpadding="10" width="100%">
														<tr>
															<td>Kategorien</td><td>&nbsp;</td>
														</tr>
														<tr>
															<td valign="top"><b>Artikelübersicht in Kategorien</b></td><td><select name="cat_listing">
	');
	if (is_array($product_listing_template_arr))
	{	
		foreach ($product_listing_template_arr as $template)
		{
			echo('<option value="'.$template['id'].'" ');if ($template['id']==$einstellungen->cat_listing_template) echo('selected'); echo('>'.$template['text'].'</option>');
		}
	}
	echo('
															</select>	
															</td>
														</tr>
														<tr>
															<td valign="top"><b>Kategorieübersicht</b></td><td><select name="cat_template">
	');
	if (is_array($category_listing_template_arr))
	{	
		foreach ($category_listing_template_arr as $template)
		{
			echo('<option value="'.$template['id'].'" ');if ($template['id']==$einstellungen->cat_category_template) echo('selected'); echo('>'.$template['text'].'</option>');
		}
	}
	echo('
															</select>	
															</td>
														</tr>
														<tr>
															<td valign="top"><b>Artikelsortierung</b></td><td><select name="cat_sorting">
	');
	if (is_array($order_array))
	{	
		foreach ($order_array as $sortierung)
		{
			echo('<option value="'.$sortierung['id'].'" ');if ($sortierung['id']==$einstellungen->cat_sorting) echo('selected'); echo('>'.$sortierung['text'].'</option>');
		}
	}
	echo('
															</select> <select name="cat_sorting2">
	');
	if (is_array($order_array2))
	{	
		foreach ($order_array2 as $sortierung)
		{
			echo('<option value="'.$sortierung['id'].'" ');if ($sortierung['id']==$einstellungen->cat_sorting2) echo('selected'); echo('>'.$sortierung['text'].'</option>');
		}
	}
	echo('
															</select>
															</td>
														</tr>
														<tr>
															<td>Artikel</td><td>&nbsp;</td>
														</tr>
														<tr>
															<td valign="top"><b>Artikeldetails</b></td><td><select name="product_template">
	');
	if (is_array($productinfo_template_arr))
	{	
		foreach ($productinfo_template_arr as $template)
		{
			echo('<option value="'.$template['id'].'" ');if ($template['id']==$einstellungen->prod_product_template) echo('selected'); echo('>'.$template['text'].'</option>');
		}
	}
	echo('
															</select>	
															</td>
														</tr>
														<tr>
															<td valign="top"><b>Artikeloptionen</b></td><td><select name="option_template">
	');
	if (is_array($productoptions_template_arr))
	{	
		foreach ($productoptions_template_arr as $template)
		{
			echo('<option value="'.$template['id'].'" ');if ($template['id']==$einstellungen->prod_options_template) echo('selected'); echo('>'.$template['text'].'</option>');
		}
	}
	echo('
															</select>	
															</td>
														</tr>
														<tr>
														<tr>
															<td valign="top"><b>Bestellungen <=> JTL-Wawi</b></td><td>
															Folgende Bestellungen und dazugehörige Kundendaten werden hiermit als "bereits zu JTL-Wawi versandt" markiert und bei einem Webshopabgleich nicht nach JTL-Wawi geholt. Möchten Sie auch alle Bestellungen und zugehörige Kundendaten in JTL-Wawi importieren, so kreuzen Sie nichts an:<br><br>
		');
	$cur_query = xtc_db_query("select * from orders_status where language_id=".$einstellungen->languages_id." order by orders_status_id");
	while ($status = mysql_fetch_object($cur_query))
	{
		echo('<input type="checkbox" name="bestellungen_bestellt[]" value="'.$status->orders_status_id.'">'.$status->orders_status_name);
	}
	echo('
															</td>
														</tr>
														</tr>
													</table><br>
												</td>
											</tr>
										</table><br>
										<table cellspacing="0" cellpadding="0" width="580">
											<tr>
												<td class="unter_content_header">&nbsp;<b>Synchronsations - Benutzerdaten</b></td>
											</tr>
											<tr>
												<td class="content">													
													Für die Synchronisation zwischen JTL-Wawi und diesem wird ein Synchronisationsbenutzer benötigt. Bitte <b>notieren Sie sich</b> unbedingt <b>diese Angaben</b> und setzen sie einen starken kryptischen Benutzernamen und Passwort - oder übernehmen Sie die zufällig generierten Vorgaben. Diese Angaben werden einmalig in den JTL-Wawi Einstellungen eingetragen.
													<br><br><br>
													<center>
													<table cellspacing="0" cellpadding="10" width="70%" style="border-width:1px;border-color:#222222;border-style:solid;">
													<tr>
														<td><b>Sync-Benutzername</b></td><td><input type="text" name="syncuser" size="20" class="login" value="'.$syncuser.'"></td>
													</tr>
													<tr>
														<td><b>Sync-Passwort</b></td><td><input type="text" name="syncpass" size="20" class="login" value="'.$syncpass.'"></td>
													</tr>
													</table>
													<br><br>
													'.$hinweis.'
													<input type="submit" value="Installation starten">
													</form>
													</center>
												</td>
											</tr>
										</table>
								</td></tr>
							</table><br>
						</td>
	');
}

function schritt1EingabenVollstaendig()
{
	if (strlen($_POST["syncuser"])>0 && strlen($_POST["syncpass"])>0)
		return 1;
	return 0;
}

function installiere()
{
	$hinweis = parse_mysql_dump("jtlwawi_connector_DB.sql");
	//inserte syncuser
	if (!mysql_query("insert into eazysales_sync values (\"".$_POST['syncuser']."\",\"".$_POST['syncpass']."\")")) $hinweis.="<br>".mysql_error()." Nr: ".mysql_errno();
	
	//Bestellungen gesendet markeiren
	$qry_teil="";
	if (is_array($_POST['bestellungen_bestellt']))
	{
		foreach ($_POST['bestellungen_bestellt'] as $i => $status)
		{
			if ($i!=0)
				$qry_teil.=" or orders_status=".$status;
			else
				$qry_teil.=" orders_status=".$status;
		}
	}
	if (strlen($qry_teil)>1)
	{
		$best_query = eS_execute_query("select orders_id from orders where $qry_teil order by orders_id");
		while ($orderkey = mysql_fetch_row($best_query))
		{
			if ($orderkey[0]>0)
			{
				//schaue, ob nicht schon markiert
				$einzel_query = eS_execute_query("select orders_id from eazysales_sentorders where orders_id=".$orderkey[0]);
				$oid = mysql_fetch_row($einzel_query);
				if (!$oid[0])
					eS_execute_query("insert into eazysales_sentorders (orders_id) values (".$orderkey[0].")");
			}
		}
	}
		
	//inserte einstellungen
	$mappingEndkunde="";
	$mappingHaendlerkunde="";
//	if (is_array($_POST['endkunde']))
//		$mappingEndkunde = implode(";",$_POST['endkunde']);
	if (is_array($_POST['haendlerkunde']))
		$mappingHaendlerkunde = implode(";",$_POST['haendlerkunde']);
	
	$shopurl = $_POST['shopurl']; if (!$shopurl) $shopurl="";
	$waehrung = $_POST['waehrung']; if (!$waehrung) $waehrung=0;
	$sprache = $_POST['sprache']; if (!$sprache) $sprache=0;
	$liefertermin = $_POST['liefertermin']; if (!$liefertermin) $liefertermin=0;
	$steuerzone = $_POST['steuerzone']; if (!$steuerzone) $steuerzone=0;
	$steuerklasse = $_POST['steuerklasse']; if (!$steuerklasse) $steuerklasse=0;
	$prioritaet = $_POST['prioritaet']; if (!$prioritaet) $prioritaet=0;
	$versandMwst = floatval($_POST['versandMwst']); if (!$versandMwst) $versandMwst=0;
	$cat_listing = $_POST['cat_listing']; if (!$cat_listing) $cat_listing="";
	$cat_template = $_POST['cat_template']; if (!$cat_template) $cat_template="";
	$cat_sorting = $_POST['cat_sorting']; if (!$cat_sorting) $cat_sorting="";
	$cat_sorting2 = $_POST['cat_sorting2']; if (!$cat_sorting2) $cat_sorting2="";
	$product_template = $_POST['product_template']; if (!$product_template) $product_template="";
	$option_template = $_POST['option_template']; if (!$option_template) $option_template="";
	$statusAbgeholt = $_POST['StatusAbgeholt']; if (!$statusAbgeholt) $statusAbgeholt=0;
	$statusVersandt = $_POST['StatusVersendet']; if (!$statusVersandt) $statusVersandt=0;
	

	eS_execute_query("delete from eazysales_einstellungen");
	eS_execute_query("insert into eazysales_einstellungen (StatusAbgeholt, StatusVersendet, currencies_id, languages_id, mappingEndkunde, mappingHaendlerkunde, shopURL, tax_class_id, tax_zone_id, tax_priority, shipping_status_id, versandMwst,cat_listing_template,cat_category_template,cat_sorting,cat_sorting2,prod_product_template,prod_options_template) values ($statusAbgeholt, $statusVersandt, $waehrung,$sprache,\"$mappingEndkunde\",\"$mappingHaendlerkunde\",\"$shopurl\",$steuerklasse,$steuerzone,$prioritaet,$liefertermin, ".floatval($versandMwst).",\"$cat_listing\",\"$cat_template\",\"$cat_sorting\",\"$cat_sorting2\",\"$product_template\",\"$option_template\")");
	//ende einstellungen

	if (strlen($hinweis)>0)
	{
		echo('
							<td bgcolor="#ffffff" style="border-color:#222222; border-width:1px; border-style:solid; border-top-width:0px; border-bottom-width:0px;" valign="top" align="center"><br>
								<table cellspacing="0" cellpadding="0" width="96%">
									<tr><td class="content_header" align="center"><h3>JTL-Wawi Connector Datenbankeinrichtung fehlgeschlagen</h3></td></tr>
									<tr><td class="content" align="center"><br>
											<table cellspacing="0" cellpadding="0" width="580">
												<tr>
													<td class="unter_content_header">&nbsp;<b>Bei der Datenbankeinrichtung sind folgende Fehler aufgetreten</b></td>
												</tr>
												<tr>
													<td class="content">
	'.$hinweis.'<br><br><br>Lösungen sollten Sie hier finden: <a href="http://www.jtl-software.de/jtlwawi_connector.php">JTL-Wawi Connector</a>
													</td>
												</tr>
											</table>
									</td></tr>
								</table><br>
							</td>
		');
	}
	else
	{
		//hole webserver
		$url= "http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
		echo('
							<td bgcolor="#ffffff" style="border-color:#222222; border-width:1px; border-style:solid; border-top-width:0px; border-bottom-width:0px;" valign="top" align="center"><br>
								<table cellspacing="0" cellpadding="0" width="96%">
									<tr><td class="content_header" align="center"><h3>JTL-Wawi Connector Installation abgeschlossen</h3></td></tr>
									<tr><td class="content" align="center"><br>
											<table cellspacing="0" cellpadding="0" width="580">
												<tr>
													<td class="unter_content_header">&nbsp;<b>Die Datenbank für JTL-Wawi Connector wurde aufgesetzt</b></td>
												</tr>
												<tr>
													<td class="content">
														Die Installation ist serverseitig soweit abgeschlossen.<br><br>
														Sie müssen nun JTL-Wawi im Menü Einstellungen -> Shop-Einstellungen konfigurieren.<br><br>
														Folgende Einstellungen müssen Sie in JTL-Wawi eintragen:<br><br>
														<table width="95%">
														<tr><td><b>API-KEY</b>: </td><td>JTL-Wawi Connector</td></tr>
														<tr><td><b>Web-Server</b>: </td><td>'.substr($url,0,strlen($url)-18).'</td></tr>
														<tr><td><b>Web-Benutzer</b>: </td><td>'.$_POST['syncuser'].'</td></tr>
														<tr><td><b>Passwort</b>: </td><td>'.$_POST['syncpass'].'</td></tr>
														</table><br><br>
														Setzen Sie einen Haken bei "Bilder per HTTP versenden".<br>
														Bei den FTP-Einstellungen müssen Sie nichts eintragen.<br>
														Wir wünschen Ihnen viel Erfolg mit Ihrem Shop!
													</td>
												</tr>
											</table>
									</td></tr>
								</table><br>
							</td>
		');
	}
}

function generatePW($length=8)
{
	$dummy= array_merge(range('0', '9'), range('a', 'z'), range('A', 'Z'));
	mt_srand((double)microtime()*1000000);
	for ($i = 1; $i <= (count($dummy)*2); $i++)
	{
		$swap= mt_rand(0,count($dummy)-1);
		$tmp= $dummy[$swap];
		$dummy[$swap]= $dummy[0];
		$dummy[0]= $tmp;
	}
	return substr(implode('',$dummy),0,$length);
}

function getTemplateArray($cur_template, $module)
{
	$files=array();
	if ($dir= opendir(DIR_FS_CATALOG.'templates/'.$cur_template->configuration_value.'/module/'.$module.'/'))
	{
		while  (($file = readdir($dir)) !==false) 
		{
			if (is_file( DIR_FS_CATALOG.'templates/'.$cur_template->configuration_value.'/module/'.$module.'/'.$file) and ($file !="index.html"))
			{
				$files[]=array('id' => $file,'text' => $file);
			}
		}
		closedir($dir);
	}	
	return $files;
}


function eS_execute_query($query)
{	
	return mysql_query($query);
}
?>