<?php
/**
 * jtlwawi_connector/dbeS/setArtikel.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.01 / 11.07.06
*/
require_once("syncinclude.php");
//Auth
$return=3;
if (auth())
{
	$return=5;
	//data da?
	if ($_POST['data'])
	{
		
		$zeilen = explode("\n",$_POST['data']);
		if (is_array($zeilen))
		{
			$return=0;
			foreach ($zeilen as $zeile)
			{
				$werte = explode(";",$zeile);
				switch ($werte[0])
				{
					case 'P':
						setMappingArtikel($werte[1],$werte[2]);
						break;
					case 'K':
						setMappingKategorie($werte[1],$werte[2]);
						break;
					case 'W':
						setMappingEigenschaftsWert($werte[1],$werte[2],$werte[3]);
						break;
				}
			}
		}
	}
}
mysql_close();
echo($return);
logge($return);
?>