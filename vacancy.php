<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
if (!$USER->IsAdmin()) {
    LocalRedirect('/');
}
\Bitrix\Main\Loader::includeModule('iblock');
$IBLOCK_ID = 25;
$elem = new CIBlockElement;
$handle = fopen($_SERVER['DOCUMENT_ROOT'] . '/upload/vacancy.csv', 'r');

$arPropertyTypeList = ['TYPE', 'ACTIVITY', 'SCHEDULE', 'FIELD', 'OFFICE', 'LOCATION'];
foreach ($arPropertyTypeList as $key => $value)
{
    $arPropertyEnums[$value] = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $IBLOCK_ID, 'CODE' => $value]);
}

if ($handle)
{
    $arData = [];
    while (($data = fgetcsv($handle)) !== false)
    {
        $arData[] = $data;
    }

    for ($i = 1; $i < count($arData); $i++)
    {
        $PROP['REQUIRE'] = explode('• ', $arData[$i][4]); // Требования к соискателю
        $PROP['DUTY'] = explode('• ', $arData[$i][5]); // Основные обязанности
        $PROP['CONDITIONS'] = explode('• ', $arData[$i][6]); // Условия работы

        foreach ($arPropertyEnums as $code => $propertyEnums)
        {
            while($enumFields = $propertyEnums->GetNext())
            {
                switch ($code)
                {
                    case 'TYPE': // Тип вакансии
                        $num = 8;
                        break;
                    case 'ACTIVITY': // Тип занятости
                        $num = 9;
                        break;
                    case 'SCHEDULE': // График работы
                        $num = 10;
                        break;
                    case 'FIELD': // Сфера деятельности
                        $num = 11;
                        break;
                    case 'OFFICE': // Комбинат/Офис
                        $num = 1;
                        break;
                    case 'LOCATION': // Местоположение
                        $num = 2;
                        break;
                }

                $keyEnumField = str_replace(array("\r", "\n", " "), '', $enumFields["VALUE"]);
                if (str_replace(array("\r", "\n", " "), '', $arData[$i][$num]) == $keyEnumField)
                {
                    $PROP[$code] = $enumFields["ID"];
                }
            }
        }

        $PROP['SALARY_VALUE'] = $arData[$i][7]; // Заработная плата (значение)
        $PROP['EMAIL'] = $arData[$i][12]; // Электронная почта (e-mail)
        $PROP['DATE'] = date('d.m.Y');

        $arLoadProductArray = [
            "MODIFIED_BY" => $USER->GetID(),
            "IBLOCK_SECTION_ID" => false,
            "IBLOCK_ID" => $IBLOCK_ID,
            "PROPERTY_VALUES" => $PROP,
            "NAME" => $arData[$i][3],
            "ACTIVE" => end($data) ? 'Y' : 'N',
        ];

        if ($PRODUCT_ID = $elem->Add($arLoadProductArray))
        {
            echo "Добавлен элемент с ID : " . $PRODUCT_ID . "<br>";
        }
        else
        {
            echo "Error: " . $elem->LAST_ERROR . '<br>';
        }
    }
}
