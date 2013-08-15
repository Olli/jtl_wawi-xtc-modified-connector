<?php
/**
 * jtlwawi_connector/dbeS/KategoriePict.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.01 / 03.07.06
*/

require_once("syncinclude.php");
logExtra(Dump($_POST));
$return=3;
if (auth())
{
	$return=0;
	/*
	$KategoriePict = new KategoriePict();
	if (intval($_POST["action"]) == 1 && $KategoriePict->setzePostDaten() && intval($_POST['KeyKategoriePict']))
	{
		$GLOBALS["DB"]->executeQuery("DELETE from tkategoriepict where kKategoriePict=".intval($_POST['KeyKategoriePict']),4);

		if ($KategoriePict->insertInDB())
			$return = 0;
		else
			$return = 1;

		//bilder skalieren
		$picbig = $picpath . $KategoriePict->cPfad;

		if (file_exists($picbig))
		{
			$picsmall = substr($picpath . $KategoriePict->cPfad, 0, -4).'-s.jpg';
			$image = imagecreatefromjpeg($picbig);
			list($width, $height) = getimagesize($picbig);
			

			//thumbnail
			$ratio = $width / $height;
			$new_width = 100;
			$new_height = round (100 / $ratio);
			
			if ($new_height>100)
			{
				$new_height=100;
				$new_width=100*$ratio;
			}

			$image_p = imagecreatetruecolor($new_width, $new_height);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			imagejpeg($image_p, $picsmall, 80);
		}
 	}
	else
		$return=5;

	*/
	if (intval($_POST["action"]) == 3 && intval($_POST['KeyKategorie'])>0)
	{
		$return = 0;
		//hol categories_id
		$categories_id = getFremdKategorie(intval($_POST['KeyKategorie']));
		eS_execute_query("update categories set categories_image='' where categories_id=".$categories_id);
		
	}
}

mysql_close();
echo($return);
logge($return);
?>