<?php

$i = -1;
$html = "<label for='f_{$field}_0' class='form-label'>" . $this->c($label) . "</label><br>";
foreach ($result as $row) {

    $i++;
    $val = (string) current($row); // first column is expected to be the non-visible value
    $opt = next($row); // second column is expected to be the visible option

    $checked = '';
    if (is_array($value) && array_search($val, $value, true) !== false) {
        $checked = 'checked';
    }

    if (is_string($value) && $value === $val) {
        $checked = 'checked';
    }

    $html .= "
    <div class='form-check $div_class'>
        <input type='$checkbox_or_radio' name='{$field}{$brackets}' class='form-check-input $class' id='f_{$field}_$i' value='" . $this->c($val) . "' $attr $checked>
        <label for='f_{$field}_$i' class='form-label'>" . $this->c($opt) . "</label>
    </div>
    ";
}
