<?php
/**
 * jtlwawi_connector/dbeS/Kategorie.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.03 / 16.09.06
*/

require_once("syncinclude.php");

$return=3;
if (auth())
{
	//hole einstellungen
	$cur_query = eS_execute_query("select * from eazysales_einstellungen");
	$einstellungen = mysql_fetch_object($cur_query);

	if ((intval($_POST["action"]) == 1 || intval($_POST["action"]) == 3) && intval($_POST['KeyKategorie'])>0)
	{
		$return = 0;
		$Kategorie = new stdClass();
		$Kategorie->kKategorie = intval($_POST["KeyKategorie"]);
		$Kategorie->kOberKategorie = intval($_POST["KeyOberKategorie"]);
		$Kategorie->nSort = intval($_POST["Sort"]);
		$Kategorie->cName = realEscape($_POST["KeyName"]);
		$Kategorie->cBeschreibung = realEscape($_POST["KeyBeschreibung"]);
		$Kategorie->parent_id = 0;
		
		if ($Kategorie->kOberKategorie>0)
		{
			//existiert oberkat?
			$categories_id_oberkat = getFremdKategorie($Kategorie->kOberKategorie);
			if (!$categories_id_oberkat)
			{
				eS_execute_query("insert into categories (categories_status, date_added, categories_template, listing_template, products_sorting, products_sorting2) values (0,now(),\"$einstellungen->cat_category_template\",\"$einstellungen->cat_listing_template\",\"$einstellungen->cat_sorting\",\"$einstellungen->cat_sorting2\")");
				//hole id
				$query = eS_execute_query("select LAST_INSERT_ID()");
				$categories_id_oberkat_arr = mysql_fetch_row($query);
				eS_execute_query("insert into categories_description (categories_id, language_id) values (".$categories_id_oberkat_arr[0].",$einstellungen->languages_id)");
				$Kategorie->parent_id = $categories_id_oberkat_arr[0];
				setMappingKategorie($Kategorie->kOberKategorie, $Kategorie->parent_id);
				
				//erstelle leere description fÃÂ¼r alle anderen Sprachen
				$sonstigeSprachen = getSonstigeSprachen($einstellungen->languages_id);
				if (is_array($sonstigeSprachen))
				{
					foreach ($sonstigeSprachen as $sonstigeSprache)
					{
						eS_execute_query("insert into categories_description (categories_id, language_id) values (".$categories_id_oberkat_arr[0].",$sonstigeSprache)");
					}
				}
			}
			else 
				$Kategorie->parent_id = $categories_id_oberkat;
		}
		//update oder insert?
		$categories_id = getFremdKategorie($_POST['KeyKategorie']);
		if ($categories_id>0)
		{
			//update
			eS_execute_query("update categories set parent_id=$Kategorie->parent_id, categories_status=1, sort_order=$Kategorie->nSort where categories_id=".$categories_id);
			eS_execute_query("update categories_description set categories_name=\"$Kategorie->cName\", categories_description=\"$Kategorie->cBeschreibung\" where categories_id=".$categories_id." and language_id=".$einstellungen->languages_id);
		}
		else 
		{
			//insert
			eS_execute_query("insert into categories (parent_id, categories_status, categories_template, listing_template, products_sorting, products_sorting2, date_added, sort_order) values ($Kategorie->parent_id,1,\"".$einstellungen->cat_category_template."\",\"".$einstellungen->cat_listing_template."\",\"".$einstellungen->cat_sorting."\",\"".$einstellungen->cat_sorting2."\",now(),$Kategorie->nSort)");
			$query = eS_execute_query("select LAST_INSERT_ID()");
			$categories_id_arr = mysql_fetch_row($query);
			eS_execute_query("insert into categories_description (categories_id, language_id, categories_name, categories_description) values (".$categories_id_arr[0].",$einstellungen->languages_id, \"$Kategorie->cName\", \"$Kategorie->cBeschreibung\")");
			setMappingKategorie($Kategorie->kKategorie, $categories_id_arr[0]);
			
			//erstelle leere description fÃÂ¼r alle anderen Sprachen
			$sonstigeSprachen = getSonstigeSprachen($einstellungen->languages_id);
			if (is_array($sonstigeSprachen))
			{
				foreach ($sonstigeSprachen as $sonstigeSprache)
				{
					//eS_execute_query("insert into categories_description (categories_id, language_id, categories_name) values (".$categories_id_arr[0].",$sonstigeSprache, \"$Kategorie->cName\")");
					eS_execute_query("insert into categories_description (categories_id, language_id, categories_name, categories_description) values (".$categories_id_arr[0].",$sonstigeSprache, \"$Kategorie->cName\", \"$Kategorie->cBeschreibung\")");
				}
			}
		}
 	}	
 	
	if (intval($_POST["action"]) == 3 && intval($_POST['KeyKategorie'])>0)
	{
		$return=0;
		$cat = getFremdKategorie(intval($_POST['KeyKategorie']));
		if ($cat>0)
			eS_execute_query("update categories set categories_status=0 where categories_id=".$cat);
	}
}

mysql_close();
echo($return);
logge($return);
?>