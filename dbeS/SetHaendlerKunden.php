<?php
/**
 * jtlwawi_connector/dbeS/SetHaendlerKunden.php
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
	if (intval($_POST["action"]) == 2 && intval($_POST['Key']))
	{
		$return = 0;
	}

	if (intval($_POST["action"]) == 4 && intval($_POST['Key']))
	{
		$return = 0;
	}
}

mysql_close();
echo($return);
//logge($return);
?>