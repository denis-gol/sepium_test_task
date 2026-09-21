<?php

require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/src/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

// Упрощённая обезличенная копия реального legacy-обработчика.
function property($property)
{
    $place = '';
    if ($property['place_prop'] != '') {
        $place = '<div class="field-help">' . h($property['place_prop']) . '</div>';
    }

    $idProp = $property['id'];
    $allOption = '';

    if ($property['type_prop'] == '1') {
        $result = '<div class="property-field name_select_rielt" data-property="' . $idProp . '" data-property-id="' . $idProp . '">
            <div class="field-label name">' . h($property['name_prop']) . '</div>
            ' . $place . '
            <input type="text" class="text-input add-inp ag_pole_good" placeholder="' . h($property['name_prop']) . '">
        </div>';
    } elseif ($property['type_prop'] == '2') {
        $answers = db()->query(
            "SELECT * FROM property_answer_s WHERE id_prop = '" . $idProp . "' ORDER BY sort_answer"
        );

        while ($answer = $answers->fetch()) {
            $allOption .= '<option value="' . h($answer['id']) . '">' . h($answer['answer_prop']) . '</option>';
        }

        $result = '<div class="property-field name_select_rielt" data-property="' . $idProp . '" data-property-id="' . $idProp . '">
            <div class="field-label name">' . h($property['name_prop']) . '</div>
            ' . $place . '
            <select class="text-input ag_pole_good">
                <option value="">Не выбрано</option>' . $allOption . '
            </select>
        </div>';
    } elseif ($property['type_prop'] == '3') {
        $answers = db()->query(
            "SELECT * FROM property_answer_s WHERE id_prop = '" . $idProp . "' ORDER BY sort_answer"
        );
        $checkboxes = '';

        while ($answer = $answers->fetch()) {
            $checkboxes .= '<label class="choice line_chek">
                <input type="checkbox">
                <span class="ckeck_param" data-val="' . h($answer['id']) . '">' . h($answer['answer_prop']) . '</span>
            </label>';
        }

        $result = '<div class="property-field name_select_rielt" data-property="' . $idProp . '" data-property-id="' . $idProp . '">
            <div class="field-label name">' . h($property['name_prop']) . '</div>
            ' . $place . '
            <div class="choice-grid checkbox_property ag_pole_good">' . $checkboxes . '</div>
        </div>';
    } elseif ($property['type_prop'] == '4') {
        $result = '<div class="property-field name_select_rielt" data-property="' . $idProp . '" data-property-id="' . $idProp . '">
            <div class="field-label name">' . h($property['name_prop']) . '</div>
            ' . $place . '
            <input type="number" inputmode="decimal" class="text-input add-inp ag_pole_good" placeholder="Числовое значение">
        </div>';
    } else {
        $result = '';
    }

    return $result;
}

$category = isset($_POST['category']) ? $_POST['category'] : array();
$result = '';

// Legacy-алгоритм намеренно содержит несколько связанных ошибок.

// всегда заполняем Общие свойства
$properties = db()->query("SELECT * FROM property_s WHERE (cat_prop = '') ORDER BY sort_prop");
// получаем все свойства одним запросом
$propertyList = $properties->fetchAll();

// добавляем свойства из категорий
if (is_array($category)) {
    foreach ($category as $categoryId) {

        // быстро проверим что такая категория существует
        $isCatExist = db()->prepare("SELECT 1 FROM category_s WHERE ID_category = ? LIMIT 1");
        $isCatExist->execute([$categoryId]);
        if (!$isCatExist->fetchColumn()) {
            continue;
        }

        $properties = db()->prepare(
            "SELECT * FROM property_s WHERE find_in_set(?, cat_prop) > 0 ORDER BY sort_prop"
        );
        $properties->execute([$categoryId]);
        // получаем все свойства одним запросом и складываем в кучу (возможны повторы свойств!!!)
        $propertyList = array_merge($propertyList, $properties->fetchAll());
    }
}

// убираем дубли свойств
$propertyList = (array_column($propertyList, null, 'id'));

// и сортируем
usort($propertyList, function($value1, $value2) {
    if ($value1['sort_prop'] == $value2['sort_prop']) {
        return 0;
    }
    return ($value1['sort_prop'] < $value2['sort_prop']) ? -1 : 1;
});

foreach ($propertyList as $property) {
    $result .= property($property);
}

echo $result === '' ? 'no' : $result;
