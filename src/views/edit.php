<?php

// display error if we can't find data
if (count($result) == 0) {
    echo $this->alert($this->i18n['not_found']);
    return;
}

$alert = '';
if ($success == 1) {
    $alert = $this->alert($this->i18n['insert_success'], 'alert-success');
}

if ($success == 2) {
    $alert = $this->alert($this->i18n['update_success'], 'alert-success');
}

if (!empty($error)) {
    $alert = $this->alert($error);
}

// render fields
$html = '';
$row = $result[0] ?? [];
foreach ($row as $field => $value) {

    // reload input last post, if error returned from on_update
    if ($action == 'update') {
        $value = $_POST[$field] ?? '';
        if (is_array($value)) {
            $value = json_encode($value);
        }

    }

    $html .= $this->get_input($field, $value ?? "", 'edit');
}

// 'flex-row-reverse' so enter key submits 'update', and not 'delete'
// form's action helps here, it keeps the same querystring even on insert/update/delete validation returns
echo "
<form id='pbte' class='pbte_edit pbte_add_edit row' method='post' action='?_action=edit&{$this->identity_name}=$id&$qs' enctype='multipart/form-data'>
$alert
$html
 <div class='d-flex flex-row-reverse justify-content-center' id='button_bar'>
    <button type='submit' class='mx-2 btn btn-primary' name='_action' value='update'>{$this->i18n['update']}</button>
    <button type='submit' class='mx-2 btn btn-danger'  name='_action' value='delete'>{$this->i18n['delete']}</button>
    <a href='?id=$id&$qs' class='mx-2 btn btn-secondary'>{$this->i18n['back']}</a>
</div>
<input type='hidden' name='{$this->identity_name}' value='$id'>
<input type='hidden' name='{$this->nonce_name}' value='{$this->nonce_value}'>
</form>
";
