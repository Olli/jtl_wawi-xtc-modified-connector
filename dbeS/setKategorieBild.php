<?php
/**
 * jtlwawi_connector/dbeS/setArtikelBild.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.01 / 20.08.06
*/

require_once("syncinclude.php");

$return=3;
$_POST['userID'] = $_POST['euser'];
$_POST['userPWD'] = $_POST['epass'];
if (auth())
{
	$return=0;
	//nur BildNr 1 wird berÃÂ¼cksichtigt
	if (intval($_POST['kArtikelBild'])>0 && intval($_POST['nNr'])==1 && $_FILES['bild'])
	{
		//hol categories_id
		$categories_id = getFremdKategorie(intval($_POST['kArtikelBild']));
		$bildname=$categories_id.".jpg";
		move_uploaded_file($_FILES['bild']['tmp_name'],DIR_FS_CATALOG_IMAGES."categories/".$bildname);
		chmod (DIR_FS_CATALOG_IMAGES."categories/".$bildname, 0644);
		//updaten
		eS_execute_query("update categories set categories_image=\"$bildname\" where categories_id=".$categories_id);
	}
}
mysql_close();
echo($return);
logge($return);

?>