CREATE TABLE eazysales_adminsession (
  cSessionId varchar(255)  default NULL,
  nSessionExpires int(10) unsigned default NULL,
  cSessionData text 
);

CREATE TABLE eazysales_einstellungen (
  currencies_id smallint(6) default NULL,
  languages_id smallint(6) default NULL,
  mappingEndkunde varchar(255) default NULL,
  mappingHaendlerkunde varchar(255) default NULL,
  shopURL varchar(255) default NULL,
  tax_class_id int(11) default NULL,
  tax_zone_id int(11) default NULL,
  tax_priority int(11) default NULL,
  shipping_status_id int(11) default NULL,
  versandMwst float default NULL,
  cat_listing_template varchar(255) default NULL,
  cat_category_template varchar(255) default NULL,
  cat_sorting varchar(255) default NULL,
  cat_sorting2 varchar(255) default NULL,
  prod_product_template varchar(255) default NULL,
  prod_options_template varchar(255) default NULL,
  StatusAbgeholt tinyint(3) unsigned NOT NULL default '0',
  StatusVersendet tinyint(3) unsigned NOT NULL default '0'
);

CREATE TABLE eazysales_mbestellpos (
  kBestellPos int(10) unsigned NOT NULL auto_increment,
  orders_products_id int(10) unsigned default NULL,
  PRIMARY KEY  (kBestellPos)
);

CREATE TABLE eazysales_martikel (
  products_id int(10) unsigned NOT NULL,
  kArtikel int(10) unsigned default NULL,
  PRIMARY KEY  (products_id)
);

CREATE TABLE eazysales_mkategorie (
  categories_id int(10) unsigned NOT NULL,
  kKategorie int(10) unsigned default NULL,
  PRIMARY KEY  (categories_id)
);

CREATE TABLE eazysales_mvariation (
  kEigenschaft int(10) unsigned NOT NULL,
  products_options_id int(10) unsigned default NULL,
  kArtikel int(11) default NULL,
  PRIMARY KEY  (kEigenschaft)
);

CREATE TABLE eazysales_mvariationswert (
  products_attributes_id int(10) unsigned NOT NULL,
  kEigenschaftsWert int(10) unsigned default NULL,
  kArtikel int(11) default NULL,
  PRIMARY KEY  (products_attributes_id)
);

CREATE TABLE eazysales_sentorders (
  orders_id int(10) unsigned NOT NULL,
  dGesendet datetime default NULL,
  PRIMARY KEY  (orders_id)
);

CREATE TABLE eazysales_sync (
  cName varchar(255)  default NULL,
  cPass varchar(255)  default NULL
);
