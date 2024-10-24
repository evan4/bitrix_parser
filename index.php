<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
if (!$USER->IsAdmin()) {
  LocalRedirect('/');
}
$APPLICATION->SetTitle("Прямой вызов API Битрикса на странице");
?>
<h1><?=$APPLICATION->ShowTitle()?></h1>
<div class="box">
<?
$IBLOCK_ID = 17;
$arProps = [];

if(!CModule::IncludeModule("iblock")) die('iblock module is not included!');
//делаем выборку из Инфоблока
$arFilter = Array(
  "IBLOCK_ID" => $IBLOCK_ID,
);

$rsProp = CIBlockPropertyEnum::GetList(
  ["SORT" => "ASC", "VALUE" => "ASC"],
  ['IBLOCK_ID' => $IBLOCK_ID]
);

while ($arProp = $rsProp->Fetch()) {
  $key = trim($arProp['VALUE']);
  $arProps[$arProp['PROPERTY_CODE']][$key] = $arProp['ID'];
}

$rsElements = CIBlockElement::GetList([], ['IBLOCK_ID' => $IBLOCK_ID], false, false, ['ID']);
while ($element = $rsElements->GetNext()) {
    CIBlockElement::Delete($element['ID']);
}


if (($handle = fopen("vacancy.csv", "r")) === false){
  echo "<p>Файл csv не найден или нет прав на чтение</p>";
  echo "</div>";
  die;
}

$row = 1;

while (($data = fgetcsv($handle)) !== false) {
  
  if ($row == 1) {
      $row++;
      continue;
  }

  $row++;
  $PROP = [
    'OFFICE' => $data[1],
    'LOCATION' => $data[2],
    'REQUIRE' => $data[4],
    'DUTY' => $data[5],
    'CONDITIONS' => $data[6],
    'SALARY_VALUE' => $data[7],
    'TYPE' => $data[8],
    'ACTIVITY' => $data[9],
    'SCHEDULE' => $data[10],
    'FIELD' => $data[11],
    'EMAIL' => $data[12],
    'SALARY_TYPE' => '',
    'DATE' => date('d.m.Y'),
  ];

  foreach ($PROP as $key => &$value) {
    $value = trim($value);
    $value = str_replace('\n', '', $value);
    if (stripos($value, '•') !== false) {
        $value = explode('•', $value);
        array_splice($value, 0, 1);
        foreach ($value as &$str) {
            $str = trim($str);
        }
    }
  }

  if ($PROP['SALARY_VALUE'] == '-') {
    $PROP['SALARY_VALUE'] = '';
  } elseif ($PROP['SALARY_VALUE'] == 'по договоренности') {
      $PROP['SALARY_VALUE'] = '';
      $PROP['SALARY_TYPE'] = $arProps['SALARY_TYPE']['договорная'];
  } else {
      $arSalary = explode(' ', $PROP['SALARY_VALUE']);
      if ($arSalary[0] == 'от' || $arSalary[0] == 'до') {
          $PROP['SALARY_TYPE'] = $arProps['SALARY_TYPE'][$arSalary[0]];
          array_splice($arSalary, 0, 1);
          $PROP['SALARY_VALUE'] = implode(' ', $arSalary);
      } else {
          $PROP['SALARY_TYPE'] = $arProps['SALARY_TYPE']['='];
      }
  }

  $arLoadProductArray = [
    "MODIFIED_BY" => $USER->GetID(),
    "IBLOCK_SECTION_ID" => false,
    "IBLOCK_ID" => $IBLOCK_ID,
    "PROPERTY_VALUES" => $PROP,
    "NAME" => $data[1],
    "ACTIVE" => end($data) ? 'Y' : 'N',
  ];
  $el = new CIBlockElement;
  if ($PRODUCT_ID = $el->Add($arLoadProductArray)) {
    echo "Добавлен элемент с ID : " . $PRODUCT_ID . "<br>";
  } else {
      echo "Error: " . $el->LAST_ERROR . '<br>';
  }

  $row++;
}

echo "</div>";

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
