<?php
/**
 * jtlwawi_connector/dbeS/Variation.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.01 / 16.09.06
*/

require_once("syncinclude.php");

$return=3;
if (auth())
{
	if (intval($_POST["action"]) == 1 && intval($_POST['KeyEigenschaft']))
	{		
		$Eigenschaft = new stdClass();
		$Eigenschaft->kEigenschaft = intval($_POST["KeyEigenschaft"]);
		$Eigenschaft->kArtikel = intval($_POST["KeyArtikel"]);
		$Eigenschaft->cName = realEscape($_POST["Name"]);
		$Eigenschaft->nSort = intval($_POST["Sort"]);

		//hole products_id
		$products_id = getFremdArtikel($Eigenschaft->kArtikel);
		if ($products_id>0)
		{
			//hole einstellungen
			$cur_query = eS_execute_query("select languages_id from eazysales_einstellungen");
			$einstellungen = mysql_fetch_object($cur_query);
			
			//hol products_options_id
			$cur_query = eS_execute_query("select products_options_id from products_options where language_id=".$einstellungen->languages_id." and products_options_name=\"$Eigenschaft->cName\"");
			$options_id = mysql_fetch_object($cur_query);
			if (!$options_id->products_options_id)
			{
				//erstelle eigenschaft
				//hole max PK
				$cur_query = eS_execute_query("select max(products_options_id) from products_options");
				$max_id_arr = mysql_fetch_row($cur_query);
				$options_id->products_options_id = $max_id_arr[0]+1;
				eS_execute_query("insert into products_options (products_options_id,language_id,products_options_name) values ($options_id->products_options_id,$einstellungen->languages_id,\"$Eigenschaft->cName\")");
				
				//erstelle leere description fÃÂ¼r alle anderen Sprachen
				$sonstigeSprachen = getSonstigeSprachen($einstellungen->languages_id);
				if (is_array($sonstigeSprachen))
				{
					foreach ($sonstigeSprachen as $sonstigeSprache)
					{
						eS_execute_query("insert into products_options (products_options_id,language_id,products_options_name) values ($options_id->products_options_id,$sonstigeSprache,\"$Eigenschaft->cName\")");
					}
				}
			}
			//mapping zu variation 
			setMappingEigenschaft($Eigenschaft->kEigenschaft,$options_id->products_options_id,$Eigenschaft->kArtikel);
			$return = 0;
		}
 	}
	else
		$return=5;
}

mysql_close();
echo($return);
logge($return);
?>