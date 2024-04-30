<?php

$html = "<option value=''></option>";
foreach ($result as $row) {

    $val = (string) current($row); // first column is expected to be the non-visible value
    $opt = next($row); // second column is expected to be the visible option

    $selected = '';
    if (is_array($value) && array_search($val, $value, true) !== false) {
        $selected = "selected";
    }

    if (is_string($value) && $value === $val) {
        $selected = "selected";
    }

    $html .= "<option value='" . $this->c($val) . "' $selected>" . $this->c($opt) . "</option>\n";
}

$label = "<label for='f_$field' class='form-label'>" . $this->c($label) . "</label>";

// bootstrap style of datalist
if ($is_datalist) {
    $html = "
    <input id='f_$field' list='f_{$field}_datalist' class='form-control $class' $attr>
    <datalist id='f_{$field}_datalist'>$html</datalist>
    ";
    return [$label, $html];
}

$html = "<select name='{$field}{$brackets}' id='f_$field' class='form-select $class' $multiple $attr>$html</select>";
