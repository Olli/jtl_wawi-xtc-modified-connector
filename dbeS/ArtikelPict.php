<?php
/**
 * jtlwawi_connector/dbeS/ArtikelPict.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.03 / 20.08.06
*/

require_once("syncinclude.php");
$picpath = "../produktbilder/";
$return=3;
if (auth())
{
	$return=0;
/*	$ArtikelPict = new ArtikelPict();
	if (intval($_POST["action"]) == 1 && $ArtikelPict->setzePostDaten())
	{
		$oldArtikelPict = new ArtikelPict();
		$oldArtikelPict->loadFromDB($ArtikelPict->kArtikel);

		$GLOBALS["DB"]->executeQuery("DELETE from tartikelpict where kArtikel=".$ArtikelPict->kArtikel,4);

		if ($ArtikelPict->insertInDB())
			$return = 0;
		else
			$return = 1;

		//gibt es alte Bilder zum lÃÂ¶schen?
		if ($oldArtikelPict->cPfad1 && !$ArtikelPict->cPfad1)
		{
			//bild nr 1 existiert nicht mehr. im fs lÃÂ¶schen
			if (file_exists($picpath.$oldArtikelPict->cPfad1))
			{
				unlink($picpath.$oldArtikelPict->cPfad1);
				if (file_exists(substr($picpath.$oldArtikelPict->cPfad1, 0, -4).'-m.jpg'))
					unlink(substr($picpath.$oldArtikelPict->cPfad1, 0, -4).'-m.jpg');
				if (file_exists(substr($picpath.$oldArtikelPict->cPfad1, 0, -4).'-s.jpg'))
					unlink(substr($picpath.$oldArtikelPict->cPfad1, 0, -4).'-s.jpg');
			}
		}
		if ($oldArtikelPict->cPfad2 && !$ArtikelPict->cPfad2)
		{
			//bild nr 2 existiert nicht mehr. im fs lÃÂ¶schen
			if (file_exists($picpath.$oldArtikelPict->cPfad2))
			{
				unlink($picpath.$oldArtikelPict->cPfad2);
				if (file_exists(substr($picpath.$oldArtikelPict->cPfad2, 0, -4).'-m.jpg'))
					unlink(substr($picpath.$oldArtikelPict->cPfad2, 0, -4).'-m.jpg');
				if (file_exists(substr($picpath.$oldArtikelPict->cPfad2, 0, -4).'-s.jpg'))
					unlink(substr($picpath.$oldArtikelPict->cPfad2, 0, -4).'-s.jpg');
			}
		}
		if ($oldArtikelPict->cPfad3 && !$ArtikelPict->cPfad3)
		{
			//bild nr 3 existiert nicht mehr. im fs lÃÂ¶schen
			if (file_exists($picpath.$oldArtikelPict->cPfad3))
			{
				unlink($picpath.$oldArtikelPict->cPfad3);
				if (file_exists(substr($picpath.$oldArtikelPict->cPfad3, 0, -4).'-m.jpg'))
					unlink(substr($picpath.$oldArtikelPict->cPfad3, 0, -4).'-m.jpg');
				if (file_exists(substr($picpath.$oldArtikelPict->cPfad3, 0, -4).'-s.jpg'))
					unlink(substr($picpath.$oldArtikelPict->cPfad3, 0, -4).'-s.jpg');
			}
		}

		//bilder skalieren
		if($ArtikelPict->cPfad1 && file_exists($picpath . $ArtikelPict->cPfad1))
		{
			$picbig = $picpath . $ArtikelPict->cPfad1;
			$picmedium = substr($picpath . $ArtikelPict->cPfad1, 0, -4).'-m.jpg';
			$picsmall = substr($picpath . $ArtikelPict->cPfad1, 0, -4).'-s.jpg';
			$image = imagecreatefromjpeg($picbig);
			list($width, $height) = getimagesize($picbig);
			$ratio = $width / $height;

			//thumbnail
			$new_width = 80;
			$new_height = round (80 / $ratio);
			$image_p = imagecreatetruecolor($new_width, $new_height);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			imagejpeg($image_p, $picsmall, 80);

			//medium
			$new_width = 210;
			$new_height = round (210 / $ratio);
			$image_p = imagecreatetruecolor($new_width, $new_height);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			imagejpeg($image_p, $picmedium, 80);

			//groÃÂ
			$new_width = 800;
			$new_height = round (800 / $ratio);
			if ($width>$new_width || $height>$new_height)
			{
				$image_p = imagecreatetruecolor($new_width, $new_height);
				imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				imagejpeg($image_p, $picbig, 100);
			}
		}

		if( $ArtikelPict->cPfad2 && file_exists($picpath . $ArtikelPict->cPfad2))
		{
			$picbig = $picpath . $ArtikelPict->cPfad2;
			$picmedium = substr($picpath . $ArtikelPict->cPfad2, 0, -4).'-m.jpg';
			$picsmall = substr($picpath . $ArtikelPict->cPfad2, 0, -4).'-s.jpg';
			$image = imagecreatefromjpeg($picbig);
			list($width, $height) = getimagesize($picbig);
			$ratio = $width / $height;

			//thumbnail
			$new_width = 80;
			$new_height = round (80 / $ratio);
			$image_p = imagecreatetruecolor($new_width, $new_height);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			imagejpeg($image_p, $picsmall, 100);

			//medium
			$new_width = 210;
			$new_height = round (210 / $ratio);
			$image_p = imagecreatetruecolor($new_width, $new_height);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			imagejpeg($image_p, $picmedium, 100);

			//medium
			$new_width = 800;
			$new_height = round (800 / $ratio);
			if ($width>$new_width || $height>$new_height)
			{
				$image_p = imagecreatetruecolor($new_width, $new_height);
				imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				imagejpeg($image_p, $picbig, 100);
			}
		}

		if( $ArtikelPict->cPfad3 && file_exists($picpath . $ArtikelPict->cPfad3))
		{
			$picbig = $picpath . $ArtikelPict->cPfad3;
			$picmedium = substr($picpath . $ArtikelPict->cPfad3, 0, -4).'-m.jpg';
			$picsmall = substr($picpath . $ArtikelPict->cPfad3, 0, -4).'-s.jpg';
			$image = imagecreatefromjpeg($picbig);
			list($width, $height) = getimagesize($picbig);
			$ratio = $width / $height;

			//thumbnail
			$new_width = 80;
			$new_height = round (80 / $ratio);
			$image_p = imagecreatetruecolor($new_width, $new_height);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			imagejpeg($image_p, $picsmall, 100);

			//medium
			$new_width = 210;
			$new_height = round (210 / $ratio);
			$image_p = imagecreatetruecolor($new_width, $new_height);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			imagejpeg($image_p, $picmedium, 100);

			//medium
			$new_width = 800;
			$new_height = round (800 / $ratio);
			if ($width>$new_width || $height>$new_height)
			{
				$image_p = imagecreatetruecolor($new_width, $new_height);
				imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				imagejpeg($image_p, $picbig, 100);
			}
		}
 	}
	else
		$return=5;
	*/
	if (intval($_POST["action"]) == 3 && intval($_POST['KeyArtikel'])>0)
	{
		$return =0;
		$products_id = getFremdArtikel(intval($_POST['KeyArtikel']));
		if ($products_id>0)
		{
			if (intval($_POST["Nr"]) == 1)
			{
				eS_execute_query("update products set products_image='' where products_id=".$products_id);
			}
			if (intval($_POST["Nr"]) > 1)
			{
				eS_execute_query("delete from products_images where products_id=$products_id and image_nr=".(intval($_POST['Nr'])-1));
			}
		}
	}
	
}

mysql_close();
echo($return);
logge($return);
?>