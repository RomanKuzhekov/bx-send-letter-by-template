<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;

class SendLetterByTemplate extends CBitrixComponent {

    /**
     * Проверка наличия модулей требуемых для работы компонента
     * @return bool
     * @throws Exception
     */
    private function _checkModules() {
        if (!Loader::includeModule('iblock')) {
            throw new \Exception('Не загружены модули необходимые для работы модуля');
        }
        return true;
    }


    /**
     * Подготовка параметров компонента
     * @param $arParams
     * @return mixed
     */
    public function onPrepareComponentParams($arParams) {
        // Проверить свойства инфоблока - должны быть созданы EMAIL, FIO, FILE, DATE_SEND
        $arFlagProps = [];
        $resProp = CIBlock::GetProperties($arParams["IBLOCK"], false, Array('ACTIVE' => 'Y'));
        while ($arProp = $resProp->Fetch()) {
            $arFlagProps[] = $arProp['CODE'];
        }
        if (!in_array('EMAIL', $arFlagProps) ||!in_array('FIO', $arFlagProps) || !in_array('FILE', $arFlagProps) || !in_array('DATE_SEND', $arFlagProps)) {
            $this->arResult['error-prop'] = "Проверьте свойства инфоблока, должны быть созданы: EMAIL, FIO, FILE, DATE_SEND";
        }

        return $arParams;
    }

    private function prepareLoader() {
        // Если нажали на "Создать рассылку" - показываем форму
        if (isset($_GET["loader"])) {
            $this->arResult["isLoader"] = true;
        }

        // Если нажали на "Отправить" - отправляем письмо
        if (!empty($_POST["table-send-mail"]) && !empty($_POST["table-id"]) && !empty($_POST["table-email"]) && !empty($_POST["table-fio"]) && !empty($_POST["table-file"]) ) {
            $this->sendLetter();
        }

        // Если отправили данные в форме - сохраняем в БД
        if (!empty($_POST["loader-save"]) && !empty($_FILES["loader-template"])  && !empty($this->arParams["PL_FIO"]) && !empty($_FILES["loader-list"]["tmp_name"])) {
            $this->saveData();
        } elseif (!empty($_POST["loader-save"])) {
            $this->arResult["error-form"][] = "Вы не полностью заполнили форму.";
        }
    }


    /**
     * Сохранение данных из формы
     */
    private function saveData() {
        //получаем готовый текст из загруженного шаблона $_FILES["loader-template"]
        $dataContentTemplate = file_get_contents($_FILES["loader-template"]["tmp_name"]);

        $arList = [];
        $extFileList = mb_strtolower(pathinfo($_FILES["loader-list"]["name"], PATHINFO_EXTENSION));
        if ($extFileList == mb_strtolower("csv") || $extFileList == mb_strtolower("txt")) { //Загрузка списка в формате csv или txt
            //получаем массив  "Email;ФИО"  из загруженного файла $_FILES["loader-list"]
            $fileList = fopen($_FILES["loader-list"]["tmp_name"], 'r');
            if (empty($fileList)) {
                $this->arResult["error-form"][] = "Не удалсь прочитать <b>загруженный список</b>";
            } else {
                while(!feof($fileList)) {
                    $arFileTemp = explode(";", htmlentities(fgets($fileList)));
                    if (!empty($arFileTemp[0]) && check_email("trim($arFileTemp[0])")) {
                        if (!empty($arFileTemp[1])) {
                            $arList[trim($arFileTemp[0])] = trim($arFileTemp[1]);
                        } else {
                            $this->arResult["error-form"][] = "У данного email <b>{$arFileTemp[0]}</b> нет ФИО";
                        }
                    } else {
                        $this->arResult["error-form"][] = "Не валидный email <b>{$arFileTemp[0]}</b>";
                    }
                }
                fclose($fileList);
            }
        }

        //проходим список и заменяем ФИО на введенную фразу
        $arFullData = [];
        foreach ($arList as $email => $fio) {
            $arFullData[$email]["fio"] = $fio;
            $arFullData[$email]["content"] = str_replace(trim(htmlspecialchars($this->arParams["PL_FIO"])), $fio, $dataContentTemplate);
        }

        foreach ($arFullData as $email => $arElement) {
            $PROP = [];
            $PROP["EMAIL"] = $email;
            $PROP["FIO"] = $arElement["fio"];

            //Сохраняем файл
            $extFile = ".".pathinfo($_FILES["loader-template"]["name"], PATHINFO_EXTENSION); //расширение файла
            $htmlNameFile = $_SERVER['DOCUMENT_ROOT'].'/local/components/send.by.template/'.Cutil::translit($email, "ru", array("replace_space"=>"-","replace_other"=>"-")).$extFile;
            file_put_contents($htmlNameFile, $arFullData[$email]["content"]);
            $PROP["FILE"] = CFile::MakeFileArray($htmlNameFile);

            $arLoadElement = Array(
                "IBLOCK_ID"      => $this->arParams["IBLOCK"],
                "PROPERTY_VALUES"=> $PROP,
                "NAME"           => "{$email}. {$arElement["fio"]}",
                "ACTIVE"         => "Y",
            );

            $el = new CIBlockElement;
            if (!$idNewElement = $el->Add($arLoadElement, false, false, false)) {
                $this->arResult["error-form"][] = "Ошибка элемента: ".$el->LAST_ERROR;
            } else {
                $this->arResult["successfully-form"] = "Данные успешно загружены.";
                header("Refresh:5");
            }
            unlink($htmlNameFile); //удаляем созданный файл
        }

    }


    /**
     * Отправка письма
     */
    private function sendLetter() {
        //Отправляем письмо с вложением
        $flagSend = false;
        $filenameSend = $_SERVER['DOCUMENT_ROOT'].$_POST["table-file"];
        $headers  = "Content-type: text/html; charset=utf-8 \r\n";
        $headers .= "From: {$this->arParams["MAIL_FROM"]}\r\n";
        $nameExtFile = "Файл.".pathinfo($_POST["table-file"], PATHINFO_EXTENSION).rand(100, 9999); //Название и расширение файла в письме
        // Отправляем почтовое сообщение
        if (empty($filenameSend)) {
            $flagSend = mail($_POST["table-email"], $this->arParams["MAIL_TITLE"], $this->arParams["MAIL_TEXT"]);
        } else {
            $flagSend = $this->sendMail($_POST["table-email"], $this->arParams["MAIL_TITLE"], $this->arParams["MAIL_TEXT"], $filenameSend, $headers, $nameExtFile);
        }

        if (!empty($flagSend)) {
            // Запишем дату отправления письма
            $arDateSend = [];
            $resDate = CIBlockElement::GetProperty($this->arParams["IBLOCK"], $_POST["table-id"], "sort", "asc", array("CODE" => "DATE_SEND"));
            while ($obDate = $resDate->GetNext()) {
                $arDateSend[] = $obDate['VALUE'];
            }
            $arDateSend[] = date("d.m.Y H:i:s");
            CIBlockElement::SetPropertyValuesEx($_POST["table-id"], false, array("DATE_SEND" => $arDateSend));

            $this->arResult["successfully-send"] = "Письмо успешно отправлено. Почта - <b>{$_POST["table-email"]}</b>. ФИО - <b>{$_POST["table-fio"]}</b>";
        }
    }


    /**
     * Получаем данные из инфоблока - выводим в таблицу
     */
    private function getData() {
        $resDataTable = CIBlockElement::GetList(array("DATE_CREATE" => "DESC"), Array("IBLOCK_ID" => $this->arParams["IBLOCK"], "ACTIVE" => "Y", "!PROPERTY_EMAIL" => false, "!PROPERTY_FIO" => false, "!PROPERTY_FILE" => false), false, Array("nPageSize"=>(int)$this->arParams["PAGER_NUMBER"]), Array("ID", "NAME", "DATE_CREATE", "IBLOCK_ID"));
        while ($obDataTable = $resDataTable->GetNextElement()) {
            $arDataTable = $obDataTable->GetFields();
            $arDataTableProp = $obDataTable->GetProperties();

            $this->arResult["dataTable"][$arDataTable["ID"]]["ID"] = $arDataTable["ID"];
            $this->arResult["dataTable"][$arDataTable["ID"]]["DATE_CREATE"] = $arDataTable["DATE_CREATE"];
            $this->arResult["dataTable"][$arDataTable["ID"]]["EMAIL"] = $arDataTableProp["EMAIL"]["VALUE"];
            $this->arResult["dataTable"][$arDataTable["ID"]]["FIO"] = $arDataTableProp["FIO"]["VALUE"];
            $this->arResult["dataTable"][$arDataTable["ID"]]["FILE"] = CFile::GetPath($arDataTableProp["FILE"]["VALUE"]);
            $this->arResult["dataTable"][$arDataTable["ID"]]["DATE_SEND"] = $arDataTableProp["DATE_SEND"]["VALUE"];
            $this->arResult["dataTable"][$arDataTable["ID"]]["Buttons"] =  CIBlock::GetPanelButtons($arDataTable["IBLOCK_ID"], $arDataTable['ID'], false, false);
        }
    }


    /**
     * Точка входа в компонент
     */
    public function executeComponent() {
        $this->_checkModules();
        $this->prepareLoader();
        $this->getData();

        $this->includeComponentTemplate();
    }


    /**
     * Вспомогательная функция для отправки почтового сообщения с вложением
     */
    private function sendMail($to, $thm, $html, $path, $header, $nameExtFile){
        $fp = fopen($path,"r");
        if (!$fp) {
            $this->arResult["error-send"] = "Файл не может быть прочитан. Скрипт остановлен.";
        } else {
            $file = fread($fp, filesize($path));
            fclose($fp);

            $headers = $header;
            $boundary = "--".md5(uniqid(time())); // генерируем разделитель
            $headers .= "MIME-Version: 1.0\n";
            $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\n";
            $multipart = "--$boundary\n";
            $multipart .= "Content-Type: text/html; charset=utf-8\n";
            $multipart .= "Content-Transfer-Encoding: Quot-Printed\n\n";
            $multipart .= "$html\n\n";
            $message_part = "--$boundary\n";
            $message_part .= "Content-Type: application/octet-stream\n";
            $message_part .= "Content-Transfer-Encoding: base64\n";
            $message_part .= "Content-Disposition: attachment; filename = \"$nameExtFile\"\n\n";
            $message_part .= chunk_split(base64_encode($file))."\n";
            $multipart .= $message_part."--$boundary--\n";

            if (!mail($to, $thm, $multipart, $headers)) {
                $this->arResult["error-send"] = "К сожалению, письмо не отправлено. Возникла ошибка при отправке письма.";
            } else {
                return true;
            }
        }
    }
}