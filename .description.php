<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("FLXMD_BUY_NOW_NAME"),
	"DESCRIPTION" => GetMessage("FLXMD_BUY_NOW_DESCRIPTION"),
	"ICON" => "/images/news_list.gif",
	"SORT" => 5,
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "flxmd",
		"NAME" => GetMessage("FLXMD_COMPONENT_SECTION_NAME"),
		"SORT" => 14,
	),
);

?>
