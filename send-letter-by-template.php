<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Отправка письма по шаблону"); ?><?$APPLICATION->IncludeComponent(
	"send.letter.by.template",
	"",
	Array(
		"IBLOCK" => "6",
		"MAIL_FROM" => "Рассылка писем",
		"MAIL_TEXT" => "Добрый день. Прикладываю файл.",
		"MAIL_TITLE" => "Письмо по шаблону",
		"PAGER_NUMBER" => "10",
		"PL_EMAIL" => "#email#",
		"PL_FIO" => "#ФИО#"
	)
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>