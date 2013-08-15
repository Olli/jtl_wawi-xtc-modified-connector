<?php
/**
 * jtlwawi_connector/dbeS/SetBestellung.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.02 / 05.02.07
*/
define ('BESTELLUNG_VERSANDT_EMAIL_SCHICKEN',0);
define ('DHL_LINK_IN_MAIL_EINBAUEN',0); 
define ('DPD_LINK_IN_MAIL_EINBAUEN',0);
define ('GLS_LINK_IN_MAIL_EINBAUEN',1); 
require_once("syncinclude.php");
$return=3;
if (auth())
{
	$return=5;
	//Bestellung versandt
	if (intval($_POST["action"]) == 6 && intval($_POST['KeyBestellung']))
	{
		$return = 0;
		//setze orders_status auf gewÃÂ¤hlte Option bei eS Versadnt
		//hole einstellungen
		$cur_query = eS_execute_query("select StatusVersendet from eazysales_einstellungen");
		$einstellungen = mysql_fetch_object($cur_query);
		
		//setze status der Bestellung
		if ($einstellungen->StatusVersendet>0 && $_POST["VersandDatum"])
		{
			eS_execute_query("update orders set orders_status=".$einstellungen->StatusVersendet." where orders_id=".intval($_POST['KeyBestellung']));
			//fÃÂ¼ge history hinzu
			$VersandInfo = $_POST["VersandInfo"];
			$VersandDatum = realEscape($_POST["VersandDatum"]);
			$Tracking = realEscape($_POST["Tracking"]);
			
			//PLZ der Lieferadresse holen 
			$liefer_query = eS_execute_query("select delivery_postcode from orders where orders_id=".intval($_POST['KeyBestellung'])); 
			$Order = mysql_fetch_object($liefer_query); 

			//Sendungsverfolgungslinks
			$DHL_Link = "http://nolp.dhl.de/nextt-online-public/set_identcodes.do?lang=de&zip=".$Order->delivery_postcode."&idc=".$Tracking; 
			$DPD_Link = "http://extranet.dpd.de/cgi-bin/delistrack?typ=1&lang=de&pknr=$Tracking&submit="; 
			$GLS_Link = "http://www.gls-germany.com/online/paketstatus.php3?mode=&hasdata=1&datatype=paketnr&paketnr=".$Tracking."&filter=all&search_x=10&search_y=10"; 
			
			//Plaintext Kommentar bauen
			$kommentar_txt = "\nIhre Bestellung wurde am $VersandDatum versandt.\n".$VersandInfo."\nIdentCode".$Tracking;
			//HTML Kommentar bauen
			$kommentar_html = "<br>Ihre Bestellung wurde am $VersandDatum versandt.<br>".$VersandInfo."<br>IdentCode".$Tracking;		
			
			if (DHL_LINK_IN_MAIL_EINBAUEN==1) 
			{ 
				$kommentar_txt."\nLink zur Sendeverfolgung: ".$DHL_Link; 
				$kommentar_html.='<br>Link zur Sendeverfolgung: <a href="'.$DHL_Link.'">'.$DHL_Link.'</a>'; 
			} 
			elseif (DPD_LINK_IN_MAIL_EINBAUEN==1) 
			{ 
				$kommentar_txt."\nLink zur Sendeverfolgung: ".$DPD_Link; 
				$kommentar_html.='<br>Link zur Sendeverfolgung: <a href="'.$DPD_Link.'">'.$DPD_Link.'</a>'; 
			} 
			elseif (GLS_LINK_IN_MAIL_EINBAUEN==1) 
			{
				$kommentar_txt."\nLink zur Sendeverfolgung: ".$GLS_Link; 
				$kommentar_html.='<br>Link zur Sendeverfolgung: <a href="'.$GLS_Link.'">'.$GLS_Link.'</a>'; 				
			}
	
			if (BESTELLUNG_VERSANDT_EMAIL_SCHICKEN==1) 
			{ 
				eS_execute_query("insert into orders_status_history (orders_id, orders_status_id, date_added, comments, customer_notified) values(".intval($_POST['KeyBestellung']).", ".$einstellungen->StatusVersendet.", now(), \"".$kommentar_txt."\", 1)"); 
				//mail aus XTC rausschicken 
				sende_xtc_mail(intval($_POST['KeyBestellung']),$kommentar_txt,$kommentar_html); 
			} 
			else 
			{ 
				eS_execute_query("insert into orders_status_history (orders_id, orders_status_id, date_added, comments) values(".intval($_POST['KeyBestellung']).", ".$einstellungen->StatusVersendet.", now(), \"".$kommentar_txt."\")"); 
			}			
		}
 	}

	//Bestellung erfolgreich abgeholt
	if (intval($_POST["action"]) == 5 && intval($_POST['KeyBestellung']))
	{
		$return = 0;
		//setze orders_status auf gewÃÂ¤hlte Option bei eS Abholung
		//hole einstellungen
		$cur_query = eS_execute_query("select StatusAbgeholt from eazysales_einstellungen");
		$einstellungen = mysql_fetch_object($cur_query);
		
		//setze status der Bestellung
		if ($einstellungen->StatusAbgeholt>0)
		{
			eS_execute_query("update orders set orders_status=".$einstellungen->StatusAbgeholt." where orders_id=".intval($_POST['KeyBestellung']));
			//fÃÂ¼ge history hinzu
			$kommentar = "Erfolgreich in JTL-Wawi ÃÂ¼bernommen";
			eS_execute_query("insert into orders_status_history (orders_id, orders_status_id, date_added, comments) values(".intval($_POST['KeyBestellung']).", ".$einstellungen->StatusAbgeholt.", now(), \"".$kommentar."\")");
		}
		
		//setze bestellung auf abgeholt
		eS_execute_query("insert into eazysales_sentorders (orders_id, dGesendet) values (".intval($_POST['KeyBestellung']).",now())");
	}
}

function sende_xtc_mail($KeyBestellung, $kommentar_txt, $kommentar_html)
{
	define('_VALID_XTC',true);
	define('FILENAME_CATALOG_ACCOUNT_HISTORY_INFO', 'account_history_info.php');
	define('DATE_FORMAT_LONG', '%A, %d. %B %Y');
	define('TABLE_LANGUAGES', 'languages');
	
	require_once (DIR_FS_CATALOG.DIR_WS_CLASSES . 'Smarty_2.6.10/Smarty.class.php');
	require_once (DIR_FS_CATALOG.DIR_WS_CLASSES.'class.phpmailer.php');
	require_once (DIR_FS_ADMIN.DIR_WS_FUNCTIONS . 'html_output.php');	
	require_once (DIR_FS_ADMIN.DIR_WS_FUNCTIONS . 'general.php');		
	require_once (DIR_FS_INC.'xtc_php_mail.inc.php');
	require_once (DIR_FS_INC . 'xtc_db_fetch_array.inc.php');
	
	//hole einstellungen
	$cur_query = eS_execute_query("select languages_id from eazysales_einstellungen");
	$einstellungen = mysql_fetch_object($cur_query);
	
	// set application wide parameters
	$configuration_query = eS_execute_query('select configuration_key as cfgKey, configuration_value as cfgValue from configuration');
	while ($configuration = mysql_fetch_array($configuration_query)) 
	{
		define($configuration['cfgKey'], $configuration['cfgValue']);
	}

	$smarty = new Smarty;
	
	$check_status_query = eS_execute_query("select language, customers_name, customers_email_address, orders_status, date_purchased from orders where orders_id = '".$KeyBestellung."'");
	$check_status = mysql_fetch_array($check_status_query);
	
	$cur_query = xtc_db_query("select orders_status_name from orders_status where language_id=".$einstellungen->languages_id." and orders_status_id=".$check_status['orders_status']);
	$status = mysql_fetch_object($cur_query);
	
	// assign language to template for caching
	$smarty->assign('language', $_SESSION['language']);
	$smarty->caching = false;

	// set dirs manual
	$smarty->template_dir = DIR_FS_CATALOG.'templates';
	$smarty->compile_dir = DIR_FS_CATALOG.'templates_c';
	$smarty->config_dir = DIR_FS_CATALOG.'lang';

	$smarty->assign('tpl_path', 'templates/'.CURRENT_TEMPLATE.'/');
	$smarty->assign('logo_path', HTTP_SERVER.DIR_WS_CATALOG.'templates/'.CURRENT_TEMPLATE.'/img/');

	$smarty->assign('NAME', $check_status['customers_name']);
	$smarty->assign('ORDER_NR', $KeyBestellung);
	$smarty->assign('ORDER_LINK', xtc_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id='.$KeyBestellung, 'SSL'));
	$smarty->assign('ORDER_DATE', xtc_date_long($check_status['date_purchased']));
	$smarty->assign('NOTIFY_COMMENTS', $kommentar_txt);
	$smarty->assign('ORDER_STATUS', $status->orders_status_name);

	$txt_mail = $smarty->fetch(CURRENT_TEMPLATE.'/admin/mail/'.$check_status['language'].'/change_order_mail.txt');
	$smarty->assign('NOTIFY_COMMENTS', nl2br($kommentar_html));
	$html_mail = $smarty->fetch(CURRENT_TEMPLATE.'/admin/mail/'.$check_status['language'].'/change_order_mail.html');

	xtc_php_mail(EMAIL_BILLING_ADDRESS, EMAIL_BILLING_NAME, $check_status['customers_email_address'], $check_status['customers_name'], '', EMAIL_BILLING_REPLY_ADDRESS, EMAIL_BILLING_REPLY_ADDRESS_NAME, '', '', EMAIL_BILLING_SUBJECT, $html_mail, $txt_mail);
}

mysql_close();
echo($return);
logge($return);
?>