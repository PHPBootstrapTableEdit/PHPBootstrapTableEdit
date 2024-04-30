<?php

$label = "<label for='f_$field' class='form-label'>" . $this->c($label) . "</label>";
$html = "<textarea name='$field' class='form-control $class' id='f_$field' $attr>" . $this->c($value) . "</textarea>";

