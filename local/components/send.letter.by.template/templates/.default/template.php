<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var array $arParams
 * @var array $arResult
 * */

$APPLICATION->SetTitle("Рассылка писем");
$APPLICATION->SetPageProperty("title", "Отправка писем по шаблону");

if (!empty($arResult['error-prop'])) {
    echo $arResult['error-prop'];
} else {
?>
    <div class="sbt-container">
        <? if (empty($arResult["error"])) { ?>
            <p><a href="?loader" class="sbt-button <?if(!empty($arResult["isLoader"])) echo "sbt-button-active"?>">&#9993; Cоздать рассылку</a></p>

            <? if (!empty($arResult["isLoader"])): ?>
                <form class="sbt-loader" enctype="multipart/form-data" method="post">
                    <p class="sbt-title-block">Форма для загрузки данных</p>

                    <p><i>Формат файлов <b>CSV, TXT</b> - конвертировать в нужный формат можно на сайте - <a href="https://convertio.co/ru/" target="_blank">convertio.co</a></i></p>

                    <p><i>Значения параметров для подстановки из текста шаблона задаются в настройках компонента.</i></p>

                    <? if (!empty($arResult["error-form"])) { ?>
                        <div class="sbt-block-message">
                            <? foreach ($arResult["error-form"] as $error) { ?>
                                <p class='sbt-error'><?=$error?></p>
                            <? } ?>
                        </div>
                    <? } elseif (!empty($arResult["successfully-form"])) { ?>
                        <div class="sbt-block-message">
                            <p class='sbt-successfully'><?=$arResult["successfully-form"]?></p>
                        </div>
                    <? } ?>
                    <p>
                        <label>1. Загрузите шаблон с текстом: <br>
                            <input type="file" name="loader-template" placeholder="Загрузите шаблон" accept=".txt,.csv" required>
                        </label>
                    </p>
                    <p>
                        <label>2. Загрузите список клиентов для рассылки писем. (Пример текста: Email;ФИО) : <br>
                            <input type="file" name="loader-list" placeholder="Загрузите список Email/ФИО" accept=".txt,.csv" required>
                        </label>
                    </p>
                    <input type="submit" value="&#128190; Сохранить" name="loader-save" class="sbt-btn"> &nbsp;&nbsp;
                    <input type="reset" value="&#10006; Очистить форму" class="sbt-btn"> &nbsp;&nbsp;
                    <a href="<?=$_SERVER["SCRIPT_NAME"]?>" class="sbt-btn">Свернуть форму</a>
                </form>
            <? endif; ?>

            <? if ($arResult["dataTable"]): ?>
                <? if (!empty($arResult["successfully-send"])): ?>
                    <div class="sbt-block-message">
                        <p class='sbt-successfully'><?=$arResult["successfully-send"]?></p>
                    </div>
                <? elseif (!empty($arResult["error-send"])): ?>
                    <div class="sbt-block-message">
                        <p class='sbt-error'><?=$arResult["error-send"]?></p>
                    </div>
                <? endif; ?>
                <p class="sbt-title-block">Данные для рассылки</p>
                <table class="sbt-table" border="1" cellpadding="0" cellspacing="0">

                    <tr>
                        <th>Дата создания</th>
                        <th>Email</th>
                        <th>ФИО</th>
                        <th>Файл</th>
                        <th>Дата последней отправки</th>
                        <th></th>
                        <? if ($USER->IsAdmin()): ?>
                            <th>для Админа</th>
                        <? endif; ?>
                    </tr>
                    <? foreach ($arResult["dataTable"] as $idElement => $element) { ?>
                        <tr>
                            <td><?=date_create($element["DATE_CREATE"])->Format('d.m.Y H:i')."<br>";?></td>
                            <td><?=$element["EMAIL"]?></td>
                            <td><?=$element["FIO"]?></td>
                            <td><a href="<?=$element["FILE"]?>" target="_blank">открыть файл</td>
                            <td>
                                <? if (!empty($element["DATE_SEND"])) {
                                    echo date_create($element["DATE_SEND"])->Format('d.m.Y')."<br>";
                                } else {
                                    echo "Не отправлялось";
                                } ?>
                            </td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="table-id" value="<?=$element["ID"]?>">
                                    <input type="hidden" name="table-email" value="<?=$element["EMAIL"]?>">
                                    <input type="hidden" name="table-fio" value="<?=$element["FIO"]?>">
                                    <input type="hidden" name="table-file" value="<?=$element["FILE"]?>">
                                    <input type="submit" value="&#10004; Отправить" name="table-send-mail" class="sbt-btn">
                                </form>
                            </td>

                            <? if ($USER->IsAdmin()): //Если Админ то дать возможность Редактировать\Удалить запись ?>
                                <td>
                                    <p><a href="javascript:void(0);" class="sbt-btn" onclick="<?=$element["Buttons"]["edit"]["edit_element"]["ONCLICK"]?>">Редактировать</a></p>
                                    <p><a href="javascript:void(0);" class="sbt-btn" onclick="<?=$element["Buttons"]["edit"]["delete_element"]["ONCLICK"]?>">Удалить</a></p>
                                </td>
                            <? endif; ?>
                        </tr>
                    <? } ?>
                </table>
            <? endif; ?>

        <? } else { ?>
            <div class="sbt-block-message">
                <p class='sbt-error'><?=$arResult["error"]?></p>
            </div>
        <? } ?>
    </div>
<?php } ?>

