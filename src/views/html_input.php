<?php

$label = "<label for='f_$field' class='form-label'>" . $this->c($label) . "</label>";
$html = "<input type='$type' name='$field' value='" .$this->c($value) . "' class='form-control $class' id='f_$field' $attr>";
