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
$IBLOCK_ID = 15;

if(!CModule::IncludeModule("iblock")) die('iblock module is not included!');
//делаем выборку из Инфоблока
$arSort = Array("SORT"=>"ASC", "NAME"=>"ASC");
$arFilter = Array("IBLOCK_ID"=>$IBLOCK_ID,"ACTIVE"=>"Y");
$obIBlockResult = CIBlockElement::GetList($arSort, $arFilter);

while($arFields = $obIBlockResult->GetNext()){
  //CIBlockElement::Delete($arFields['ID']);
}

if (($handle = fopen("export_file.csv", "r")) === false) die;

while (($data = fgetcsv($handle)) !== false) {
  if ($row == 1) {
      $row++;
      continue;
  }
  $PROP = [];
  $PROP['IE_NAME'] = $data[1];
  $PROP['IE_ID'] = $data[2];
  $PROP['IE_ACTIVE'] = $data[3];
  $PROP['IC_GROUP0'] = $data[18];

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
