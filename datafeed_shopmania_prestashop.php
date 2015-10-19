<?php

##########################################################################################
# In order to be able to use this script you need to join the merchant program depending on the country where your store is selling the products
#
# AUSTRALIA - http://shopmania.com.au/ (only supporting AUD, NZD datafeeds)
# ARGENTINA - http://www.shopmania.com.ar/ (only supporting ARS, EUR, USD)       *NEW
# BRASIL - http://www.shopmania.com.br/ (only supporting BRL, USD) 
# BULGARY - http://www.shopmania.bg/ (only supporting BGN, EUR, USD)
# CZECH REPUBLIC - http://www.shop-mania.cz/ (only supporting CZK, EUR, USD)		*NEW
# CHILE - http://www.shopmania.cl/ (only supporting CLP, USD, EUR)       *NEW
# CHINA - http://www.shopmania.cn/ (only supporting CNY, USD)       
# DEUTSCHLAND - http://www.shopmania.de/ (only supporting EUR, USD) 
# FRANCE - http://www.shopmania.fr/ (only supporting EUR, USD datafeeds)
# HUNGARY - http://www.shopmania.hu/ (only supporting HUF, EUR, USD datafeeds)
# INDIA - http://www.shopmania.in/ (only supporting INR, USD datafeeds)
# IRELAND - http://www.shopmania.ie/ (only supporting EUR, GBP datafeeds)
# ITALY - http://www.shopmania.it/ (only supporting EUR, USD datafeeds)
# JAPAN - http://www.shopmania.jp/  (only supporting JPY, USD datafeeds)       
# MEXICO - http://www.shopmania.com.mx/ (only supporting MXN (Mexican peso), USD, EUR datafeeds)
# NETHERLANDS - http://www.shopmania.nl/ (only supporting EUR datafeeds)		*NEW
# POLSKA - http://www.shopmania.pl/ (only supporting PLN, EUR, USD) 
# PORTUGAL - http://www.shopmania.pt/ (only supporting EUR, USD) 
# ROMANIA - http://www.shopmania.ro/ (only supporting RON, EUR, USD datafeeds)
# RUSSIA - http://www.shopmania.ru/ (only supporting RUB, EUR, USD)       
# SERBIA - http://www.shopmania.rs/ (only supporting RSD, EUR)		*NEW	
# SLOVAKIA - http://www.shop-mania.sk/ (only supporting EUR, USD)
# SOUTH AFRICA - http://www.shopmania.co.za/ (only supporting ZAR, USD, EUR)       *NEW
# SPAIN - http://www.shopmania.es/ (only supporting EUR datafeeds) 
# SWEDEN - http://www.shopmania.se/ (only supporting SEK, EUR, USD datafeeds)		*NEW
# TURKEY - http://www.shopmania.com.tr/ (only supporting TRY, EUR, USD)
# US - http://www.shopmania.com/ (only supporting USD, CAD datafeeds)
# UK - http://www.shopmania.co.uk/ (only supporting GBP, EUR, USD datafeeds)
#
# Once you join the program and your application is approved you need to place the file on your server and set up the path to the file on the Merchant Interface
# Files will be  retrieved daily from your server having the products listed automatically on ShopMania
# 
# 
# Options
# @url_param taxes=on (on,off) 
# @url_param storetaxes=on (on,off) 
# @url_param discount=on (on,off) 
# @url_param add_vat=off (on,off) 
# @url_param vat_value=24 (VAT_VALUE) 
# @url_param shipping=off (on,off) 
# @url_param add_tagging=on (on,off) 
# @url_param tagging_params=&utm_source=shopmania&utm_medium=cpc&utm_campaign=direct_link (TAGGING_PARAMS) 
# @url_param description=on (on,off) 
# @url_param image=on (on,off) 
# @url_param specialprice=on (on,off) 
# @url_param sef=off (on,off) 
# @url_param on_stock=off (on,off) 
# @url_param forcepath=off (on,off) 
# @url_param forcefolder= (FORCEFOLDER) 
# @url_param currency= (DEFAULT_CURRENCY) 
#
# 
######################################################################

// Debuging
if (isset($_GET['debug'])) {
	$time_start = microtime(true);
	$time = $time_start;
}

// Current datafeed script version
$script_version = "1.60";

// Print current Script version
if (@$_GET['get'] == "version") {
	echo "<b>Datafeed PrestaShop</b><br />";
	echo "script version <b>" . $script_version . "</b><br />";
	exit;
}

// Set no time limit only if php is not running in Safe Mod
if (!ini_get("safe_mode")) {
   if (function_exists('set_time_limit')) {
		@set_time_limit(0);
	}
	if ((int)substr(ini_get("memory_limit"), 0, -1) < 512) {
		ini_set("memory_limit", "512M");
	}
}

// Error reporting
if (@$_GET['errors'] == "off") {
	// Turn off all error reporting
	error_reporting(0);
}
else {
	error_reporting(E_ALL^E_NOTICE);
}
ignore_user_abort();
$_SVR = array();

##### Include configuration files ################################################

$site_base_path = "./";

# Once all is set up you need to check the result and make sure the output is correct
# Point the browser to http://www.example.com/path_to_datafeed/shopmania_datafeed_prestashop.php and look into the source code of the out put
# What you need to see is something like this
# Category | Manufacturer | Part Number | Merchant Code | Product Name | Product Description | Product URL | Product Image URL | Product Price | Currency | Shipping value | Availability | GTIN (UPC/EAN/ISBN) 

//Avoid any modifications below this line

// Include configuration file
if (!file_exists($site_base_path . 'init.php')) {
	exit('<HTML><HEAD><TITLE>404 Not Found</TITLE></HEAD><BODY><H1>Not Found</H1>Please ensure that datafeed_shopmania_prestashop.php is in the root directory, or make sure the path to the directory where the init.php file is located is defined corectly above in $site_base_path variable</BODY></HTML>');
}
else {
	include($site_base_path . 'config/config.inc.php');
	require_once($site_base_path . 'init.php');
}

// Force default db connection
if (@$_GET['connection'] == "forced") {
	// Connect to the database
	$conn = @mysql_connect(_DB_SERVER_, _DB_USER_, _DB_PASSWD_);
	if (mysql_error()) {
		print "Connection error. Please check the connection settings. Bye bye...";
		exit;
	}
	// Select database to use
	else {
		@mysql_select_db(_DB_NAME_, $conn);
		if (mysql_error()) {
			print "Connection error. Please check the connection settings. Bye bye...";
			exit;
		}
	}
}

####################################################################

// Datafeed specific settings
$datafeed_separator = "|"; // Possible options are \t or |

##### Extract params from url ################################################

$apply_taxes = (@$_GET['taxes'] == "off") ? "off" : "on";
$apply_storetaxes = (@$_GET['storetaxes'] == "off") ? "off" : "on";
$apply_discount = (@$_GET['discount'] == "off") ? "off" : "on";
$add_vat = (@$_GET['add_vat'] == "on") ? "on" : "off";
$vat_value = (@$_GET['vat_value'] > 0) ? ((100 + $_GET['vat_value']) / 100) : 1.24; // default value
$add_shipping = (@$_GET['shipping'] == "on") ? "on" : ((@$_GET['shipping'] == "v2") ? "v2" : "off");
$availability = (@$_GET['availability'] == "off") ? "off" : "on";
$gtin = (@$_GET['gtin'] == "off") ? "off" : "on";
$add_tagging = (@$_GET['add_tagging'] == "off") ? "off" : "on";
$tagging_params = (@$_GET['tagging_params'] != "") ? urldecode($_GET['tagging_params']) : "utm_source=shopmania&utm_medium=cpc&utm_campaign=direct_link";
$show_description = (@$_GET['description'] == "off") ? "off" : ((@$_GET['description'] == "limited") ? "limited" : ((@$_GET['description'] == "short") ? "short" : ((@$_GET['description'] == "full") ? "full" : ((@$_GET['description'] == "full_limited") ? "full_limited" : ((@$_GET['description'] > 0) ? $_GET['description'] : "on")))));
$show_image = (@$_GET['image'] == "off" || @$_GET['images'] == "off") ? "off" : ((@$_GET['image'] == "v3" || @$_GET['images'] == "v3") ? "v3" : ((@$_GET['image'] == "v2" || @$_GET['images'] == "v2") ? "v2" : ((@$_GET['image'] == "v1" || @$_GET['images'] == "v1") ? "v1" : "on")));
$image_size = (@$_GET['image_size'] != "") ? $_GET['image_size'] : "";
$show_specialprice = (@$_GET['specialprice'] == "off") ? "off" : "on";
$sef = (@$_GET['sef'] == "on") ? "on" : ((@$_GET['sef'] == "v2") ? "v2" : "off");
$on_stock_only = (@$_GET['on_stock'] == "on") ? "on" : "off";
$available_for_order = (@$_GET['available_for_order'] == "off") ? "off" : "on";
$currency = (@$_GET['currency'] != "") ? $_GET['currency'] : "";
$currency_id = (@$_GET['currency_id'] != "") ? $_GET['currency_id'] : "";
$language_code = (@$_GET['language'] != "") ? $_GET['language'] : "";
$language_id = (@$_GET['language_id'] != "") ? $_GET['language_id'] : "";
$force_path = (@$_GET['forcepath'] == "on") ? "on" : "off";
$force_folder = (@$_GET['forcefolder'] != "") ? $_GET['forcefolder'] : "";
$limit = (@$_GET['limit'] > 0) ? $_GET['limit'] : "";
$cookies = (@$_GET['cookies'] == "off") ? "off" : "on";
$default_cat = (@$_GET['default_cat'] == "on") ? "on" : "off";
$full_cat = (@$_GET['full_cat'] == "on") ? "on" : "off";
$display_currency = (@$_GET['display_currency'] != "") ? $_GET['display_currency'] : "";
$version = (@$_GET['version'] > 0) ? $_GET['version'] : ""; // default value
$show_combinations = (@$_GET['combinations'] == "on") ? "on" : "off";
$full_cat_path = (@$_GET['full_cat_path'] == "on") ? "on" : "off";
$show_attribute = (@$_GET['attribute'] == "on") ? "on" : "off";
####################################################################

$base_dir = "http://" . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, "UTF-8") . __PS_BASE_URI__;
$base_image_dir = "http://" . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, "UTF-8") . _THEME_PROD_DIR_;

// get configuration values
$configuration = Configuration::getMultiple(array(
	'PS_ORDER_OUT_OF_STOCK',
	'PS_STOCK_MANAGEMENT',
	'PS_REWRITING_SETTINGS',
	'PS_CURRENCY_DEFAULT',
	'PS_LANG_DEFAULT',
	'PS_SHIPPING_FREE_PRICE',
	'PS_SHIPPING_HANDLING',
	'PS_SHIPPING_METHOD',
	'PS_SHIPPING_FREE_WEIGHT'
));

// Get allow out of stock ordering value 1 - yes / 0 - no
$allow_out_of_stock_ordering = (int)$configuration['PS_ORDER_OUT_OF_STOCK'];

// Get stock management condition 
$enable_stock_management = (int)$configuration['PS_STOCK_MANAGEMENT'];

// Print URL options
if (@$_GET['get'] == "options") {
	$script_basepath = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
	
	echo "<b>Datafeed PrestaShop</b><br />";
	echo "PrestaShop version <b>" . _PS_VERSION_ . "</b><br />";
	echo "script version <b>" . $script_version . "</b><br /><br /><br />";

	echo "<b>Show stores</b> - possible values on<br />";
	echo "show_stores=on <a href=\"" . $script_basepath . "?show_stores=on" . "\" >" . $script_basepath . "?show_stores=on" . "</a><br /><br />";
	
	echo "<b>Db connection</b> - force the default prestashop db connection <br />";
	echo "connection= <a href=\"" . $script_basepath . "?connection=forced" . "\" >" . $script_basepath . "?connection=forced" . "</a><br /><br />";
		
	//echo "Taxes options - possible values on, off default value on<br />";
	//echo "taxes=on (on,off) <a href=\"" . $script_basepath . "?taxes=off" . "\" >" . $script_basepath . "?taxes=off" . "</a><br /><br />";
	
	//echo "Store taxes options - possible values on, off default value on<br />";
	//echo "storetaxes = on (on,off) <a href=\"" . $script_basepath . "?storetaxes=off" . "\" >" . $script_basepath . "?storetaxes=off" . "</a><br /><br />";
	
	//echo "Discount options - possible values on, off default value on<br />";
	//echo "discount=on (on,off) <a href=\"" . $script_basepath . "?discount=off" . "\" >" . $script_basepath . "?discount=off" . "</a><br /><br />";
	
	echo "<b>Add VAT to prices</b> - possible values on, off default value off<br />";
	echo "add_vat=off (on,off) <a href=\"" . $script_basepath . "?add_vat=on" . "\" >" . $script_basepath . "?add_vat=on" . "</a><br /><br />";
	
	echo "<b>VAT value</b> - possible values percent value default value 24  - interger or float number ex 19 or 19.5<br />";
	echo "vat_value=24 (VAT_VALUE) <a href=\"" . $script_basepath . "?add_vat=on&vat_value=19" . "\" >" . $script_basepath . "?add_vat=on&vat_value=19" . "</a><br /><br />";

	echo "<b>Add shipping to datafeed</b> - possible values on, off, v2 default value off  on - for a limited number of products<br />";
	echo "shipping=off (on,off,v2) <ul><li><a href=\"" . $script_basepath . "?shipping=on" . "\" >" . $script_basepath . "?shipping=on" . "</a></li>
	<li><a href=\"" . $script_basepath . "?shipping=v2" . "\" >" . $script_basepath . "?shipping=v2" . "</a> (only Handling + additional shipping cost)</li></ul>";
	
	echo "<b>Add availability to datafeed</b> - possible values on, off default value on<br />";
	echo "availability=on (on,off) <a href=\"" . $script_basepath . "?availability=off" . "\" >" . $script_basepath . "?availability=off" . "</a><br /><br />";
	
	echo "<b>Show only products available for order</b> - possible values on, off default value on<br />";
	echo "available_for_order=on (on,off) <a href=\"" . $script_basepath . "?available_for_order=off" . "\" >" . $script_basepath . "?available_for_order=off" . "</a><br /><br />";
	
	echo "<b>Add GTIN to datafeed</b> - possible values on, off default value on<br />";
	echo "gtin=on (on,off) <a href=\"" . $script_basepath . "?gtin=off" . "\" >" . $script_basepath . "?gtin=off" . "</a><br /><br />";
	
	echo "<b>Add GA Tagging to product URL</b> - possible values on, off default value on<br />";
	echo "add_tagging=on (on,off) <a href=\"" . $script_basepath . "?add_tagging=off" . "\" >" . $script_basepath . "?add_tagging=off" . "</a><br /><br />";
	
	echo "<b>Add custom tagging to product URL</b> - possible values url_encode(TAGGING_PARAMS) default value tagging_params=utm_source=shopmania&utm_medium=cpc&utm_campaign=direct_link<br />";
	echo "tagging_params=utm_source=shopmania&utm_medium=cpc&utm_campaign=direct_link (TAGGING_PARAMS) <a href=\"" . $script_basepath . "?tagging_params=from%3Dshopmania" . "\" >" . $script_basepath . "?tagging_params=from%3Dshopmania" . "</a><br /><br />";

	echo "<b>Display Description options</b> - possible values on, off, short, full, limited, full_limited, char_limit (integer) default value on<br />";
	echo "<ul><li><b>description=off</b> - do not display descriptions<br/ > <a href=\"" . $script_basepath . "?description=off" . "\" >" . $script_basepath . "?description=off" . "</a></li>";
	echo "<li><b>description=short</b> - display short descriptions<br/ > <a href=\"" . $script_basepath . "?description=short" . "\" >" . $script_basepath . "?description=short" . "</a></li>";
	echo "<li><b>description=full</b> - display full descriptions (short description + description)<br/ > <a href=\"" . $script_basepath . "?description=full" . "\" >" . $script_basepath . "?description=full" . "</a></li>";
	echo "<li><b>description=limited</b> - display limited descriptions (descriptions limited to 300 chars)<br/ > <a href=\"" . $script_basepath . "?description=limited" . "\" >" . $script_basepath . "?description=limited" . "</a></li>";
	echo "<li><b>description=full_limited</b> - display full descriptions (short description + description) limited to 300 chars<br/ > <a href=\"" . $script_basepath . "?description=full_limited" . "\" >" . $script_basepath . "?description=full_limited" . "</a></li>";
	echo "<li><b>description=char_limit</b> - display limited description to char_limit characters <br/ > <a href=\"" . $script_basepath . "?description=500" . "\" >" . $script_basepath . "?description=500" . "</a></li></ul>";
	
	echo "<b>Display image options</b> - possible values on, off, v2, v3 default value on(v3) both image and images parameter can be used<br />";
	echo "<ul><li><b>images=off</b> - do not display images<br/ > <a href=\"" . $script_basepath . "?images=off" . "\" >" . $script_basepath . "?images=off" . "</a></li>";
	echo "<li><b>images=v1</b> - /prod_id-image_id/seo_keyword.jpg<br/ > <a href=\"" . $script_basepath . "?images=v1" . "\" >" . $script_basepath . "?images=v1" . "</a></li>";
	echo "<li><b>images=v2</b> - /image_id/seo_keyword.jpg<br/ > <a href=\"" . $script_basepath . "?images=v2" . "\" >" . $script_basepath . "?images=v2" . "</a></li>";
	echo "<li><b>images=v3</b> - img/p/x/y/z/xyz.jpg <br/ > <a href=\"" . $script_basepath . "?images=v3" . "\" >" . $script_basepath . "?images=v3" . "</a></li></ul>";
	
	echo "<b>Image size</b> - possible values: large, medium <br />";
	echo "image_size= (on,off) <a href=\"" . $script_basepath . "?image_size=large" . "\" >" . $script_basepath . "?image_size=large" . "</a><br /><br />";
	
	//echo "Special price options - possible values on, off default value on<br />";
	//echo "specialprice=on (on,off) <a href=\"" . $script_basepath . "?specialprice=off" . "\" >" . $script_basepath . "?specialprice=off" . "</a><br /><br />";
	
	echo "<b>Show only on stock products</b> - possible values on, off default value off<br />";
	echo "on_stock=off (on,off) <a href=\"" . $script_basepath . "?on_stock=on" . "\" >" . $script_basepath . "?on_stock=on" . "</a><br /><br />";
	
	echo "<b>Show SEO friendly url</b> - possible values on, off default value off<br />";
	echo "sef=off (on,off,v2) <ul><li><a href=\"" . $script_basepath . "?sef=on" . "\" >" . $script_basepath . "?sef=on" . "</a></li>
	<li><a href=\"" . $script_basepath . "?sef=v2" . "\" >" . $script_basepath . "?sef=v2" . "</a> (used in prestashop versions 1.4.1 or higher)</li></ul>";
	
	echo "<b>Get prices in specified currency</b> - possible values USD,EUR etc. <br />";
	echo "currency=DEFAULT_CURRENCY <a href=\"" . $script_basepath . "?currency=EUR" . "\" >" . $script_basepath . "?currency=EUR" . "</a><br /><br />";
	
	echo "<b>Get prices in specified currency id</b> - possible values 1,2 etc. <br />";
	echo "currency_id=DEFAULT_CURRENCY_ID <a href=\"" . $script_basepath . "?currency_id=1" . "\" >" . $script_basepath . "?currency_id=1" . "</a><br /><br />";

	echo "<b>Get texts in specified language code</b> - possible values en,ro etc. <br />";
	echo "language=DEFAULT_LANGUAGE_CODE <a href=\"" . $script_basepath . "?language=en" . "\" >" . $script_basepath . "?language=en" . "</a><br /><br />";
	
	echo "<b>Get texts in specified language id</b> - possible values 1,2 etc. <br />";
	echo "language_id=DEFAULT_LANGUAGE_ID <a href=\"" . $script_basepath . "?language_id=1" . "\" >" . $script_basepath . "?language_id=1" . "</a><br /><br />";
	
	echo "<b>Get feed paginated</b> - possible values 1,2,..  etc. <br />";
	echo "pg=PAGE <a href=\"" . $script_basepath . "?pg=1" . "\" >" . $script_basepath . "?pg=1" . "</a><br />";
	echo "pg=PAGE&limit=PAGE_SIZE <a href=\"" . $script_basepath . "?pg=1&limit=100" . "\" >" . $script_basepath . "?pg=1&limit=100" . "</a><br /><br />";
	
	echo "<b>Limit displayed products</b> - possible values integer <br />";
	echo "limit=no_limit <a href=\"" . $script_basepath . "?limit=10" . "\" >" . $script_basepath . "?limit=10" . "</a><br /><br />";

	echo "<b>Use cookies</b> - possible values on, off default value on - used to rewrite language and currency cookies with selected values when displaing in certain language or currency<br />";
	echo "cookies=on (on,off) <a href=\"" . $script_basepath . "?cookies=off" . "\" >" . $script_basepath . "?cookies=off" . "</a><br /><br />";
	
	echo "<b>Use default categories</b> - use products default category possible values on, off default value off<br />";
	echo "default_cat=off (on,off) <a href=\"" . $script_basepath . "?default_cat=on" . "\" >" . $script_basepath . "?default_cat=on" . "</a><br /><br />";
	
	echo "<b>Use full categories</b> - use products full category possible values on, off default value off<br />";
	echo "full_cat=off (on,off) <a href=\"" . $script_basepath . "?full_cat=on" . "\" >" . $script_basepath . "?full_cat=on" . "</a><br /><br />";
	
	echo "<b>Use full categories path</b> - use products full category(most detailed cat, greatest level depth category) path possible values on, off default value off<br />";
	echo "full_cat_path=off (on,off) <a href=\"" . $script_basepath . "?full_cat_path=on" . "\" >" . $script_basepath . "?full_cat_path=on" . "</a><br /><br />";
	
	echo "<b>Display currency code</b> - force the display of certain currency code possible values USD,EUR etc. <br />";
	echo "display_currency=DEFAULT_CURRENCY <a href=\"" . $script_basepath . "?display_currency=EUR" . "\" >" . $script_basepath . "?display_currency=EUR" . "</a><br /><br />";
	
	echo "<b>Force compatibility version</b> - possible values 140, 150 etc. <br />";
	echo "version=PS_INSTALL_VERSION <a href=\"" . $script_basepath . "?version=140" . "\" >" . $script_basepath . "?version=140" . "</a><br />";
	echo "version=PS_INSTALL_VERSION <a href=\"" . $script_basepath . "?version=150" . "\" >" . $script_basepath . "?version=150" . "</a><br /><br />";

	echo "<b>Display product combinations</b> - possible values on, off default value on<br />";
	echo "combinations=off (on,off) <a href=\"" . $script_basepath . "?combinations=on" . "\" >" . $script_basepath . "?combinations=on" . "</a><br /><br />";
	
	echo "<b>Display product attributess</b> - possible values on, off default value off<br />";
	echo "attribute=off (on,off) <a href=\"" . $script_basepath . "?attribute=on" . "\" >" . $script_basepath . "?attribute=on" . "</a><br /><br />";
	
	echo "<b>Error reporting </b> - display errors -  values on, off default value on<br />";
	echo "errors=on (on,off) <a href=\"" . $script_basepath . "?errors=off" . "\" >" . $script_basepath . "?errors=off" . "</a><br /><br />";

	echo "<br />";
	
	exit;
	
}

##### Extract options from database ################################################

// Get prestashop version 
$ps_installed_version = _PS_VERSION_;
$ps_version = ($version > 0) ? $version : (int)substr(str_replace(".", "", $ps_installed_version), 0, 3);

// Get list of stores
if ( (isset($_GET['show_stores']) && ($_GET['show_stores'] == 'on')) || (@$_GET['mode'] == "debug")) {
	$script_basepath = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
	$shops = Shop::getShops(true, null, false);

	foreach ($shops as $i=>$v) {
		print $i . " : " . $v['name'] . " <br /> URI: " . $v['uri'] . "<br />domain: " . $v['domain'] . "<br /><a href=\"" . $script_basepath . "?shop_id="  . $i . "\" >" . $script_basepath . "?shop_id=" . $i . "</a><br /><br />";
	}
	exit;
}

// Get default currency
$default_currency_id = ($configuration['PS_CURRENCY_DEFAULT'] > 0) ? $configuration['PS_CURRENCY_DEFAULT'] : 1;

// Use selected currency id
if ($currency_id > 0) {
	$res_currency = Db::getInstance()->ExecuteS("SELECT * FROM " . _DB_PREFIX_ . "currency WHERE id_currency = '" . addslashes($currency_id) . "'");
	$CURRENCY['id_currency'] = $res_currency[0]['id_currency'];
	$CURRENCY['iso_code'] = $res_currency[0]['iso_code'];
	$CURRENCY['conversion_rate'] = $res_currency[0]['conversion_rate'];
	unset($res_currency);
}
elseif ($currency != "") {
	// Use selected currency code
	$res_currency = Db::getInstance()->ExecuteS("SELECT * FROM " . _DB_PREFIX_ . "currency WHERE iso_code = '" . addslashes($currency) . "' AND deleted = 0");
	$CURRENCY['id_currency'] = $res_currency[0]['id_currency'];
	$CURRENCY['iso_code'] = $res_currency[0]['iso_code'];
	$CURRENCY['conversion_rate'] = $res_currency[0]['conversion_rate'];
	$CURRENCY['conversion_rate'] = ($CURRENCY['conversion_rate'] > 0) ? $CURRENCY['conversion_rate'] : 1;
}
else {
	$res_currency = Db::getInstance()->ExecuteS("SELECT * FROM " . _DB_PREFIX_ . "currency WHERE id_currency = '" . addslashes($default_currency_id) . "'");
	$CURRENCY['id_currency'] = $res_currency[0]['id_currency'];
	$CURRENCY['iso_code'] = $res_currency[0]['iso_code'];
	$CURRENCY['conversion_rate'] = 1;
	
	unset($res_default_currency);
	unset($res_currency);
}

$CURRENCY['iso_code'] = (trim(strtolower($CURRENCY['iso_code'])) == "lei" || trim(strtolower($CURRENCY['iso_code'])) == "ro") ? "RON" : $CURRENCY['iso_code'];

// Force displayed currency
$datafeed_currency = ($display_currency != "") ? $display_currency : $CURRENCY['iso_code'];

// Get lang id
if ($language_id > 0) {
	// Set the main language
	$main_language = $language_id;
}
elseif ($language_code != "") {

	// Detect specified  language ID
	$res_language_id = Db::getInstance()->ExecuteS("SELECT id_lang FROM " . _DB_PREFIX_ . "lang WHERE iso_code = '" . addslashes($language_code) . "'");

	// Set the main language
	$main_language = $res_language_id[0]['id_lang'];
	$main_language_code = $language_code;
}
else {
	// Detect default  language ID
	$main_language = ($configuration['PS_LANG_DEFAULT'] > 0) ? $configuration['PS_LANG_DEFAULT'] : 1;
	
	$default_lang = $main_language;
}

// Detect specified  language ID
$language_code_res = Db::getInstance()->ExecuteS("SELECT iso_code FROM " . _DB_PREFIX_ . "lang WHERE id_lang = '" . addslashes($main_language) . "'");
// Get main language_code
$main_language_code = $language_code_res[0]['iso_code'];

// Rewrite cookies with selected values
if ($cookies == "on") {
	// Set main language
	$cookie->id_lang = $main_language;
	// Rewrite currency cookie with selected value
	$cookie->id_currency = $default_currency_id;
}

// Get category array
if ($full_cat == "on") {

	if ($ps_version >= 150) {
		$CAT_ARR = array();

		$queryt = 'SELECT cp.`id_category`, cl.`name`, cl.`link_rewrite`, c.id_parent, c.level_depth FROM `'._DB_PREFIX_.'category_product` cp
			LEFT JOIN `'._DB_PREFIX_.'category` c ON (c.id_category = cp.id_category)
			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (cp.`id_category` = cl.`id_category`'.Shop::addSqlRestrictionOnLang('cl').')
			'.Shop::addSqlAssociation('category', 'c').'
			WHERE cl.`id_lang` = '.(int)$main_language;

		$rrr = Db::getInstance()->query($queryt);
		while ($field = Db::getInstance()->nextRow($rrr)) {
			$CAT_ARR[$field['id_category']] = $field;
		}
	}
	elseif ($ps_version < 150) {
		$CAT_ARR = array();
		// Build categories array
		$cats = Category::getCategories($main_language, true, false);
		if (is_array($cats) && sizeof($cats) > 0) {
			foreach ($cats AS $i=>$v) {
				$CAT_ARR[$v['id_category']]['id_category'] = $v['id_category'];
				$CAT_ARR[$v['id_category']]['id_parent'] = $v['id_parent'];
				$CAT_ARR[$v['id_category']]['level_depth'] = $v['level_depth'];
				$CAT_ARR[$v['id_category']]['name'] = $v['name'];
			}
			unset($cats);
		}
	}
}

// Add shopping cart
if ($add_shipping == "on") {
	global $cart;
	$cart->add();
}

$add_limit = "";
if (isset($_GET['pg']) && @$_GET['pg'] > 0) {
	$_step = ($limit > 0) ? $limit : 1000;
	$_start = ($_GET['pg'] - 1) * $_step;
	$_start = ($_start >= 0) ? $_start : 0;
	$add_limit = " LIMIT " . $_start . "," . $_step;
}

$available_for_order_cond = ($available_for_order == "on" && $ps_version >= 140) ? " AND p.available_for_order=1" : ""; 

if ($default_cat == "on") {
	// Use default prod cat
}
elseif ($ps_version >= 150) {
	$JOIN_PROD_CAT = "LEFT JOIN " . _DB_PREFIX_ . "category_product cp ON cp.id_product = p.id_product
	LEFT JOIN " . _DB_PREFIX_ . "category c ON cp.id_category = c.id_category
	" . Shop::addSqlAssociation('category', 'c') . "
	LEFT JOIN `"._DB_PREFIX_."category_lang` cl ON (cp.`id_category` = cl.`id_category`" . Shop::addSqlRestrictionOnLang('cl') . ")
	LEFT JOIN " . _DB_PREFIX_ . "category_group cg ON cp.id_category = cg.id_category";
	
	$cat_prod_fields = ", cp.id_category";
	$cat_prod_cond = "AND c.active=1 AND cg.id_group=1";
	$cat_prod_aux = ($full_cat_path == "on") ? ", c.level_depth DESC" : ", id_category DESC";
}
else {
	$JOIN_PROD_CAT = "LEFT JOIN " . _DB_PREFIX_ . "category_product ON " . _DB_PREFIX_ . "category_product.id_product = p.id_product
	LEFT JOIN " . _DB_PREFIX_ . "category c ON " . _DB_PREFIX_ . "category_product.id_category = c.id_category
	LEFT JOIN " . _DB_PREFIX_ . "category_group cg ON " . _DB_PREFIX_ . "category_product.id_category = cg.id_category";
	
	$cat_prod_fields = ", " . _DB_PREFIX_ . "category_product.id_category";
	$cat_prod_cond = "AND c.active=1 AND cg.id_group=1";
	$cat_prod_aux = ", id_category DESC";
}

// Debuging
if (isset($_GET['debug'])) {
	// Get prod stats
	$q_res = "SELECT COUNT(if(p.active='1', 1, NULL)) AS active_products, COUNT(p.id_product) AS total_products " . " FROM " . _DB_PREFIX_ . "product p";
	$stats_arr = array();
	if ($ps_version >= 150) { 
		// Extract product id, category id
		$res_prod_stats = Db::getInstance()->ExecuteS($q_res);
		$stats_arr = $res_prod_stats[0];
	}
	else {
		$res_prod_stats = mysql_fetch_assoc(mysql_query($q_res));
		$stats_arr = $res_prod_stats;
	}
	
	echo "Active products: " . $stats_arr['active_products'] . "\n";
	echo "Total products: " . $stats_arr['total_products'] . "\n\n";
	// Init prod count
	$cnt_prod = 0;
	$add_limit = " LIMIT 0,10";

}

######################################################################


##### Extract products from database ###############################################

if ($ps_version >= 150) {
	$q = "SELECT p.id_product" . $cat_prod_fields . "  
	FROM " . _DB_PREFIX_ . "product p
	" . Shop::addSqlAssociation('product', 'p') . "
	" . $JOIN_PROD_CAT . "
	WHERE p.active=1 " . $cat_prod_cond . $available_for_order_cond . "
	AND product_shop.`visibility` IN ('both', 'catalog') AND product_shop.`active` = 1 
	ORDER BY p.id_product ASC" . $cat_prod_aux . $add_limit;
	
}
else {
	$q = "SELECT p.id_product" . $cat_prod_fields . "  
	FROM " . _DB_PREFIX_ . "product p
	" . $JOIN_PROD_CAT . "
	WHERE p.active=1 " . $cat_prod_cond . $available_for_order_cond . "

	ORDER BY p.id_product ASC" . $cat_prod_aux . $add_limit;
}

if ($ps_version >= 150) { 
	// Extract product id, category id
	$r = Db::getInstance()->query($q);
}
else {
	$r = @mysql_query($q);
}

###################################################################


##### Print product data ####################################################

$current_id = 0;
$prod_count = 0;

if ($ps_version >= 150) { 

	while ($field = Db::getInstance()->nextRow($r)) {

		// If we've sent this one, skip the rest - this is to ensure that we do not get duplicate products
		$prod_id = $field['id_product'];
		if ($current_id == $prod_id) {
			continue;
		}	
		else {
			$current_id = $prod_id;
		}
		
		// Get product data
		$product = new Product(intval($field['id_product']), true, intval($main_language));

		// Show only on stock products
		if ($enable_stock_management == 1 && $on_stock_only == "on" && $product->quantity == 0) {
			continue;
		}
		
		// Show combinations
		if ($show_combinations == "on") {
		
			// Build attributes combinaisons
			$COMBINAISONS = $product->getAttributeCombinations(intval($main_language));
			$COMB_GROUPS = array();
			
			if (is_array($COMBINAISONS)) {
				// Get combination images
				$COMBINATION_IMAGES = $product->getCombinationImages((int)($main_language));
				$COMB_ARR = array();
				if ($show_attribute == "on") {
					// Initialize variables
					$ATTRIBUTE = array();
					$ATTR = array();

					// Extract attributes
					$ATTRIBUTE = $product->getFrontFeaturesStatic($main_language, $prod_id);
					// Build array with attributes
					foreach($ATTRIBUTE as $i=>$v) {
						$ATTR[$v['name']] = $v['value'];
					}
				}
				foreach ($COMBINAISONS AS $k => $combination) {
				
					//$price_to_convert = Tools::convertPrice($combination['price'], $CURRENCY['id_currency']);
					//$price = Tools::displayPrice($price_to_convert, $CURRENCY['id_currency']);
				
					// Show only on stock products
					if ($enable_stock_management == 1 && $on_stock_only == "on" && $combination['quantity'] == 0) {
						continue;
					}
				
					$COMB_ARR[$combination['id_product_attribute']]['id_product_attribute'] = $combination['id_product_attribute'];
					$COMB_ARR[$combination['id_product_attribute']]['attributes'][] = array($combination['group_name'], $combination['attribute_name'], $combination['id_attribute']);
					$COMB_ARR[$combination['id_product_attribute']]['wholesale_price'] = $combination['wholesale_price'];
					$COMB_ARR[$combination['id_product_attribute']]['price'] = $combination['price'];
					$COMB_ARR[$combination['id_product_attribute']]['weight'] = $combination['weight'];
					$COMB_ARR[$combination['id_product_attribute']]['reference'] = $combination['reference'];
					$COMB_ARR[$combination['id_product_attribute']]['supplier_reference'] = $combination['supplier_reference'];
					$COMB_ARR[$combination['id_product_attribute']]['ean13'] = $combination['ean13'];
					$COMB_ARR[$combination['id_product_attribute']]['upc'] = $combination['upc'];
					$COMB_ARR[$combination['id_product_attribute']]['quantity'] = $combination['quantity'];
					$COMB_ARR[$combination['id_product_attribute']]['id_image'] = isset($COMBINATION_IMAGES[$combination['id_product_attribute']][0]['id_image']) ? $COMBINATION_IMAGES[$combination['id_product_attribute']][0]['id_image'] : 0;
					$COMB_ARR[$combination['id_product_attribute']]['default_on'] = $combination['default_on'];
					$COMB_ARR[$combination['id_product_attribute']]['attr'] = json_encode($ATTR);
					if ($combination['is_color_group'])
						$COMB_GROUPS[$combination['id_attribute_group']] = $combination['group_name'];
				}
			}

		}
	
		// For links
		$link = new Link();

		$item = array();
		$item['product_id'] = $product->id;
		$item['manufacturer_name'] = $product->manufacturer_name;
		$item['name'] = $product->name;
		$item['link_rewrite'] = $product->link_rewrite;
		$item['image'] = $product->getCover($product->id);
		$item['mpn'] = $product->reference;
		
		// Get default image url
		$item['image_url'] = $link->getImageLink($product->link_rewrite, $item['product_id'] . "-" . $item['image']['id_image'], $image_size);
		
		$item['price'] = floatval(trim($product->getPrice(true, NULL, 2)));
		$item['price'] = $item['price'] * $CURRENCY['conversion_rate'];
		
		// Add VAT to prices
		if ($add_vat == "on") {
			$item['price'] = $item['price'] * $vat_value;
		}
		
		// Use category
		$use_cat = ($default_cat == "on") ? $product->id_category_default : $field['id_category'];
		
		// We have a product cat id or default cat
		if ($use_cat > 0) {
			if ($full_cat == "on") {
				$item['cat_name'] = smfeed_get_full_cat($use_cat, $CAT_ARR);
			}
			else {
				$item['cat_name'] = Tools::getPath($use_cat, '');
			}
		}
		else {
			// Get product category
			$category = new Category($product->id_category_default, intval($main_language));
			$item['cat_name'] = (isset($category->id) AND $category->id) ? Tools::getPath($category->id, '') : Tools::getPath($product->id_category_default, '');
		}
		
		// Show attributes
		if ($show_attribute == "on") {
			// Initialize variables
			$ATTRIBUTE = array();
			$ATTR = array();
			
			// Extract attributes
			$ATTRIBUTE = $product->getFrontFeaturesStatic($main_language, $prod_id);
			// Build array with attributes
			foreach($ATTRIBUTE as $i=>$v) {
				$ATTR[$v['name']] = $v['value'];
			}
			
			// If show combinations is off then show variants as attributes
			if ($show_combinations == "off") {
				// Extract product variants
				$COMBINAISONS = $product->getAttributeCombinations(intval($main_language));
				// Init variants array
				$COMB = array();
				if (is_array($COMBINAISONS)) {
					foreach($COMBINAISONS as $i=>$v) {
						$COMB[$v['group_name']][$v['attribute_name']] = $v['attribute_name'];
					}
				}
				if (is_array($COMB)) {
					foreach($COMB as $i=>$v) {
						$ATTR[$i] = join(",", array_values($v));
					}
				}
			}
			$tmp = array();
			foreach ($ATTR as $i=>$v) {
				$tmp[] = $i . ": " . $v;
			}
			$item['attr'] = join("; ", array_values($tmp));
		}
		
		// Clean product name
		$item['cat_name'] = trim(smfeed_html_to_text($item['cat_name']));
		$item['name'] = str_replace("\n", "", strip_tags($item['name']));
		
		$item['description'] = "";
		
		// Limit description size
		if ($show_description == "limited" || $show_description == "full_limited" || $show_description > 0) {
			
			$item['description'] = ($show_description == "limited" || $show_description > 0) ? $product->description : ($product->description_short . " " . $product->description);
			$item['description'] = ($show_description == "full_limited") ? $product->description_short . " " . $product->description : $product->description;
			
			$limit_size = ($show_description > 0) ? $show_description : "300";
			
			$item['description'] = substr($item['description'], 0, $limit_size + 300);
			$item['description'] = smfeed_replace_not_in_tags("\n", "<BR />", $item['description']);
			$item['description'] = str_replace("\n", " ", $item['description']);
			$item['description'] = str_replace("\r", "", $item['description']);
			$item['description'] = strip_tags($item['description']);
			$item['description'] = substr($item['description'], 0, $limit_size);
		}
		else {
			if ($show_description == "on") {
				// Get description
				$item['description'] = $product->description;
			}
			elseif ($show_description == "short") {
				// Get short description
				$item['description'] = $product->description_short;
			}
			elseif ($show_description == "full") {
				// Get full description (shortdesc + desc)
				$item['description'] = $product->description_short . " " . $product->description;
			}

			$item['description'] = smfeed_replace_not_in_tags("\n", "<BR />", $item['description']);
			$item['description'] = str_replace("\n", " ", $item['description']);
			$item['description'] = str_replace("\r", "", $item['description']);
		}
		
		// Clean product names and descriptions (separators)
		if ($datafeed_separator == "\t") {
			$item['name'] = str_replace("\t", " ", strip_tags($item['name']));
			$item['description'] = str_replace("\t", " ", $item['description']);
			$item['cat_name'] = str_replace("\t", ">", $item['cat_name']);
		}
		elseif ($datafeed_separator == "|") {
			$item['name'] = str_replace("|", " ", strip_tags($item['name']));
			$item['description'] = str_replace("|", " ", $item['description']);
			$item['cat_name'] = str_replace("|", ">", $item['cat_name']);
		}
		else {
			print "Incorrect columns separator.";
			exit;			
		}

		$use_lang_code = ($default_lang == $main_language) ? "" : $main_language_code;
		
		if ($sef == "off") {
			$item['prod_url'] = smfeed_get_product_url($item['product_id'], "", $base_dir, $use_lang_code);
		}
		elseif ($sef == "v2") {
			if ((int)$configuration['PS_REWRITING_SETTINGS']) {
				$rewrite_infos = Product::getUrlRewriteInformations((int)$field['id_product']);
				
				foreach ($rewrite_infos AS $infos) {
					if ($infos['id_lang'] == $main_language){
						$item['prod_url'] = $link->getProductLink((int)$field['id_product'], $infos['link_rewrite'], $infos['category_rewrite'], $infos['ean13'], (int)$infos['id_lang']);
					}
				}
			}
		}
		else {
			$item['prod_url'] = smfeed_get_product_url($item['product_id'], $item['link_rewrite'], $base_dir, $use_lang_code);		
		}
		
		// Add GA Tagging parameters to url
		if ($add_tagging == "on") {
			$and_param = (preg_match("/\?/", $item['prod_url'])) ? "&" : "?";
			$item['prod_url'] = $item['prod_url'] . $and_param . $tagging_params;
		}
		
		if ($item['image']['id_image'] > 0 && $show_image != "off") {

			// Get default image
			if ($show_image == "on")  {
				// Add http:// if missing
				$item['image_url'] = (strpos($item['image_url'], "http://") === false) ? "http://" . $item['image_url'] : $item['image_url'];
			
				$item['prod_image'] = $item['image_url'];
			}
			else {
				$item['prod_image'] = smfeed_get_product_image($item['product_id'], $item['image']['id_image'], $base_image_dir, $sef, $show_image, $base_dir, $item['link_rewrite']);		
			}
			
		}
		else {
			$item['prod_image'] = "";
		}
		
		/*// Display availability 		
		if ($availability == "on") {
			$row['availability'] = ($product->checkQty(1) == 1 && $product->available_for_order == 1) ? "In stock" : "Out of stock";
		}
		else { 
			$row['availability'] = "";
		}*/
		
		// Display availability
		if ($availability == "on") {
			// Stock managent is activated
			if ($enable_stock_management == 1) {
			
				// Product is in stock and is available for order
				if ($product->quantity > 0 && $product->available_for_order == 1) {
					$row['availability'] = "In stock";
				}
				// Product is not in stock and is available for backorder
				elseif (($product->out_of_stock == 1 || ($allow_out_of_stock_ordering == 1 && $product->out_of_stock == 2)) && $product->available_for_order == 1) {
					//$row['availability'] = ($product->available_later != "") ? $product->available_later : "Available for order";
					$row['availability'] = "Available for order";
				}
				else {
					$row['availability'] = "Out of stock";
				}
			}
			else {
				// Show available for backorder message
				if ($product->quantity == 0 && ($product->out_of_stock == 1 || ($allow_out_of_stock_ordering == 1 && $product->out_of_stock == 2)) && $product->available_for_order == 1) {
					//$row['availability'] = ($product->available_later != "") ? $product->available_later : "Available for order";
					$row['availability'] = "Available for order";
				}
				else {
					// All products are in stock
					$row['availability'] = "In stock";
				}
			}
		}
		else {
			$row['availability'] = "";
		}

		// Display shipping
		if ($add_shipping == "on") {
			// Add product to cart
			$updateQuantity = $cart->updateQty((int)(1), (int)($product->id), (int)($idProductAttribute), $customizationId, Tools::getValue('op', 'up'));
			
			// Get shipping value
			//$row['shipping_value'] = $cart->getOrderShippingCost();
			$row['shipping_value'] = $cart->getPackageShippingCost();
			
			$row['shipping_value'] = $row['shipping_value'] * $CURRENCY['conversion_rate'];

			// Remove product from cart
			$updateQuantity = $cart->updateQty((int)(0), (int)($product->id), (int)($idProductAttribute), $customizationId, Tools::getValue('op', 'up'));
		}
		elseif($add_shipping == "v2") {
			$row['shipping_value'] = $product->additional_shipping_cost + $configuration['PS_SHIPPING_HANDLING'];
		}
		else {
			$row['shipping_value'] = "";
		}
		
		// Display GTIN 
		$item['gtin'] = ($gtin == "on") ? (($product->upc != "") ? $product->upc : (($product->ean13 != "") ? $product->ean13 : "")) : "";

		// Required fields are: category name, merchant product ID, product name, product URL, product price
		// For the product model you should only use the manufacturer code, ISBN code or UPC code - If you are not sure about a field please leave it empty
		
		// Output the datafeed content
		// Category, Manufacturer, Model, ProdCode, ProdName, ProdDescription, ProdURL, ImageURL, Price, Currency, Shipping value, Availability, GTIN (UPC/EAN/ISBN) 
		
		// Display combination products
		if ($show_combinations == "on" && is_array($COMB_ARR) && sizeof($COMB_ARR) > 0) {
			foreach ($COMB_ARR AS $id_comb => $comb) {
				$atr_name = array();
				$atr_link = "";
				if (is_array($comb['attributes'])) {
					foreach ($comb['attributes'] AS $id_attr => $attr) {
						$atr_name[] = $attr[1];
						$atr_link .= "/" . str_replace(" ", "_", strtolower($attr[0])) . "-" . str_replace(" ", "_", strtolower($attr[1]));
					}
				}
			
				$comb['name'] = $item['name'] . " " . join(", ", $atr_name);
				// Get combination price
				$comb['price'] = floatval(trim($product->getPrice(true, $id_comb, 2)));
				$comb['price'] = $comb['price'] * $CURRENCY['conversion_rate'];
				
				// Add VAT to prices
				if ($add_vat == "on") {
					$comb['price'] = $comb['price'] * $vat_value;
				}
				
				// Build combination url
				$comb['prod_url'] = ($atr_link != "") ? $item['prod_url'] . "#" . $atr_link : $item['prod_url'];
				
				$comb['mpn'] = ($comb['reference'] != "") ? $comb['reference'] : $item['mpn'];
				$comb['product_id'] = $item['product_id'] . "_" . $id_comb;
				$comb['prod_image'] = ($comb['id_image'] > 0) ? $link->getImageLink($product->link_rewrite, $item['product_id'] . "-" . $comb['id_image'], $image_size) : $item['image_url'];
				// Add http:// if missing
				$comb['prod_image'] = (strpos($comb['prod_image'], "http://") === false) ? "http://" . $comb['prod_image'] : $comb['prod_image'];
				
				$comb['gtin'] = ($gtin == "on") ? (($comb['upc'] != "") ? $comb['upc'] : (($comb['ean13'] != "") ? $comb['ean13'] : "")) : "";
				$comb['gtin'] = ($comb['gtin'] != "") ? $comb['gtin'] : $item['gtin'];
				
				// Display availability
				if ($availability == "on") {
					// Stock managent is activated
					if ($enable_stock_management == 1) {
					
						// Product is in stock and is available for order
						if ($product->quantity > 0 && $product->available_for_order == 1 && $comb['quantity'] > 0) {
							$comb['availability'] = "In stock";
						}
						// Product is not in stock and is available for backorder
						elseif (($product->out_of_stock == 1 || ($allow_out_of_stock_ordering == 1 && $product->out_of_stock == 2)) && $product->available_for_order == 1) {
							//$row['availability'] = ($product->available_later != "") ? $product->available_later : "Available for order";
							$comb['availability'] = "Available for order";
						}
						else {
							$comb['availability'] = "Out of stock";
						}
					}
					else {
						// Show available for backorder message
						if ($comb['quantity'] == 0 && ($product->out_of_stock == 1 || ($allow_out_of_stock_ordering == 1 && $product->out_of_stock == 2)) && $product->available_for_order == 1) {
							//$row['availability'] = ($product->available_later != "") ? $product->available_later : "Available for order";
							$comb['availability'] = "Available for order";
						}
						else {
							// All products are in stock
							$comb['availability'] = "In stock";
						}
					}
				}
				else {
					$comb['availability'] = "";
				}
				
				// Output the datafeed content
				// Category, Manufacturer, Model, ProdCode, ProdName, ProdDescription, ProdURL, ImageURL, Price, Currency, Shipping value, Availability, GTIN (UPC/EAN/ISBN) 
				print
				$item['cat_name'] . $datafeed_separator . 
				$item['manufacturer_name'] . $datafeed_separator . 
				$comb['mpn'] . $datafeed_separator . 
				$comb['product_id'] . $datafeed_separator . 
				$comb['name'] . $datafeed_separator . 
				$item['description'] . $datafeed_separator . 
				$comb['prod_url'] . $datafeed_separator . 
				$comb['prod_image'] . $datafeed_separator .
				$comb['price'] . $datafeed_separator . 
				$datafeed_currency . $datafeed_separator . 
				$row['shipping_value'] . $datafeed_separator .
				$comb['availability'] . $datafeed_separator . (($show_attribute == "on") ? $comb['gtin'] . $datafeed_separator .
				$comb['attr'] : $comb['gtin'])
				 . "\n";
				
			}
			
		}
		// Display simple default products
		else {
		
			// Output the datafeed content
			// Category, Manufacturer, Model, ProdCode, ProdName, ProdDescription, ProdURL, ImageURL, Price, Currency, Shipping value, Availability, GTIN (UPC/EAN/ISBN) 
			print  
			$item['cat_name'] . $datafeed_separator . 
			$item['manufacturer_name'] . $datafeed_separator . 
			$item['mpn'] . $datafeed_separator . 
			$item['product_id'] . $datafeed_separator . 
			$item['name'] . $datafeed_separator . 
			$item['description'] . $datafeed_separator . 
			$item['prod_url'] . $datafeed_separator . 
			$item['prod_image'] . $datafeed_separator .
			$item['price'] . $datafeed_separator . 
			$datafeed_currency . $datafeed_separator . 
			$row['shipping_value'] . $datafeed_separator .
			$row['availability'] . $datafeed_separator . (($show_attribute == "on") ? $item['gtin'] . $datafeed_separator . 
			$item['attr'] : $item['gtin'])
			 . "\n";
		}

		// Debuging
		if (isset($_GET['debug'])) {
			$cnt_prod ++;
			echo $cnt_prod . "."; 
			echo "\t" . number_format(microtime(true) - $time, 3) . "s \n"; 
			$time = microtime(true);
			echo "\t" . number_format(memory_get_usage()/1048576, 3) . "Mb\n\n";
			echo "memory limit " . ini_get("memory_limit") . "\n";
			echo "max_execution_time " . ini_get("max_execution_time") . "\n\n";
		}
		
		$prod_count ++;
		
		// Limit displayed products
		if ($limit > 0 && $prod_count >= $limit && !isset($_GET['pg'])) {
			break;
		}
		
	}
}
else {
	while ($field = mysql_fetch_assoc($r)) {

		// If we've sent this one, skip the rest - this is to ensure that we do not get duplicate products
		$prod_id = $field['id_product'];
		if ($current_id == $prod_id) {
			continue;
		}
		else {
			$current_id = $prod_id;
		}
		
		// Get product data
		$product = new Product(intval($field['id_product']), true, intval($main_language));

		// Show only on stock products
		if ($enable_stock_management == 1 && $on_stock_only == "on" && $product->quantity == 0) {
			continue;
		}
		// Show combinations
		if ($show_combinations == "on") {
		
			// Build attributes combinaisons
			$COMBINAISONS = $product->getAttributeCombinaisons(intval($main_language));
			$COMB_GROUPS = array();
			
			if (is_array($COMBINAISONS)) {
			
				// Show only on stock products
				if ($enable_stock_management == 1 && $on_stock_only == "on" && $combination['quantity'] == 0) {
					continue;
				}
				// Get combination images
				$COMBINATION_IMAGES = $product->getCombinationImages((int)($main_language));
				$COMB_ARR = array();
				foreach ($COMBINAISONS AS $k => $combinaison) {
					$COMB_ARR[$combinaison['id_product_attribute']]['wholesale_price'] = $combinaison['wholesale_price'];
					$COMB_ARR[$combinaison['id_product_attribute']]['price'] = $combinaison['price'];
					$COMB_ARR[$combinaison['id_product_attribute']]['weight'] = $combinaison['weight'];
					$COMB_ARR[$combinaison['id_product_attribute']]['reference'] = $combinaison['reference'];
					$COMB_ARR[$combinaison['id_product_attribute']]['supplier_reference'] = $combinaison['supplier_reference'];
					$COMB_ARR[$combinaison['id_product_attribute']]['ean13'] = $combinaison['ean13'];
					$COMB_ARR[$combinaison['id_product_attribute']]['upc'] = $combinaison['upc'];
					$COMB_ARR[$combinaison['id_product_attribute']]['quantity'] = $combinaison['quantity'];
					$COMB_ARR[$combinaison['id_product_attribute']]['id_image'] = isset($COMBINATION_IMAGES[$combinaison['id_product_attribute']][0]['id_image']) ? $COMBINATION_IMAGES[$combinaison['id_product_attribute']][0]['id_image'] : 0;
					$COMB_ARR[$combinaison['id_product_attribute']]['default_on'] = $combinaison['default_on'];
					$COMB_ARR[$combinaison['id_product_attribute']]['ecotax'] = $combinaison['ecotax'];
					$COMB_ARR[$combinaison['id_product_attribute']]['attributes'][] = array($combinaison['group_name'], $combinaison['attribute_name'], $combinaison['id_attribute']);
					if ($combinaison['is_color_group'])
						$COMB_GROUPS[$combinaison['id_attribute_group']] = $combinaison['group_name'];
				}
			}

		}
	
		// For links
		$link = new Link();

		$item = array();
		$item['product_id'] = $product->id;
		$item['manufacturer_name'] = $product->manufacturer_name;
		$item['name'] = $product->name;
		$item['link_rewrite'] = $product->link_rewrite;
		$item['image'] = $product->getCover($product->id);
		$item['mpn'] = $product->reference;
		
		// Get default image url
		$item['image_url'] = $link->getImageLink($product->link_rewrite, $item['product_id'] . "-" . $item['image']['id_image'], $image_size);
		
		$item['price'] = floatval(trim($product->getPrice(true, NULL, 2)));
		$item['price'] = $item['price'] * $CURRENCY['conversion_rate'];
		
		// Add VAT to prices
		if ($add_vat == "on") {
			$item['price'] = $item['price'] * $vat_value;
		}
		
		if ($full_cat == "on") {
			$use_cat = ($default_cat == "on") ? $product->id_category_default : $field['id_category'];
			$item['cat_name'] = smfeed_get_full_cat($use_cat, $CAT_ARR);
		}
		elseif ($default_cat == "on") {
			$item['cat_name'] = Tools::getPath($product->id_category_default, '');
		}
		elseif ($field['id_category'] > 0) {
			$item['cat_name'] = Tools::getPath($field['id_category'], '');
		}
		else {
			$category = new Category($product->id_category_default, intval($main_language));
			$item['cat_name'] = (isset($category->id) AND $category->id) ? Tools::getPath($category->id, '') : Tools::getPath($product->id_category_default, '');
		}
		$item['cat_name'] = trim(smfeed_html_to_text($item['cat_name']));
		
		// Clean product name (new lines)
		$item['name'] = str_replace("\n", "", strip_tags($item['name']));
		
		$item['description'] = "";
		
		// Limit description size
		if ($show_description == "limited" || $show_description == "full_limited" || $show_description > 0) {
			
			$item['description'] = ($show_description == "limited" || $show_description > 0) ? $product->description : ($product->description_short . " " . $product->description);
			$item['description'] = ($show_description == "full_limited") ? $product->description_short . " " . $product->description : $product->description;
			
			$limit_size = ($show_description > 0) ? $show_description : "300";
			
			$item['description'] = substr($item['description'], 0, $limit_size + 300);
			$item['description'] = smfeed_replace_not_in_tags("\n", "<BR />", $item['description']);
			$item['description'] = str_replace("\n", " ", $item['description']);
			$item['description'] = str_replace("\r", "", $item['description']);
			$item['description'] = strip_tags($item['description']);
			$item['description'] = substr($item['description'], 0, $limit_size);
		}
		else {
			if ($show_description == "on") {
				// Get description
				$item['description'] = $product->description;
			}
			elseif ($show_description == "short") {
				// Get short description
				$item['description'] = $product->description_short;
			}
			elseif ($show_description == "full") {
				// Get full description (shortdesc + desc)
				$item['description'] = $product->description_short . " " . $product->description;
			}

			$item['description'] = smfeed_replace_not_in_tags("\n", "<BR />", $item['description']);
			$item['description'] = str_replace("\n", " ", $item['description']);
			$item['description'] = str_replace("\r", "", $item['description']);
		}
		
		// Clean product names and descriptions (separators)
		if ($datafeed_separator == "\t") {
			$item['name'] = str_replace("\t", " ", strip_tags($item['name']));
			$item['description'] = str_replace("\t", " ", $item['description']);
			$item['cat_name'] = str_replace("\t", ">", $item['cat_name']);
		}
		elseif ($datafeed_separator == "|") {
			$item['name'] = str_replace("|", " ", strip_tags($item['name']));
			$item['description'] = str_replace("|", " ", $item['description']);
			$item['cat_name'] = str_replace("|", ">", $item['cat_name']);
		}
		else {
			print "Incorrect columns separator.";
			exit;			
		}

		$use_lang_code = ($default_lang == $main_language) ? "" : $main_language_code;
		
		if ($sef == "off") {
			$item['prod_url'] = smfeed_get_product_url($item['product_id'], "", $base_dir, $use_lang_code);
		}
		elseif ($sef == "v2") {
			if ((int)$configuration['PS_REWRITING_SETTINGS']) {
				$rewrite_infos = Product::getUrlRewriteInformations((int)$field['id_product']);
				
				foreach ($rewrite_infos AS $infos) {
					if ($infos['id_lang'] == $main_language){
						$item['prod_url'] = $link->getProductLink((int)$field['id_product'], $infos['link_rewrite'], $infos['category_rewrite'], $infos['ean13'], (int)$infos['id_lang']);
					}
				}
			}
		}
		else {
			$item['prod_url'] = smfeed_get_product_url($item['product_id'], $item['link_rewrite'], $base_dir, $use_lang_code);		
		}
		
		// Add GA Tagging parameters to url
		if ($add_tagging == "on") {
			$and_param = (preg_match("/\?/", $item['prod_url'])) ? "&" : "?";
			$item['prod_url'] = $item['prod_url'] . $and_param . $tagging_params;
		}
		
		if ($item['image']['id_image'] > 0 && $show_image != "off") {

			// Get default image
			if ($show_image == "on")  {
				// Add host to image url if needed
				$item['image_url'] = (substr($item['image_url'], 0, 1) == "/") ? "http://" . $_SERVER['HTTP_HOST'] . $item['image_url'] : $item['image_url'];
				// Add http:// if missing
				$item['image_url'] = (strpos($item['image_url'], "http://") === false) ? "http://" . $item['image_url'] : $item['image_url'] ;
				
				$item['prod_image'] = $item['image_url'];
			}
			else {
				$item['prod_image'] = smfeed_get_product_image($item['product_id'], $item['image']['id_image'], $base_image_dir, $sef, $show_image, $base_dir, $item['link_rewrite']);		
			}
			
		}
		else {
			$item['prod_image'] = "";
		}
		
		/*// Display availability 		
		if ($availability == "on") {
			$row['availability'] = ($product->checkQty(1) == 1 && $product->available_for_order == 1) ? "In stock" : "Out of stock";
		}
		else { 
			$row['availability'] = "";
		}*/

		// Display availability
		if ($availability == "on") {
			if (isset($product->available_for_order)) {
				if ($enable_stock_management == 1) {
					// Product is in stock and is available for order
					if ($product->quantity > 0 && $product->available_for_order == 1) {
						$row['availability'] = "In stock";
					}
					// Product is not in stock and is available for backorder
					elseif (($product->out_of_stock == 1 || ($allow_out_of_stock_ordering == 1 && $product->out_of_stock == 2)) && $product->available_for_order == 1) {
						$row['availability'] = "Available for order";
					}
					else {
						$row['availability'] = "Out of stock";
					}
				}
				else {
					// Show available for backorder message
					if ($product->quantity == 0 && ($product->out_of_stock == 1 || ($allow_out_of_stock_ordering == 1 && $product->out_of_stock == 2)) && (isset($product->available_for_order) && $product->available_for_order == 1)) {
						//$row['availability'] = ($product->available_later != "") ? $product->available_later : "Available for order";
						$row['availability'] = "Available for order";
					}
					else {
						// All products are in stock
						$row['availability'] = "In stock";
					}
				}
			}
			else {
				// Stock managent is activated
				if ($enable_stock_management == 1) {
					// Product is in stock and is available for order
					if ($product->quantity > 0) {
						$row['availability'] = "In stock";
					}
					// Product is not in stock and is available for backorder
					elseif ($product->out_of_stock == 1 || ($allow_out_of_stock_ordering == 1 && $product->out_of_stock == 2)) {
						$row['availability'] = "Available for order";
					}
					else {
						$row['availability'] = "Out of stock";
					}
				}
				else {
					// Show available for backorder message
					if ($product->quantity == 0 && ($product->out_of_stock == 1 || ($allow_out_of_stock_ordering == 1 && $product->out_of_stock == 2))) {
						$row['availability'] = "Available for order";
					}
					else {
						// All products are in stock
						$row['availability'] = "In stock";
					}
				}
			}
		}
		else {
			$row['availability'] = "";
		}

		// Display shipping
		if ($add_shipping == "on") {
			// Add product to cart
			$updateQuantity = $cart->updateQty((int)(1), (int)($product->id), (int)($idProductAttribute), $customizationId, Tools::getValue('op', 'up'));

			// Get shipping value
			$row['shipping_value'] = $cart->getOrderShippingCost();
			$row['shipping_value'] = $row['shipping_value'] * $CURRENCY['conversion_rate'];

			// Remove product from cart
			$updateQuantity = $cart->updateQty((int)(0), (int)($product->id), (int)($idProductAttribute), $customizationId, Tools::getValue('op', 'up'));
		}
		else {
			$row['shipping_value'] = "";
		}
		
		// Display GTIN 
		$item['gtin'] = ($gtin == "on") ? (($product->upc != "") ? $product->upc : (($product->ean13 != "") ? $product->ean13 : "")) : "";
	
		// Required fields are: category name, merchant product ID, product name, product URL, product price
		// For the product model you should only use the manufacturer code, ISBN code or UPC code - If you are not sure about a field please leave it empty

		// Display combination products
		if ($show_combinations == "on" && is_array($COMB_ARR) && sizeof($COMB_ARR) > 0) {
			foreach ($COMB_ARR AS $id_comb => $comb) {
				$atr_name = array();
				if (is_array($comb['attributes'])) {
					foreach ($comb['attributes'] AS $id_attr => $attr) {
						$atr_name[] = $attr[1];
					}
				}
			
				$comb['name'] = $item['name'] . " " . join(", ", $atr_name);
				// Get combination price
				$comb['price'] = floatval(trim($product->getPrice(true, $id_comb, 2)));
				$comb['price'] = $comb['price'] * $CURRENCY['conversion_rate'];
				
				// Add VAT to prices
				if ($add_vat == "on") {
					$comb['price'] = $comb['price'] * $vat_value;
				}
				
				$comb['mpn'] = ($comb['reference'] != "") ? $comb['reference'] : $item['mpn'];
				$comb['product_id'] = $item['product_id'] . "_" . $id_comb;
				$comb['prod_image'] = ($comb['id_image'] > 0) ? $link->getImageLink($product->link_rewrite, $item['product_id'] . "-" . $comb['id_image'], $image_size) : $item['image_url'];
				$comb['gtin'] = ($gtin == "on") ? (($comb['upc'] != "") ? $comb['upc'] : (($comb['ean13'] != "") ? $comb['ean13'] : "")) : "";
				$comb['gtin'] = ($comb['gtin'] != "") ? $comb['gtin'] : $item['gtin'];
				
				// Display availability
				if ($availability == "on") {
					// Available_for_order mark is set
					if (isset($product->available_for_order)) {
						// Stock managent is activated
						if ($enable_stock_management == 1) {
							// Product is in stock and is available for order
							if ($product->quantity > 0 && (isset($product->available_for_order) && $product->available_for_order == 1) && $comb['quantity'] > 0) {
								$comb['availability'] = "In stock";
							}
							// Product is not in stock and is available for backorder
							elseif (($product->out_of_stock == 1 || ($allow_out_of_stock_ordering == 1 && $product->out_of_stock == 2)) && (isset($product->available_for_order) && $product->available_for_order == 1)) {
								//$row['availability'] = ($product->available_later != "") ? $product->available_later : "Available for order";
								$comb['availability'] = "Available for order";
							}
							else {
								$comb['availability'] = "Out of stock";
							}
						}
						else {
							// Show available for backorder message
							if ($comb['quantity'] == 0 && ($product->out_of_stock == 1 || ($allow_out_of_stock_ordering == 1 && $product->out_of_stock == 2)) && (isset($product->available_for_order) && $product->available_for_order == 1)) {
								//$row['availability'] = ($product->available_later != "") ? $product->available_later : "Available for order";
								$comb['availability'] = "Available for order";
							}
							else {
								// All products are in stock
								$comb['availability'] = "In stock";
							}
						}
					}
					else {
						// Stock managent is activated
						if ($enable_stock_management == 1) {
						
							// Product is in stock and is available for order
							if ($product->quantity > 0 && $comb['quantity'] > 0) {
								$comb['availability'] = "In stock";
							}
							// Product is not in stock and is available for backorder
							elseif ($product->out_of_stock == 1 || ($allow_out_of_stock_ordering == 1 && $product->out_of_stock == 2)) {
								//$row['availability'] = ($product->available_later != "") ? $product->available_later : "Available for order";
								$comb['availability'] = "Available for order";
							}
							else {
								$comb['availability'] = "Out of stock";
							}
						}
						else {
							// Show available for backorder message
							if ($comb['quantity'] == 0 && ($product->out_of_stock == 1 || ($allow_out_of_stock_ordering == 1 && $product->out_of_stock == 2))) {
								//$row['availability'] = ($product->available_later != "") ? $product->available_later : "Available for order";
								$comb['availability'] = "Available for order";
							}
							else {
								// All products are in stock
								$comb['availability'] = "In stock";
							}
						}
					}
				}
				else {
					$comb['availability'] = "";
				}
				
				// Output the datafeed content
				// Category, Manufacturer, Model, ProdCode, ProdName, ProdDescription, ProdURL, ImageURL, Price, Currency, Shipping value, Availability, GTIN (UPC/EAN/ISBN) 
				print  
				$item['cat_name'] . $datafeed_separator . 
				$item['manufacturer_name'] . $datafeed_separator . 
				$comb['mpn'] . $datafeed_separator . 
				$comb['product_id'] . $datafeed_separator . 
				$comb['name'] . $datafeed_separator . 
				$item['description'] . $datafeed_separator . 
				$item['prod_url'] . $datafeed_separator . 
				$comb['prod_image'] . $datafeed_separator .
				$comb['price'] . $datafeed_separator . 
				$datafeed_currency . $datafeed_separator . 
				$row['shipping_value'] . $datafeed_separator .
				$comb['availability'] . $datafeed_separator . 
				$comb['gtin'] . "\n";
				
			}
			
		}
		// Display simple default products
		else {
		
			// Output the datafeed content
			// Category, Manufacturer, Model, ProdCode, ProdName, ProdDescription, ProdURL, ImageURL, Price, Currency, Shipping value, Availability, GTIN (UPC/EAN/ISBN) 
			print  
			$item['cat_name'] . $datafeed_separator . 
			$item['manufacturer_name'] . $datafeed_separator . 
			$item['mpn'] . $datafeed_separator . 
			$item['product_id'] . $datafeed_separator . 
			$item['name'] . $datafeed_separator . 
			$item['description'] . $datafeed_separator . 
			$item['prod_url'] . $datafeed_separator . 
			$item['prod_image'] . $datafeed_separator .
			$item['price'] . $datafeed_separator . 
			$datafeed_currency . $datafeed_separator . 
			$row['shipping_value'] . $datafeed_separator .
			$row['availability'] . $datafeed_separator . 
			$item['gtin'] . "\n";
		}
		
		// Debuging
		if (isset($_GET['debug'])) {
			$cnt_prod ++;
			echo $cnt_prod . "."; 
			echo "\t" . number_format(microtime(true) - $time, 3) . "s \n"; 
			$time = microtime(true);
			echo "\t" . number_format(memory_get_usage()/1048576, 3) . "Mb\n\n";
			echo "memory limit " . ini_get("memory_limit") . "\n";
			echo "max_execution_time " . ini_get("max_execution_time") . "\n\n";
		}
		
		$prod_count ++;
		
		// Limit displayed products
		if ($limit > 0 && $prod_count >= $limit && !isset($_GET['pg'])) {
			break;
		}
		
	}
}	

###################################################################

// Debuging
if (isset($_GET['debug'])) {
	echo "\npage loaded in " . number_format(microtime(true) - $time_start, 3) . "s \n"; 
	echo "memory limit " . ini_get("memory_limit") . "\n";
	echo "max_execution_time " . ini_get("max_execution_time") . "\n\n";
}


##### Functions ########################################################

// Function to return the Product URL based on your product ID
function smfeed_get_product_url($prod_id, $link_rewrite, $base_dir, $use_lang){	
	
	global $ps_version;
	
	$url_lang = ($use_lang != "") ? "lang-" . $use_lang . "/" : "";
	
	if ($link_rewrite != "") {		
		$url = $base_dir . $url_lang . $prod_id . "-" . $link_rewrite . ".html";
	}
	else {
		if ($ps_version >= 150) {
			$url = $base_dir . $url_lang . "index.php?id_product=" . $prod_id . "&controller=product";
		}
		else {
			$url = $base_dir . $url_lang . "product.php?id_product=" . $prod_id;
		}
	}
	
	return $url;
	
}

// Function to return the Product Image based on your product image or optionally Product ID
function smfeed_get_product_image($prod_id, $prod_image, $base_image_dir, $sef, $show_image, $base_dir, $link_rewrite){
	
	if ($show_image == "v1") {
		return $base_dir . $prod_id . "-" . $prod_image . "/" . $link_rewrite . ".jpg";
	}
	elseif ($show_image == "v2") {
		return $base_dir . $prod_image . "/" . $link_rewrite . ".jpg";
	}
	elseif ($show_image == "v3") {
		$tmp = str_split($prod_image);
		$image_folder = join("/", $tmp);
		return $base_image_dir . $image_folder . "/" . $prod_image . ".jpg";
	}
	elseif ($sef == "v2") {
		return $base_dir . $prod_id . "-" . $prod_image . "/" . $link_rewrite . ".jpg";
	}
	else {
		// default
		return $base_image_dir . $prod_id . "-" . $prod_image . ".jpg";
	}
	
}

// Function to get category with full path
function smfeed_get_full_cat($cat_id, $CATEGORY_ARR) {

	$item_arr = $CATEGORY_ARR[$cat_id];
	$cat_name = $item_arr['name'];
	
	while (sizeof($CATEGORY_ARR[$item_arr['id_parent']]) > 0 && is_array($CATEGORY_ARR[$item_arr['id_parent']]) ) {
		// Skip root and home categories
		if ($CATEGORY_ARR[$item_arr['id_parent']]['level_depth'] > 0) {
			$cat_name = $CATEGORY_ARR[$item_arr['id_parent']]['name'] . " > " . $cat_name;
		}
		$item_arr = $CATEGORY_ARR[$item_arr['id_parent']];
	}
	
	// Strip html from category name
	$cat_name = smfeed_html_to_text($cat_name);
	
	return $cat_name;
}

function smfeed_html_to_text($string){

	$search = array (
		"'<script[^>]*?>.*?</script>'si",  // Strip out javascript
		"'<[\/\!]*?[^<>]*?>'si",  // Strip out html tags
		"'([\r\n])[\s]+'",  // Strip out white space
		"'&(quot|#34);'i",  // Replace html entities
		"'&(amp|#38);'i",
		"'&(lt|#60);'i",
		"'&(gt|#62);'i",
		"'&(nbsp|#160);'i",
		"'&(iexcl|#161);'i",
		"'&(cent|#162);'i",
		"'&(pound|#163);'i",
		"'&(copy|#169);'i",
		"'&(reg|#174);'i",
		"'&#8482;'i",
		"'&#149;'i",
		"'&#151;'i"
		);  // evaluate as php
	
	$replace = array (
		" ",
		" ",
		"\\1",
		"\"",
		"&",
		"<",
		">",
		" ",
		"&iexcl;",
		"&cent;",
		"&pound;",
		"&copy;",
		"&reg;",
		"<sup><small>TM</small></sup>",
		"&bull;",
		"-",
		);
	
	$text = preg_replace ($search, $replace, $string);
	return $text;
	
}

function smfeed_clean_description($string){

	$search = array (
		"'<html>'i",
		"'</html>'i",
		"'<body>'i",
		"'</body>'i",
		"'<head>.*?</head>'si",
		"'<!DOCTYPE[^>]*?>'si"
		); 

	$replace = array (
		"",
		"",
		"",
		"",
		"",
		""
		); 
		
	$text = preg_replace ($search, $replace, $string);
	return $text;

}

function smfeed_replace_not_in_tags($find_str, $replace_str, $string) {
	
	$find = array($find_str);
	$replace = array($replace_str);	
	preg_match_all('#[^>]+(?=<)|[^>]+$#', $string, $matches, PREG_SET_ORDER);	
	foreach ($matches as $val) {	
		if (trim($val[0]) != "") {
			$string = str_replace($val[0], str_replace($find, $replace, $val[0]), $string);
		}
	}	
	return $string;
}

###################################################################

?>