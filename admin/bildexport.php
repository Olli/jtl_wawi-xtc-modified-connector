<?php
/**
 * jtlwawi_connector/bildexport.php
 * AdminLogin fÃÂ¼r JTL-Wawi Connector
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.01 / 07.12.06
*/

require_once("admininclude.php");
require_once("adminTemplates.php");

$adminsession = new AdminSession();

if ($_SESSION["loggedIn"]!=1)
{
	header('Location: index.php');
	exit;
}
if ($_GET['go']==1)
	baueCSV();
else 
{
	zeigeKopf();
	zeigeLinks($_SESSION["loggedIn"]);
	zeigeBildexport();
	zeigeFuss();
}

function zeigeBildexport()
{
	
	echo('
						<td bgcolor="#ffffff" style="border-color:#222222; border-width:1px; border-style:solid; border-top-width:0px; border-bottom-width:0px; border-left-width:0px;" valign="top" align="center" height="400"><br>
							<table cellspacing="0" cellpadding="0" width="96%">
								<tr><td class="content_header" align="center"><h3>Bildexport CSV erstellen</h3></td></tr>
								<tr><td class="content" align="center"><br>
										JTL-Wawi importiert ÃÂ¼ber den Connector gewÃÂ¶hnlich vollautomatisch Bilder beim ersten Webabgleich mit dem Shop.<br><br>
										Diese Funktion richtet sich an alle, die Probleme mit dem Bildimport in JTL-Wawi haben. Es wird hier eine CSV Bildimportdatei fÃÂ¼r JTL-Wawi erstellt, die dort mittels der Funktion "Bildimport" importiet werden kÃÂ¶nnen. Es werden bis zu 10 Bilder pro Artikel importiert.<br>
										Durch den Klick auf Bildexport CSV erstellen wird die CSV estellt. Diese sollten Sie mit Copy & Paste in eine Datei kopieren und abspeichern. Beim Import dieser Datei in JTL-Wawi werden die Bilder nachtrÃÂ¤glich importiert. Bilder, die in JTL-Wawi bereits existieren werden ersetzt.<br><br>
										<a href="bildexport.php?go=1">Bildexport CSV generieren</a>
								</td></tr>
							</table><br>
						</td>
	');
}

function baueCSV()
{
	$bilderUrls="";
	$cur_query = eS_execute_query("select configuration_value from configuration where configuration_key=\"MO_PICS\"");
	$additional_pics = mysql_fetch_object($cur_query);
	//hole alle producte der Reihe nach
	$prod_query = eS_execute_query("select products_id, products_image, products_model from products order by products_model");	
	while ($product = mysql_fetch_object($prod_query))
	{
		if (!$product->products_model)
			continue;
		$pic="";
		//hat produkt bild?
		$imageUrlPrefix="";
		if ($product->products_image)
		{
			$path="";
			if (file_exists(DIR_FS_CATALOG_ORIGINAL_IMAGES.$product->products_image))
				$path=DIR_WS_CATALOG_ORIGINAL_IMAGES;
			elseif (file_exists(DIR_FS_CATALOG_POPUP_IMAGES.$product->products_image))
				$path=DIR_WS_CATALOG_POPUP_IMAGES;
			elseif (file_exists(DIR_FS_CATALOG_IMAGES.$product->products_image))
				$path="/".DIR_WS_IMAGES;

			if (strlen($path)>1)
			{
				
				//is es ein jpg?
				if (preg_match("#jpg#i",substr($product->products_image,strlen($product->products_image)-3)))
				{
					$pic=HTTP_SERVER."/".$path.$product->products_image;
				}
				//is es ein jpeg? 
				elseif (preg_match("#jpeg#i",substr($product->products_image,strlen($product->products_image)-4))) 
				{ 
				   $pic=HTTP_SERVER."/".$path.$product->products_image; 
				}
				elseif (preg_match("#gif#i",substr($product->products_image,strlen($product->products_image)-3)))
				{
					if(function_exists("ImageCreateFromGIF"))
					{
						$im = @ImageCreateFromGIF (DIR_FS_CATALOG_ORIGINAL_IMAGES.$product->products_image);
						if ($im)
						{
							//erstelle dir, falls noch nicht getan
							if (!file_exists(DIR_FS_CATALOG_IMAGES."es_export"))
								mkdir (DIR_FS_CATALOG_IMAGES."es_export");
								
							imagejpeg($im,DIR_FS_CATALOG_IMAGES."es_export/".$product->products_id.".jpg");
							if (file_exists(DIR_FS_CATALOG_IMAGES."es_export/".$product->products_id.".jpg"))
							{
								$pic=HTTP_SERVER."/".DIR_WS_CATALOG_IMAGES."es_export/".$product->products_id.".jpg";
							}
						}
					}
				}
				elseif (preg_match("#png#i",substr($product->products_image,strlen($product->products_image)-3)))
				{
					if(function_exists("ImageCreateFromPNG"))
					{
						$im = @ImageCreateFromPNG (DIR_FS_CATALOG_ORIGINAL_IMAGES.$product->products_image);
						if ($im)
						{
							//erstelle dir, falls noch nicht getan
							if (!file_exists(DIR_FS_CATALOG_IMAGES."es_export"))
								mkdir (DIR_FS_CATALOG_IMAGES."es_export");
								
							imagejpeg($im,DIR_FS_CATALOG_IMAGES."es_export/".$product->products_id.".jpg");
							if (file_exists(DIR_FS_CATALOG_IMAGES."es_export/".$product->products_id.".jpg"))
							{
								$pic=HTTP_SERVER."/".DIR_WS_CATALOG_IMAGES."es_export/".$product->products_id.".jpg";
							}
						}
					}
				}
			}
			if (strlen($pic)>1)
			{
				echo($product->products_model.";".$pic.";1;\r\n");
			}
		}

		if ($additional_pics->configuration_value>0)
		{
			//hole bilder
			$cur_query = eS_execute_query("select * from products_images where products_id=".$product->products_id." order by image_nr");
			while ($bild = mysql_fetch_object($cur_query))
			{
				$pic="";
				if ($bild->image_name && $bild->image_nr>0)
				{
					$path="";
					if (file_exists(DIR_FS_CATALOG_ORIGINAL_IMAGES.$bild->image_name))
						$path=DIR_WS_CATALOG_ORIGINAL_IMAGES;
					elseif (file_exists(DIR_FS_CATALOG_POPUP_IMAGES.$bild->image_name))
						$path=DIR_WS_CATALOG_POPUP_IMAGES;
					elseif (file_exists(DIR_FS_CATALOG_IMAGES.$bild->image_name))
						$path="/".DIR_WS_IMAGES;
						
					if (strlen($path)>1)
					{			
						//is es ein jpg?
						if (preg_match("#jpg#i",substr($bild->image_name,strlen($bild->image_name)-3)))
						{
							$pic=HTTP_SERVER."/".$path.$bild->image_name;
						}
						//is es ein jpeg? 
						elseif (preg_match("#jpeg#i",substr($bild->image_name,strlen($bild->image_name)-4))) 
						{ 
						   $pic=HTTP_SERVER."/".$path.$bild->image_name; 
						}
						elseif (preg_match("#gif#i",substr($bild->image_name,strlen($bild->image_name)-3)))
						{
							if(function_exists("ImageCreateFromGIF"))
							{
								$im = @ImageCreateFromGIF (DIR_FS_CATALOG_ORIGINAL_IMAGES.$bild->image_name);
								if ($im)
								{
									//erstelle dir, falls noch nicht getan
									if (!file_exists(DIR_FS_CATALOG_IMAGES."es_export"))
										mkdir (DIR_FS_CATALOG_IMAGES."es_export");
										
									imagejpeg($im,DIR_FS_CATALOG_IMAGES."es_export/".$product->products_id."_".$bild->image_nr.".jpg");
									if (file_exists(DIR_FS_CATALOG_IMAGES."es_export/".$product->products_id."_".$bild->image_nr.".jpg"))
									{
										$pic=HTTP_SERVER."/".DIR_WS_CATALOG_IMAGES."es_export/".$product->products_id."_".$bild->image_nr.".jpg";
									}
								}
							}
						}
						elseif (preg_match("#png#i",substr($bild->image_name,strlen($bild->image_name)-3)))
						{
							if(function_exists("ImageCreateFromPNG"))
							{
								$im = @ImageCreateFromPNG (DIR_FS_CATALOG_ORIGINAL_IMAGES.$bild->image_name);
								if ($im)
								{
									//erstelle dir, falls noch nicht getan
									if (!file_exists(DIR_FS_CATALOG_IMAGES."es_export"))
										mkdir (DIR_FS_CATALOG_IMAGES."es_export");
										
									imagejpeg($im,DIR_FS_CATALOG_IMAGES."es_export/".$product->products_id."_".$bild->image_nr.".jpg");
									if (file_exists(DIR_FS_CATALOG_IMAGES."es_export/".$product->products_id."_".$bild->image_nr.".jpg"))
									{
										$pic=HTTP_SERVER."/".DIR_WS_CATALOG_IMAGES."es_export/".$product->products_id."_".$bild->image_nr.".jpg";
									}
								}
							}
						}
					}
				}
				if (strlen($pic)>1)
				{
					echo($product->products_model.";".$pic.";".($bild->image_nr+1).";\r\n");
				}
			}
		}
	}
}

?>

