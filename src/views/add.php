<?php

$alert = $this->alert($error);

// render fields
$html = '';
$row = $result[0] ?? [];
foreach ($row as $field => $x) {

    // load value attribute, if defined
    $value = $this->add[$field]['value'] ?? '';

    // allow input from get, like query_string_carry elements
    // also, reload input on validation repost
    if (empty($value)) {
        $value = $_POST[$field] ?? $_GET[$field] ?? '';
        if (is_array($value)) {
            $value = json_encode($value);
        }

    }

    // no id in the add form
    if ($field == $this->identity_name) {
        continue;
    }

    $html .= $this->get_input($field, $value, 'add');
}

// form's action helps here, it keeps the same querystring even on insert validation returns
echo "
<form id='pbte' class='pbte_add pbte_add_edit row' method='post' action='?_action=add&{$this->identity_name}=$id&$qs' enctype='multipart/form-data'>
$alert
$html
 <div class='d-flex justify-content-center' id='button_bar'>
    <a href='?id=$id&$qs' class='mx-2 btn btn-secondary'>" . $this->c($this->i18n['back']) . "</a>
    <button type='submit' class='mx-2 btn btn-primary' name='_action' value='insert'>" . $this->c($this->i18n['add_2']) . "</button>
</div>
<input type='hidden' name='{$this->nonce_name}' value='" . $this->c($this->nonce_value) . "'>
</form>
";
