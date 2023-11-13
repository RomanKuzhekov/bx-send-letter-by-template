<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/**
 * @var string $componentPath
 * @var string $componentName
 * @var array $arCurrentValues
 * */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if( !Loader::includeModule("iblock") ) {
    throw new \Exception('Не загружены модули необходимые для работы компонента');
}

// инфоблоки выбранного типа
$arIBlock = [];
$rsIBlock = CIBlock::GetList(['SORT' => 'ASC'], ['ACTIVE' => 'Y']);
while ($arr = $rsIBlock->Fetch()) {
    $arIBlock[$arr['ID']] = '['.$arr['ID'].'] '.$arr['NAME'];
}
unset($arr, $rsIBlock);

$arComponentParameters = [
    // группы в левой части окна
    "GROUPS" => [
        "SETTINGS" => [
            "NAME" => Loc::getMessage('SETTING_NAME'),
            "SORT" => 550,
        ],
    ],
    "PARAMETERS" => [
        "IBLOCK" => [
            "PARENT" => "SETTINGS",
            "NAME" => Loc::getMessage('IBLOCK_NAME'),
            "TYPE" => "LIST",
            "VALUES" => $arIBlock,
            "REFRESH" => "Y"
        ],
        "PAGER_NUMBER" => [
            "PARENT" => "SETTINGS",
            "NAME" => Loc::getMessage("PAGER_NUMBER"),
            "TYPE" => "STRING",
            "DEFAULT" => "10",
        ],
        "MAIL_FROM" => [
            "PARENT" => "SETTINGS",
            "NAME" => Loc::getMessage("MAIL_FROM"),
            "TYPE" => "STRING",
            "DEFAULT" => "Рассылка писем",
        ],
        "MAIL_TITLE" => [
            "PARENT" => "SETTINGS",
            "NAME" => Loc::getMessage("MAIL_TITLE"),
            "TYPE" => "STRING",
            "DEFAULT" => "Письмо по шаблону",
        ],
        "MAIL_TEXT" => [
            "PARENT" => "SETTINGS",
            "NAME" => Loc::getMessage("MAIL_TEXT"),
            "TYPE" => "STRING",
            "DEFAULT" => "Добрый день. Прикладываю файл.",
        ],
        "PL_FIO" => [
            "PARENT" => "SETTINGS",
            "NAME" => Loc::getMessage("PL_FIO"),
            "TYPE" => "STRING",
            "DEFAULT" => "#ФИО#",
        ],
        "PL_EMAIL" => [
            "PARENT" => "SETTINGS",
            "NAME" => Loc::getMessage("PL_EMAIL"),
            "TYPE" => "STRING",
            "DEFAULT" => "#email#",
        ]
    ]
];