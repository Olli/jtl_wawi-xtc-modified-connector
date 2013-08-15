<?php
/**
 * jtlwawi_connector/dbeS/GetAdresse.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.03 / 13.10.06
*/
require_once("syncinclude.php");

$return=3;
if (auth())
{
	if (intval($_POST['KeyAdresse']))
	{
		//hole order
		$cur_query = eS_execute_query("select * from orders where orders_id=".intval($_POST['KeyAdresse']));
		$Order = mysql_fetch_object($cur_query);
		if (!$Order->delivery_firstname && !$Order->delivery_lastname)
		{
			list($Order->delivery_firstname, $Order->delivery_lastname) = explode(" ", $Order->delivery_name);
		}
		
		//falls kein kunde existiert, key muss irgendwo her!
		if (!$Order->customers_id)
			$Order->customers_id = 10000000-$Order->orders_id;

		
		echo(CSVkonform($Order->orders_id).';');
		echo(CSVkonform($Order->customers_id).';');
		echo(CSVkonform($Order->delivery_firstname).';');
		echo(CSVkonform($Order->delivery_lastname).';');
		echo(CSVkonform($Order->delivery_company).';');
		echo(CSVkonform($Order->delivery_street_address).';');
		echo(CSVkonform($Order->delivery_postcode).';');
		echo(CSVkonform($Order->delivery_city).';');
		echo(CSVkonform($Order->delivery_country).';');
		echo(CSVkonform($Order->customers_telephone).';');
		echo(';'); //keine Faxangaben
		echo(CSVkonform($Order->customers_email_address).';');
		echo(';'); //Type
		echo(';'); //anrede
		echo(CSVkonform($Order->delivery_suburb).';'); //adresszusatz
		echo("\n");
		
		$return=0;
 	}
	else
		$return=5;
}

mysql_close();
echo($return);
logge($return);
?>