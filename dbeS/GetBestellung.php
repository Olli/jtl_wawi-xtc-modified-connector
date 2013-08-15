<?php
/**
 * jtlwawi_connector/dbeS/GetBestellung.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.03 / 11.10.06
*/

require_once("syncinclude.php");

$return=3;
if (auth())
{
	$return=0;	
	//hole alle neuen order	
	$cur_query = eS_execute_query("select orders.payment_method, orders.orders_id, orders.customers_id, orders.comments, date_format(orders.date_purchased, \"%d.%m.%Y\") as ErstelltDatumF from orders LEFT JOIN eazysales_sentorders ON orders.orders_id=eazysales_sentorders.orders_id where eazysales_sentorders.orders_id is NULL limit 1");
	if ($Bestellung = mysql_fetch_object($cur_query))
	{
		//falls kein kunde existiert, key muss irgendwo her!
		if (!$Bestellung->customers_id)
			$Bestellung->customers_id = 10000000-$Bestellung->orders_id;
		
		$VersandKey = 0;
		//tu Zahlungsweise in Comment:
		switch ($Bestellung->payment_method)
		{
			case 'banktransfer':
				$Bestellung->zahlungsweise = "Zahlungsweise: Lastschrift";
				$VersandKey = -1;
				break;
			case 'cc':
				$Bestellung->zahlungsweise = "Zahlungsweise: Kreditkarte";
				$VersandKey = -1;
				break;
			case 'cod':
				$Bestellung->zahlungsweise = "Zahlungsweise: Nachnahme";
				break;
			case 'invoice':
				$Bestellung->zahlungsweise = "Zahlungsweise: Auf Rechnung";
				break;
			case 'paypal': 
				$Bestellung->zahlungsweise = "Zahlungsweise: PayPal"; 
				break; 
			case 'moneyorder': 
				$Bestellung->zahlungsweise = "Zahlungsweise: Scheck/Vorkasse"; 
				break;
			case 'ipayment': 
				$Bestellung->zahlungsweise = "Zahlungsweise: Kreditkarte"; 
				break;
			case 'ipaymentelv': 
				$Bestellung->zahlungsweise = "Zahlungsweise: Lastschrift"; 
				break;
			default:
				$Bestellung->zahlungsweise = "Zahlungsweise: $Bestellung->payment_method";
				break;
		}		
		echo(CSVkonform($Bestellung->orders_id).';');
		echo(CSVkonform($Bestellung->orders_id).';');
		echo(CSVkonform($Bestellung->customers_id).';');
		echo(CSVkonform($Bestellung->orders_id).';');
		echo(CSVkonform($VersandKey).';');
		echo(';'); //VersandInfo
		echo(';'); //Versanddatum
		echo(';'); //Tracking Nr
		echo(CSVkonform($Bestellung->zahlungsweise).';');
		echo(';'); //Abgeholt
		echo(';'); //Status
		echo(CSVkonform($Bestellung->ErstelltDatumF).';'); 
		echo(CSVkonform($Bestellung->orders_id).';');
		echo(CSVkonform($Bestellung->comments).';');
		echo("\n");
	}
}
echo($return);
mysql_close();
logge($return);
?>