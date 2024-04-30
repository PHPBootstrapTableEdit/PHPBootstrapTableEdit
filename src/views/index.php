<?php

$last_column = count($result[0] ?? []) - 1;

// query string for table header, keep _page=0, it means pagination turned off
$exclude = ['_order_by', '_desc'];
if (($_GET['_page'] ?? '') !== '0') {
    $exclude[] = '_page';
}

$qs = $this->get_query_string($exclude);

// render table header
$i = 0;
$thead = '';
$desc_inverse = intval(!$desc);
foreach (($result[0] ?? array()) as $field => $x) {

    // no text on the last edit link column
    if ($i++ == $last_column && $field == $this->identity_name) {
        $field = "";
    }

    $thead .= "<th><a href='?_order_by=$i&_desc=$desc_inverse&$qs'>" . $this->c($this->get_label($field, 'index')) . "</th>";
}

$qs = $this->get_query_string([$this->identity_name]);
if (strlen($qs) > 0) {
    $qs .= "&";
}

// render table body
$tbody = '';
foreach ($result as $row) {

    $i = 0;
    $td = "";
    $id = $row[$this->identity_name] ?? null;
    foreach ($row as $field => $value) {

        // 3 options: edit link, user defined function, or plain text
        if ($i++ == $last_column && $field == $this->identity_name) {
            $str = "<a href='?_action=edit&$qs{$this->identity_name}=$value' class='edit'>{$this->i18n['edit']}</a>";
        } elseif (isset($this->index[$field]['function'])) {
            $str = call_user_func($this->index[$field]['function'], ['field' => $field, 'value' => $value, 'id' => $id]);
        } else {
            $str = $this->c($value, $this->ellipse_at);
        }

        $td .= "<td>$str</td>";
    }

    // highlight active row
    $class = '';
    if (($_GET[$this->identity_name] ?? '') == ($row[$this->identity_name] ?? 0)) {
        $class = $this->css['index_active'];
    }

    $tbody .= "<tr class='$class'>$td</tr>\n";
}

// pagination and search layout
$qs = $this->get_query_string(["page"]);
$html = "
<div class='row d-flex flex-wrap'>
    <div class='col-md-1 mb-3'>
        <a href='?_action=add&$qs' class='btn btn-primary index_add'>{$this->i18n['add']}</a>
    </div>
    <div class='col-md-7 mb-1'>
        $pagination
    </div>
    <div class='col-md-4 mb-3 text-end'>
        <div class='input-group'>
            <input type='text' class='form-control form-inline index_search_input' name='_search' value='$search'>
            <button type='submit' class='btn btn-primary index_search'>{$this->i18n['search']}</button>
        </div>
    </div>
</div>
";

// append table
if (strlen($tbody) > 0) {
    $html .= "
    <table class='table {$this->css['index_table']}'>
        <thead>$thead</thead>
        <tbody>$tbody</tbody>
    </table>
    ";
}

// bottom pagination
if (strlen($pagination) > 0) {
    $html .= "
    <div class='container'>
        <div class='row'>
            <div class='col'>
                $pagination
            </div>
        </div>
    </div>
    ";
}

// append 'no data' message
if (strlen($tbody) == 0) {
    $html .= $this->alert($this->i18n['no_records']);
}

$alert = '';
if ($success == 3) {
    $alert = $this->alert($this->i18n['delete_success'], 'alert-success');
}

// query_string_carry - bring data along with the search form
$carry = '';
foreach ($this->query_string_carry as $key) {

    $val = $_GET[$key] ?? null;

    if (is_string($val)) {
        $carry .= "<input type='hidden' name='$key' value='" . $this->c($val) . "'>\n";
    }

    if (is_array($val)) {
        foreach ($val as $v) {
            $carry .= "<input type='hidden' name='{$key}[]' value='" . $this->c($v) . "'>\n";
        }
    }

}

echo "
<form id='pbte' class='pbte_index'>
$alert
$html
$carry
</form>
";
