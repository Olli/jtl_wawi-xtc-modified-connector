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
 * @version v1.03 / 20.08.06
*/

require_once("syncinclude.php");

$return=3;
$_POST['userID'] = $_POST['euser'];
$_POST['userPWD'] = $_POST['epass'];
if (auth())
{
	$return=0;
	//nur BildNr 1 wird berÃÂ¼cksichtigt
	if (intval($_POST['kArtikelBild'])>0 && $_FILES['bild'])
	{
		//hol Anzahl unterstÃÂ¼tzter Bidler 
		$cur_query = xtc_db_query("select configuration_value from configuration where configuration_key=\"MO_PICS\"");
		$additional_pics = mysql_fetch_object($cur_query);
		
		//hol products_id
		$products_id = getFremdArtikel(intval($_POST['kArtikelBild']));	
		if ($products_id>0)	
		{
			$bildname=$products_id."_".(intval($_POST['nNr'])-1).".jpg";
			if (intval($_POST['nNr'])==1 || $additional_pics->configuration_value>=intval($_POST['nNr'])-1)
			{
				move_uploaded_file($_FILES['bild']['tmp_name'],DIR_FS_CATALOG_ORIGINAL_IMAGES.$bildname);
				chmod (DIR_FS_CATALOG_ORIGINAL_IMAGES.$bildname, 0644);
			
				$im = @ImageCreateFromJPEG (DIR_FS_CATALOG_ORIGINAL_IMAGES.$bildname);
				if ($im)
				{	
					//bild skalieren
					list($width, $height) = getimagesize(DIR_FS_CATALOG_ORIGINAL_IMAGES.$bildname);
					$ratio = $width / $height;
					
					//thumbnail
					$cur_query = xtc_db_query("select configuration_value from configuration where configuration_key=\"PRODUCT_IMAGE_THUMBNAIL_WIDTH\"");
					$width_obj = mysql_fetch_object($cur_query);
					$cur_query = xtc_db_query("select configuration_value from configuration where configuration_key=\"PRODUCT_IMAGE_THUMBNAIL_HEIGHT\"");
					$height_obj = mysql_fetch_object($cur_query);
					$new_width = 120;
					if ($width_obj->configuration_value>0)
						$new_width = $width_obj->configuration_value;
					$new_height = round ($new_width / $ratio);
					if ($new_height>$height_obj->configuration_value)
					{
						$new_height=$height_obj->configuration_value;
						$new_width = round ($new_height * $ratio);
					}
					$image_p = imagecreatetruecolor($new_width, $new_height);
					imagecopyresampled($image_p, $im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
					imagejpeg($image_p, DIR_FS_CATALOG_THUMBNAIL_IMAGES.$bildname, 80);
					chmod (DIR_FS_CATALOG_THUMBNAIL_IMAGES.$bildname, 0644);
					
					//info
					$cur_query = xtc_db_query("select configuration_value from configuration where configuration_key=\"PRODUCT_IMAGE_INFO_WIDTH\"");
					$width_obj = mysql_fetch_object($cur_query);
					$cur_query = xtc_db_query("select configuration_value from configuration where configuration_key=\"PRODUCT_IMAGE_INFO_HEIGHT\"");
					$height_obj = mysql_fetch_object($cur_query);
					$new_width = 200;
					if ($width_obj->configuration_value>0)
						$new_width = $width_obj->configuration_value;
					$new_height = round ($new_width / $ratio);
					if ($new_height>$height_obj->configuration_value)
					{
						$new_height=$height_obj->configuration_value;
						$new_width = round ($new_height * $ratio);
					}
					$image_p = imagecreatetruecolor($new_width, $new_height);
					imagecopyresampled($image_p, $im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
					imagejpeg($image_p, DIR_FS_CATALOG_INFO_IMAGES.$bildname, 80);
					chmod (DIR_FS_CATALOG_INFO_IMAGES.$bildname, 0644);
								
					//popup
					$cur_query = xtc_db_query("select configuration_value from configuration where configuration_key=\"PRODUCT_IMAGE_POPUP_WIDTH\"");
					$width_obj = mysql_fetch_object($cur_query);
					$cur_query = xtc_db_query("select configuration_value from configuration where configuration_key=\"PRODUCT_IMAGE_POPUP_HEIGHT\"");
					$height_obj = mysql_fetch_object($cur_query);
					$new_width = 300;
					if ($width_obj->configuration_value>0)
						$new_width = $width_obj->configuration_value;
					$new_height = round ($new_width / $ratio);
					if ($new_height>$height_obj->configuration_value)
					{
						$new_height=$height_obj->configuration_value;
						$new_width = round ($new_height * $ratio);
					}
					$image_p = imagecreatetruecolor($new_width, $new_height);
					imagecopyresampled($image_p, $im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
					imagejpeg($image_p, DIR_FS_CATALOG_POPUP_IMAGES.$bildname, 80);
					chmod (DIR_FS_CATALOG_POPUP_IMAGES.$bildname, 0644);
					
				
					//updaten
					if (intval($_POST['nNr'])==1)
						eS_execute_query("update products set products_image=\"$bildname\" where products_id=".$products_id);
					else 
					{
						//lÃÂ¶sche evtl. alten Eintrag
						eS_execute_query("delete from products_images where products_id=$products_id and image_nr=".(intval($_POST['nNr'])-1));
						eS_execute_query("insert into products_images (products_id, image_nr, image_name) values ($products_id, ".(intval($_POST['nNr'])-1).", \"".$bildname."\")");
					}
				}
			}
		}
	}
}
mysql_close();
echo($return);
logge($return);

?>