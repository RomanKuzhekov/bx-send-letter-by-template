<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
    "NAME" => GetMessage("SBT_NAME"),
    "DESCRIPTION" => GetMessage("SBT_DESCRIPTION"),
    "PATH" => array(
        "ID" => "service",
        "CHILD" => array(
            "ID" => "subscribe",
            "NAME" => GetMessage("SBT_SERVICE")
        )
    ),
);