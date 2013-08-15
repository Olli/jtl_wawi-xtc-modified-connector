<?php
/**
 * jtlwawi_connector/index.php
 * AdminLogin fÃÂ¼r JTL-Wawi Connector
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.0 / 14.06.06
*/

require_once("admininclude.php");
require_once("adminTemplates.php");

$adminsession = new AdminSession();

//adminlogin
if (intval($_POST["adminlogin"])==1)
{
	$user_query = eS_execute_query("select * from customers where customers_email_address=\"".realEscape($_POST["benutzer"])."\" and customers_password=\"".md5($_POST["passwort"])."\"");
	$user = mysql_fetch_object($user_query);
	//hole DEFAULT_CUSTOMERS_STATUS_ID_ADMIN
	$cur_query = xtc_db_query("select configuration_value from configuration where configuration_key=\"DEFAULT_CUSTOMERS_STATUS_ID_ADMIN\"");
	$def_adminstatus = mysql_fetch_object($cur_query);
	if ($user->customers_id>0 && $def_adminstatus->configuration_value==0)
	{
		$_SESSION["loggedIn"] = 1;
	}
}

zeigeKopf();
zeigeLinks($_SESSION["loggedIn"]);
if ($_SESSION["loggedIn"]!=1)
	zeigeLogin();
else
	zeigeLoginBereich();
zeigeFuss();

?>