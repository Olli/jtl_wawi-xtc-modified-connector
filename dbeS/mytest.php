<?php
/**
 * jtlwawi_connector/dbeS/mytest.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.0 / 16.06.06
*/

require_once("syncinclude.php");

$return=3;
$cName = $_POST["uID"];
$cPass = $_POST["uPWD"];

$_POST["uID"]="*";
$_POST["uPWD"]="*";

$cur_query = eS_execute_query("select * from eazysales_sync");
$loginDaten = mysql_fetch_object($cur_query);
if ($cName == $loginDaten->cName && $cPass == $loginDaten->cPass)
	$return=0;

mysql_close();
echo($return.";XTC");
//echo($return);
logge($return);
?>
