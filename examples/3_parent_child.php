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
    #pbte.pbte_add_edit input[name=country] { background-color: #ccc; }
    #pbte.pbte_index a.index_add { white-space: nowrap; }
    </style>
</head>
<body>


<?php

$country = trim(strtoupper($_GET['country'] ?? '')); // parent country code

// assure the incoming country is valid
if (!preg_match('/^[A-Z]{2}$/', $country)) {
    die("Missing or invalid country");
}

echo "<h3><a href='3_parent.php' title='Back to Countries'>Countries</a> » " . htmlentities($country) . " » Provinces</h3>";

$dbh = new PDO('sqlite:example.db');
//$dbh = new PDO("mysql:host=127.0.0.1;dbname=example;", "example", "example");
//$dbh = new PDO("pgsql:host=127.0.0.1;dbname=example;", "example", "example");

require "../src/PHPBootstrapTableEdit.php";
//require "../vendor/autoload.php";

$o = new PHPBootstrapTableEdit\PHPBootstrapTableEdit($dbh);

$o->query_string_carry[] = 'country';
$o->table_name = 'provinces';
$o->identity_name = 'id';

// change 'Add' link into 'Add Province'
$o->i18n['add'] = 'Add Province';

// define opening index table, to render the [edit] link, the last column must be the identity id
$o->index_sql = "
select (select c.title from countries c where c.code = :country limit 1) as country,
       title as province,
       code as abbreviation,
       id
from   provinces p
where  country = :country
and    (coalesce(title, '') like :search or coalesce(code, '') like :search)
order by title
";

// named parameters for search
$o->index_sql_param[':country'] = $country;
$o->index_sql_param[':search'] = '%' . trim($_GET['_search'] ?? '') . '%';

// define fields on the edit form
$o->edit_sql = "
select country,
       title,
       code
from   provinces
where  id = :id
";
$o->edit_sql_param[':id'] = intval($_POST['id'] ?? $_GET['id'] ?? 0);

// non floating input style
$o->floating = false;

// define field attributes
$o->edit['country']['readonly'] = true;
$o->edit['title']['required'] = true;
$o->edit['title']['label'] = 'Province/State';
$o->edit['code']['label'] = 'Abbreviation';
$o->edit['code']['pattern'] = '[a-zA-Z]{2}';
$o->edit['code']['title'] = 'Abbreviation must be 2 letters';
$o->edit['code']['size'] = '2';
$o->edit['code']['maxlength'] = '2';
$o->edit['code']['style'] = "width:100px";

// 12 column layout
// using '4' to create a three column layout
$o->edit['country']['colspan'] = '4';
$o->edit['title']['colspan'] = '4';
$o->edit['code']['colspan'] = '4';

// copy all 'edit' setting into 'add', the add form is almost the same settings
$o->add = $o->edit;
$o->add_sql = $o->edit_sql;
$o->add_sql_param = $o->edit_sql_param;

$o->add['country']['value'] = $country;

// this validation function will used for updates too, not just new insert
$o->on_insert = function () {

    global $o, $country;
    $id = $_POST['id'] ?? null;
    $code = trim($_POST['code'] ?? '');
    $title = trim($_POST['title'] ?? '');

    if (strlen($code) != 2) {
        return "Abbreviation must be 2 letters";
    }

    if (strlen($title) == 0) {
        return "Province required";
    }

    if ($id === null) {
        // check for duplicates before insert
        $sql = "select 1 from provinces where country = :country and (title = :title or code = :code) ";
        $result = $o->query($sql, [':country' => $country, ':title' => $title, ':code' => $code]);
    } else {
        // check for duplicates before update
        $sql = "select 1 from provinces where country = :country and (title = :title or code = :code) and id != :id";
        $result = $o->query($sql, [':country' => $country, ':title' => $title, ':code' => $code, ':id' => $id]);
    }

    if (count($result) > 0) {
        return "Entry already exists";
    }

    $_POST['code'] = strtoupper($_POST['code']);
    $_POST['country'] = strtoupper($_POST['country']);

};

// use the same validation function for update
$o->on_update = $o->on_insert;

// call the controller
$o->run();

?>

</body>
