<?php

// wrap message in an alert
$text = $this->c($text);
if (strlen($text) > 0) {
    $text = "<div class='alert $class alert-dismissible fade show' role='alert'>$text</div>";
}

