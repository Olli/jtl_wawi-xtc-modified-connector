<?php
/**
 * jtlwawi_connector/dbeS/setFirma.php
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
if (auth())
{
	if (intval($_POST["action"]) == 1)
	{
		$return = 0;
 	}
	else
		$return=5;
}

mysql_close();
echo($return);
//logge($return);
?>