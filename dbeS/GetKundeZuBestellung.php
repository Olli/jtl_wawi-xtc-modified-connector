<?php
/**
 * jtlwawi_connector/dbeS/GetKundeZuBestellung.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.03 / 12.10.06
*/

require_once("syncinclude.php");

$return=3;
if (auth())
{
	if (intval($_POST['KeyBestellung']))
	{
		$return=0;
		
		//hole einstellungen 
		$cur_query = eS_execute_query("select mappingHaendlerkunde from eazysales_einstellungen");
		$einstellungen = mysql_fetch_object($cur_query);
		$haendler_arr = explode(";",$einstellungen->mappingHaendlerkunde);
		
		//hole order		
		$cur_query = eS_execute_query("select * from orders where orders_id=".intval($_POST['KeyBestellung']));
		$Kunde = mysql_fetch_object($cur_query);

		//zusatzinfos vom kunden holen		
		$cur_query = eS_execute_query("select customers.customers_gender, customers.customers_newsletter, customers.customers_fax, customers.customers_vat_id, date_format(customers.customers_dob, \"%d.%m.%Y\") as geburtsdatum from orders, customers where orders.customers_id=customers.customers_id and customers.customers_id=".$Kunde->customers_id);
		$cust = mysql_fetch_object($cur_query);
		
		$Kunde->customers_gender = $cust->customers_gender;
		$Kunde->customers_newsletter = $cust->customers_newsletter;
		$Kunde->customers_fax = $cust->customers_fax;
		$Kunde->customers_vat_id = $cust->customers_vat_id;
		$Kunde->geburtsdatum = $cust->geburtsdatum;
		
		$Kunde->cAnrede="Frau";
		if ($Kunde->customers_gender=="m")
			$Kunde->cAnrede="Herr";
			
		$Kunde->cHaendler="N";
		if (in_array($Kunde->customers_status,$haendler_arr))
			$Kunde->cHaendler="Y";
			
		$Kunde->cNewsletter="N";
		if ($Kunde->customers_newsletter)
			$Kunde->cNewsletter="Y";
			
		if (!$Kunde->billing_firstname && !$Kunde->billing_lastname)
		{
			list($Kunde->billing_firstname, $Kunde->billing_lastname) = explode(" ", $Kunde->billing_name);
		}
		
		//falls kein kunde existiert, key muss irgendwo her!
		if (!$Kunde->customers_id)
			$Kunde->customers_id = 10000000-$Kunde->orders_id;
		
		echo(CSVkonform($Kunde->customers_id).';');
		echo(CSVkonform($Kunde->customers_id).';');
		echo(';');
		echo('"*****";');
		echo(CSVkonform($Kunde->cAnrede).';');
		echo(';'); //Titel
		echo(CSVkonform($Kunde->billing_firstname).';');
		echo(CSVkonform($Kunde->billing_lastname).';');
		echo(CSVkonform(substr($Kunde->billing_company,0,49)).';');
		echo(CSVkonform($Kunde->billing_street_address).';');
		echo(CSVkonform($Kunde->billing_postcode).';');
		echo(CSVkonform($Kunde->billing_city).';');
		echo(CSVkonform($Kunde->billing_country).';');
		echo(CSVkonform($Kunde->customers_telephone).';');
		echo(CSVkonform($Kunde->customers_fax).';');
		echo(CSVkonform($Kunde->customers_email_address).';');
		echo(CSVkonform($Kunde->cHaendler).';');
		echo(';'); //Rabatt
		echo(CSVkonform($Kunde->customers_vat_id).';');
		echo(CSVkonform($Kunde->cNewsletter).';');
		echo(CSVkonform($Kunde->geburtsdatum).';'); //Geburtstag
		echo(CSVkonform($Kunde->customers_suburb).';'); //adresszusatz
		echo(';'); //www
		echo("\n");
 	}
	else
		$return=5;
}

mysql_close();
echo($return);
logge($return);
?>