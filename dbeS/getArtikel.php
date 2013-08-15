<?php
/**
 * jtlwawi_connector/dbeS/getArtikel.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.10 / 20.11.06
*/
require_once("syncinclude.php");

$Response="";
$return = 3;
//Auth
if (auth())
{
	$return = 0;
	//hole einstellunegn
	$cur_query = eS_execute_query("select * from eazysales_einstellungen");
	$einstellungen = mysql_fetch_object($cur_query);
	
	//get currency
	$cur_query = eS_execute_query("select * from currencies where currencies_id=".$einstellungen->currencies_id);
	$currency = mysql_fetch_object($cur_query);
	//hole einen noch nicht versandten Artikel nach eS raus 
	$cur_query = eS_execute_query("select products.products_id from products LEFT JOIN eazysales_martikel ON products.products_id=eazysales_martikel.products_id where eazysales_martikel.products_id is NULL limit 1");	
	if ($product_id = mysql_fetch_object($cur_query))
	{		
		//hole product		
		$product_query = eS_execute_query("select * from products where products_id=".$product_id->products_id);
		if ($product = mysql_fetch_object($product_query))
		{	
			//hole beschreibung
			$product_desc_query = eS_execute_query("select * from products_description where products_id=".$product_id->products_id." and language_id=".$einstellungen->languages_id." order by products_name");
			$product_desc = mysql_fetch_object($product_desc_query);
			//mappe alles auf product
			$product->language_id=$product_desc->language_id;
			$product->products_name=$product_desc->products_name;
			$product->products_description=$product_desc->products_description;
			$product->products_short_description=$product_desc->products_short_description;
			$product->products_keywords=$product_desc->products_keywords;
			$product->products_meta_title=$product_desc->products_meta_title;
			$product->products_meta_description=$product_desc->products_meta_description;
			$product->products_meta_keywords=$product_desc->products_meta_keywords;
			$product->products_url=$product_desc->products_url;
			
			//hole VPE
			$vpe="";
			if ($product->products_vpe>0)
			{
				$vpe_query = eS_execute_query("select products_vpe_name from products_vpe where language_id=".$GLOBALS['einstellungen']->languages_id." and products_vpe_id=".$product->products_vpe);		
				$vpe_res = mysql_fetch_object($vpe_query);
				$vpe = substr($vpe_res->products_vpe_name,0,10);
			}
			//bereite dieses Produkt zum Senden vor
			//hole Steuer
			$tax = get_tax($product->products_tax_class_id);
			$products_status="N";
			if ($product->products_status)	
				$products_status="Y";
				
			$UVP = 0;
			if (get_preisEndkunde($product)!=$product->products_price)
			{
				$UVP=$product->products_price;
			}
			//baue Response			
			$Response=CSVkonform("P").";".			
					CSVkonform($product->products_id).";".
					CSVkonform(substr(unhtmlentities($product->products_model),0,20)).";".			
					CSVkonform(substr(unhtmlentities($product->products_name),0,255)).";".			
					CSVkonform(substr(unhtmlentities($product->products_description),0,64000)).";".			
					CSVkonform(substr(unhtmlentities($product->products_short_description),0,4000)).";".			
					CSVkonform(get_preisEndkunde($product)+get_preisEndkunde($product)*$currency->value*$tax/100).";".			
					CSVkonform(get_preisEndkunde($product)*$currency->value).";".			
					CSVkonform($UVP+$UVP*$currency->value*$tax/100).";".			
					CSVkonform($tax).";".			
					CSVkonform("").";".			
					CSVkonform("Y").";".			
					CSVkonform($products_status).";".			
					CSVkonform($product->products_quantity).";".			
					CSVkonform($vpe).";".			
					CSVkonform("1").";".			
					CSVkonform(unhtmlentities($product->products_ean)).";".			
					CSVkonform("").";".		
					CSVkonform(get_preisHaendlerKunde($product)+get_preisHaendlerKunde($product)*$currency->value*$tax/100).";".		
					CSVkonform(get_preisHaendlerKunde($product)*$currency->value).";".		
					CSVkonform($product->products_startpage).";".		
					CSVkonform("N").";".		
					CSVkonform("N").";".		
					CSVkonform($product->products_weight).";".		
					CSVkonform("N").";".		
					CSVkonform("N").";".		
					CSVkonform("N").";".		
					CSVkonform("N").";".		
					CSVkonform("0").";".		
					CSVkonform("0").";".		
					CSVkonform(unhtmlentities(getManufacturer($product->manufacturers_id))).";".		
					get_bildURL($product).";\n";		
					
			$Response.=get_cats($product->products_id);
			$Response.=get_variationen($product->products_id);
//			$Response.=get_variationswerte($product->products_id);
			$Response.=get_attribute($product);
			$Response.=get_staffelpreise($product->products_id,1);
			$Response.=get_staffelpreise($product->products_id,0);
		}
	}
}
echo($return.";\n");
echo($Response);

function get_bildURL($product)
{
	$bilderUrls="";
	$cur_query = eS_execute_query("select configuration_value from configuration where configuration_key=\"MO_PICS\"");
	$additional_pics = mysql_fetch_object($cur_query);
	
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
	}
	$bilderUrls = CSVkonform($pic);
	if ($additional_pics->configuration_value>0)
	{
		//hole bilder
		$cur_query = eS_execute_query("select * from products_images where products_id=".$product->products_id." order by image_nr");
		while ($bild = mysql_fetch_object($cur_query))
		{
			$pic="";
			if ($bild->image_name)
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
			$bilderUrls.=";".CSVkonform($pic);
		}
	}
	
	return $bilderUrls;
}

function get_attribute($product)
{
	$attribute="";
	//Lieferstatus
	if ($product->products_shippingtime>0)
	{
		//hole bezeichnung
		$cur_query = eS_execute_query("select shipping_status_name from shipping_status where language_id=".$GLOBALS['einstellungen']->languages_id." and shipping_status_id=".$product->products_shippingtime);
		$status = mysql_fetch_object($cur_query);
		if (strlen($status->shipping_status_name)>0)
		{
			//Attribut hinzufÃÂ¼gen
			$attribute.=CSVkonform("T").";".			
				CSVkonform("Lieferstatus").";".
				CSVkonform($status->shipping_status_name).";\n";			
		}
	}
	
	//rabatt erlaubt
	if ($product->products_discount_allowed>0)
	{
		$attribute.=CSVkonform("T").";".			
			CSVkonform("Rabatt erlaubt").";".
			CSVkonform($product->products_discount_allowed).";\n";
	}
	
	//Herstellerlink gesetzt?
	//Attribut hinzufÃÂ¼gen
	$attribute.=CSVkonform("T").";".			
		CSVkonform("Herstellerlink").";".
		CSVkonform($product->products_url).";\n";	

	//ist vpe gesetzt?
	if ($product->products_vpe_value>0)
	{
		//Attribut hinzufÃÂ¼gen
		$attribute.=CSVkonform("T").";".			
			CSVkonform("VPE Wert").";".
			CSVkonform($product->products_vpe_value).";\n";
	}
	
	//VPE Status
	if ($product->products_vpe_status>0)
	{
		$attribute.=CSVkonform("T").";".			
			CSVkonform("VPE anzeigen").";".
			CSVkonform("ja").";\n";
	}
	else 
	{
		$attribute.=CSVkonform("T").";".			
			CSVkonform("VPE anzeigen").";".
			CSVkonform("nein").";\n";
	}
		
	//FSK 18?
	if ($product->products_fsk18>0)
	{
		//Attribut hinzufÃÂ¼gen
		$attribute.=CSVkonform("T").";".			
			CSVkonform("FSK 18").";".
			CSVkonform("ja").";\n";
	}
	
	
	//Reihung
	$attribute.=CSVkonform("T").";".			
		CSVkonform("Reihung").";".
		CSVkonform($product->products_sort).";\n";
		
	//Reihungstartseite
	$attribute.=CSVkonform("T").";".			
		CSVkonform("Reihung Startseite").";".
		CSVkonform($product->products_startpage_sort).";\n";
		
	//suchbegriffe
	$attribute.=CSVkonform("T").";".			
		CSVkonform("Suchbegriffe").";".
		CSVkonform(substr($product->products_keywords,0,255)).";\n";
				
	//meta title
	$attribute.=CSVkonform("T").";".			
		CSVkonform("Meta Title").";".
		CSVkonform(substr($product->products_meta_title,0,255)).";\n";
				
	//meta description
	$attribute.=CSVkonform("T").";".			
		CSVkonform("Meta Description").";".
		CSVkonform(substr($product->products_meta_description,0,255)).";\n";
				
	//meta keywords
	$attribute.=CSVkonform("T").";".			
		CSVkonform("Meta Keywords").";".
		CSVkonform(substr($product->products_meta_keywords,0,255)).";\n";
		
	return $attribute;
}

function get_variationswerte($products_id,$options_id)
{
	$variationswerte="";
	//existieren Variationen fÃÂ¼r diesen Artikel?
	$cur_query = eS_execute_query("select * from products_attributes where options_id=".$options_id." and products_id=".$products_id);
	while ($variation = mysql_fetch_object($cur_query))
	{
		if ($variation->options_values_id>0)
		{
			//hole Variationswertnamen etc.
			$opt_query = eS_execute_query("select products_options_values_name, products_options_values_id from products_options_values where language_id=".$GLOBALS['einstellungen']->languages_id." and products_options_values_id=".$variation->options_values_id);
			$var_name = mysql_fetch_object($opt_query);
			if ($var_name->products_options_values_id>0)
			{
				if ($variation->price_prefix=="-")
					$variation->options_values_price*=-1;
				if ($variation->weight_prefix=="-")
					$variation->options_values_weight*=-1;
				$variationswerte.=CSVkonform("W").";".			
					CSVkonform($variation->options_id).";".
					CSVkonform($variation->products_attributes_id).";".
					CSVkonform($var_name->products_options_values_name).";".
					CSVkonform($variation->options_values_price+$variation->options_values_price*$GLOBALS['currency']->value*$GLOBALS['tax']/100).";".
					CSVkonform($variation->options_values_price).";".
					CSVkonform($variation->options_values_weight).";".
					CSVkonform(substr($variation->attributes_model,0,30)).";".
					CSVkonform($variation->sortorder).";".
					CSVkonform($variation->attributes_stock).";\n";
			}
		}		
	}
	return $variationswerte;
}

function get_variationen($products_id)
{
	$variationen="";
	//existieren Variationen zu diesem Artikel?
	$cur_query = eS_execute_query("select * from products_attributes where products_id=".$products_id." group by options_id");
	while ($variation = mysql_fetch_object($cur_query))
	{
		if ($variation->options_id>0)
		{
			//hole Variationsname etc.
			$opt_query = eS_execute_query("select products_options_name, products_options_id from products_options where language_id=".$GLOBALS['einstellungen']->languages_id." and products_options_id=".$variation->options_id);
			$var_name = mysql_fetch_object($opt_query);
			if ($var_name->products_options_id>0)
			{
				$variationen.=CSVkonform("V").";".			
					CSVkonform($variation->options_id).";".
					CSVkonform($var_name->products_options_name).";".					
					CSVkonform("Y").";".
					CSVkonform(0).";\n";
			}
			$variationen.=get_variationswerte($products_id,$variation->options_id);
		}
	}
	return $variationen;
}

function get_staffelpreise($products_id, $endkunde)
{
	$staffel="";
	$anzahl = "";
	$preise = "";
	$personalOfferTable = "personal_offers_by_customers_status_";
	$endKunden_arr = explode(";",$GLOBALS['einstellungen']->mappingEndkunde);
	$haendlerKunden_arr = explode(";",$GLOBALS['einstellungen']->mappingHaendlerkunde);	
	
	$id=-1;
	$staffelpreiseDa=false;
	if ($endkunde==1)
	{
		if (strlen($GLOBALS['einstellungen']->mappingEndkunde)>0)
		{
			$staffelpreiseDa=true;
			$table = $personalOfferTable.$endKunden_arr[0];
		}
	}
	else
	{
		if (strlen($GLOBALS['einstellungen']->mappingHaendlerkunde)>0)
		{
			$staffelpreiseDa=true;
			$table = $personalOfferTable.$haendlerKunden_arr[0];
		}
	}
	$anzahlStaffelpreise=0;
	
	if ($staffelpreiseDa)
	{
		//existieren Staffelprese fÃÂ¼r diesen Artikel?
		$cur_query = eS_execute_query("select * from ".$table." where products_id=".$products_id." and quantity>1 order by quantity asc limit 5");
		while ($staffelpreise = mysql_fetch_object($cur_query))
		{
			$anzahlStaffelpreise++;
			$anzahl.=";".CSVkonform($staffelpreise->quantity);
			$preise.=";".CSVkonform($staffelpreise->personal_offer*$GLOBALS['currency']->value);
		}
		if (strlen($anzahl)>0)
		{
			$staffel=CSVkonform("SH");
			if ($endkunde)
				$staffel=CSVkonform("SP");
			$staffel.=$anzahl;
			for ($i=0;$i<5-$anzahlStaffelpreise;$i++)
				$staffel.=";".CSVkonform("0");	
			$staffel.=$preise;
			for ($i=0;$i<5-$anzahlStaffelpreise;$i++)
				$staffel.=";".CSVkonform("0");	
			$staffel.=";\n";	
		}
	}
	return $staffel;
}

function get_preisEndkunde($product)
{	
	$endKunden_arr = explode(";",$GLOBALS['einstellungen']->mappingEndkunde);
	if ($endKunden_arr[0]>=0 && strlen($endKunden_arr[0])>0)
	{
		$personalOfferTable = "personal_offers_by_customers_status_".$endKunden_arr[0];
		$cur_query = eS_execute_query("select * from $personalOfferTable where quantity=1 and products_id=".$product->products_id);
		$staffelpreise = mysql_fetch_object($cur_query);
		if ($staffelpreise->quantity == 1 && $staffelpreise->personal_offer>0)
			return $staffelpreise->personal_offer;
	}
	return $product->products_price;
}

function get_preisHaendlerKunde($product)
{
	$haendlerKunden_arr = explode(";",$GLOBALS['einstellungen']->mappingHaendlerkunde);
	if ($haendlerKunden_arr[0]>=0 && strlen($haendlerKunden_arr[0])>0)
	{
		$personalOfferTable = "personal_offers_by_customers_status_".$haendlerKunden_arr[0];
		$cur_query = eS_execute_query("select * from $personalOfferTable where quantity=1 and products_id=".$product->products_id);
		$staffelpreise = mysql_fetch_object($cur_query);
		if ($staffelpreise->quantity == 1 && $staffelpreise->personal_offer>0)
			return $staffelpreise->personal_offer;
	}
	return 0;
}

//get categroies 
function get_cats($products_id)
{
	$res ="";
	//get cat_id
	$glob_cat_query = eS_execute_query("select * from products_to_categories where products_id=".$products_id);
	while ($act_cat = mysql_fetch_object($glob_cat_query))
	{
		$catArr = array();
		$cur_cat_id = $act_cat->categories_id;
		if ($cur_cat_id>0)
		{
			$cat_query = eS_execute_query("select * from categories where categories_id=".$cur_cat_id);
			$current_cat = mysql_fetch_object($cat_query);	
			array_push($catArr,$cur_cat_id);
		}
		
		while ($current_cat->parent_id>0)
		{
			$cur_cat_id = $current_cat->parent_id;
			$cat_query = eS_execute_query("select * from categories where categories_id=".$cur_cat_id);
			$current_cat = mysql_fetch_object($cat_query);		
			array_push($catArr,$cur_cat_id);		
		}
		$cnt = count($catArr);
		for ($i=0;$i<$cnt;$i++)
		{
			$vor="UK";
			if ($i==0)
				$vor="K";
			$catId = array_pop($catArr);
			$cat_query = eS_execute_query("select * from categories_description where categories_id=".$catId." and language_id=".$GLOBALS['einstellungen']->languages_id);
			$current_cat = mysql_fetch_object($cat_query);
			$cat_query = eS_execute_query("select categories_status, categories_image, sort_order from categories where categories_id=".$catId);
			$current_cat_status = mysql_fetch_object($cat_query);
			$catimage="";
			if ($current_cat_status->categories_image)
			{
				if (preg_match("#gif#i",substr($current_cat_status->categories_image,strlen($current_cat_status->categories_image)-3)))
				{
					if(function_exists("ImageCreateFromGIF"))
					{
						$im = @ImageCreateFromGIF (DIR_FS_CATALOG_IMAGES."categories/".$current_cat_status->categories_image);
						if ($im)
						{
							//erstelle dir, falls noch nicht getan
							if (!file_exists(DIR_FS_CATALOG_IMAGES."es_export"))
								mkdir (DIR_FS_CATALOG_IMAGES."es_export");
								
							imagejpeg($im,DIR_FS_CATALOG_IMAGES."es_export/".$catId.".jpg");
							if (file_exists(DIR_FS_CATALOG_IMAGES."es_export/".$catId.".jpg"))
							{
								$pic=HTTP_SERVER."/".DIR_WS_CATALOG_IMAGES."es_export/".$catId.".jpg";
							}
						}
					}
				}
				elseif (preg_match("#png#i",substr($current_cat_status->categories_image,strlen($current_cat_status->categories_image)-3)))
				{
					if(function_exists("ImageCreateFromPNG"))
					{
						$im = @ImageCreateFromPNG (DIR_FS_CATALOG_IMAGES."categories/".$current_cat_status->categories_image);
						if ($im)
						{
							//erstelle dir, falls noch nicht getan
							if (!file_exists(DIR_FS_CATALOG_IMAGES."es_export"))
								mkdir (DIR_FS_CATALOG_IMAGES."es_export");
								
							imagejpeg($im,DIR_FS_CATALOG_IMAGES."es_export/".$catId.".jpg");
							if (file_exists(DIR_FS_CATALOG_IMAGES."es_export/".$catId.".jpg"))
							{
								$pic=HTTP_SERVER."/".DIR_WS_CATALOG_IMAGES."es_export/".$catId.".jpg";
							}
						}
					}
				}
				else
				{
					$pic=HTTP_SERVER."/".DIR_WS_CATALOG_IMAGES."categories/".$current_cat_status->categories_image;
				}
				$catimage=$pic;
			}
			$res.=CSVkonform($vor).";".			
				CSVkonform(unhtmlentities($current_cat->categories_name)).";".
				CSVkonform(substr(unhtmlentities($current_cat->categories_description),0,64000)).";".
				CSVkonform($catId).";".
				CSVkonform($current_cat_status->categories_status).";".
				CSVkonform($catimage).";".
				CSVkonform($current_cat_status->sort_order).";\n";
		}
	}
	if ($res=="")
		$res.=CSVkonform("K").";".			
			CSVkonform("Top").";".
			CSVkonform("Wurzelkategorie").";".
			CSVkonform(0).";".
			CSVkonform(0).";;".
			CSVkonform(0).";\n";
	return $res;
}

//get hersteller
function getManufacturer($manufacturers_id)
{
	if ($manufacturers_id>0)
	{
		$manu_query = eS_execute_query("select * from manufacturers where manufacturers_id=".$manufacturers_id);
		$manu = mysql_fetch_object($manu_query);
		return ($manu->manufacturers_name);	
	}
	
	return "";
}

?>