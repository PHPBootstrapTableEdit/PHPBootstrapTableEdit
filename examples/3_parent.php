<?php
declare(strict_types=1); 
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <style>
    body { display: block; width: 90%; margin: 20px auto; }
    #pbte.pbte_add_edit { max-width: 800px; margin: 0 auto; }
    #pbte.pbte_index a.index_add { white-space: nowrap; }
    </style>
</head>
<body>

<h3>Countries</h3>

<?php

$dbh = new PDO('sqlite:example.db');
//$dbh = new PDO("mysql:host=127.0.0.1;dbname=example;", "example", "example");
//$dbh = new PDO("pgsql:host=127.0.0.1;dbname=example;", "example", "example");

require "../src/PHPBootstrapTableEdit.php";
//require "../vendor/autoload.php";

$o = new PHPBootstrapTableEdit\PHPBootstrapTableEdit($dbh);

$o->table_name = 'countries';
$o->identity_name = 'id';

// change 'Add' link into 'Add Country'
$o->i18n['add'] = 'Add Country';

// define opening index table, to render the [edit] link, the last column must be the identity id
$o->index_sql = "
select c.title as country,
       c.code,
       (select count(1) from provinces p where p.country = c.code ) as provinces,
       c.id
from   countries c
where  ( coalesce(c.title, '') like :search or coalesce(c.code, '') like :search )
order by c.title
";

// named parameters index_sql
$o->index_sql_param[':search'] = '%' . trim($_REQUEST['_search'] ?? '') . '%';

// define fields on the edit form
$o->edit_sql = "select id, title, code from countries where id = :id";
$o->edit_sql_param[':id'] = intval($_POST['id'] ?? $_GET['id'] ?? 0);

// non floating input style
$o->floating = false;

// define field attributes
$o->edit['id']['disabled'] = true;
$o->edit['title']['required'] = true;
$o->edit['code']['required'] = true;
$o->edit['code']['pattern'] = '[a-zA-Z]{2}';
$o->edit['code']['title'] = 'Country code must be 2 letters';
$o->edit['code']['size'] = '2';
$o->edit['code']['maxlength'] = '2';
$o->edit['code']['style'] = "width:100px";

// 12 column layout - colspan = 4 creates a three column layout
$o->edit['id']['colspan'] = '12';
$o->edit['title']['colspan'] = '12';
$o->edit['code']['colspan'] = '12';

// copy all 'edit' setting into 'add', the add form is almost the same settings
$o->add = $o->edit;
$o->add_sql = $o->edit_sql;
$o->add_sql_param = $o->edit_sql_param;

// this validation function will used for updates too, not just new insert
$o->on_insert = function () {

    global $o;
    $id = $_POST['id'] ?? null;
    $code = $_POST['code'] ?? '';
    if (strlen($code) != 2) {
        return "Error, missing county code";
    }

    $sql = "select 1 from countries where code = :code and (id != :id or :id is null)";
    $result = $o->query($sql, [':code' => $code, ':id' => $id]);

    if (count($result) > 0) {
        return "'$code' already exists";
    }

    $_POST['code'] = strtoupper($_POST['code']);

};

// use the same validation function for update
$o->on_update = $o->on_insert;

// assure there are no children before deleting
$o->on_delete = function () {

    global $o;
    $id = $_POST['id'] ?? null;
    $sql = "select 1 from provinces where country = (select code from countries where id = :id)";
    $result = $o->query($sql, [':id' => $id]);
    $cnt = count($result);

    if (count($result) > 0) {
        return "Please delete the provinces ($cnt) before removing the country";
    }

};

// call the controller
$o->run();

// append link to child page
// probably cleaner and easier if we did this in jQuery instead
if (($_GET['_action'] ?? '') == 'edit') {
    $id = $_GET['id'] ?? 0;
    $sql = "select code from countries where id = :id";
    $result = $o->query($sql, [':id' => $id]);
    $code = $o->c($result[0]['code']);
    echo "<div class='text-center mt-2'><a href='3_parent_child.php?country=$code' class='mx-2 btn btn-secondary'>Manage Provinces</a></div>";
}

?>

</body>
