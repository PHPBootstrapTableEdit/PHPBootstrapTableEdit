<?php

// two styles of links, image with thumbnail and non-image
$html = "<a href='$url' id='f_{$field}_link' class='input-group-text file_link' style='background-image: url($url); background-size: cover; min-width: 100px; color: transparent;' target='_blank'>$ext</a>";
if (!$is_image) {
    $html = "<a href='$url' id='f_{$field}_link' class='input-group-text' target='_blank'>$ext</a>";
}

// append 'accept' attribute if it's not already there
if (strpos($attr, "accept='") === false) {

    if ($is_image) {
        $attr .= " accept='.gif,.png,.jpg' ";
    } else {
        $attr .= " accept='.$ext' ";
    }

}

// trash icon/checkbox to delete existing file
$html .= "<span class='input-group-text'><label class='file_trash' title='{$this->i18n['delete_file']}'><input type='checkbox' class='form-check-input' name='{$field}_delete' value='1'><span></span></label></span>";

// no image, then no extra html needed
if (strlen($value ?? '') == 0) {
    $html = '';
}

$label = "<label for='f_$field' class='form-label'>" . $this->c($label) . "</label>";

if ($this->floating) {
    $html = "<input type='$type' name='$field' class='form-control $class' id='f_$field' $attr>$html";
} else {
    $html = "
    <div class='input-group'>
        <input type='$type' name='$field' class='form-control $class' id='f_$field' $attr>
        $html
    </div>
    ";
}

