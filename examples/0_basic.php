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
    body { display: block; width: 90%; margin: 40px auto; }
    </style>
</head>
<body>


<?php

$dbh = new PDO('sqlite:example.db');
//$dbh = new PDO("mysql:host=127.0.0.1;dbname=example;", "example", "example");
//$dbh = new PDO("pgsql:host=127.0.0.1;dbname=example;", "example", "example");

require "../src/PHPBootstrapTableEdit.php";
//require "../vendor/autoload.php";

$o = new PHPBootstrapTableEdit\PHPBootstrapTableEdit($dbh);

$o->table_name = 'markets';
$o->identity_name = 'id';

// define opening index table, to render the [edit] link, the last column must be the identity id
$o->index_sql = "
select m.title,
       c.title as country,
       m.create_date,
       case when m.is_active = 1 then 'Yes' else '-' end as is_active,
       m.id
from   markets m
left
join   countries c
on     m.country = c.code
where  ( coalesce(m.title, '') like :search or coalesce(c.title, '') like :search )
order by 2 desc
";

// named parameters for search
$o->index_sql_param[':search'] = '%' . trim($_REQUEST['_search'] ?? '') . '%';

// define fields on the edit form
$o->edit_sql = "select title, email, country, create_date, is_active from markets where id = :id";
$o->edit_sql_param[':id'] = intval($_POST['id'] ?? $_GET['id'] ?? 0);

// floating input style
$o->floating = true;

// define fields attributes
$o->edit['title']['required'] = true;
$o->edit['country']['type'] = 'select';
$o->edit['country']['sql'] = 'select code, title from countries'; // define how to populate the select dropdown
$o->edit['create_date']['type'] = 'date';
$o->edit['is_active']['type'] = 'checkbox';

// 12 column layout - colspan = 4 creates a three column layout
$o->edit['title']['colspan'] = '4';
$o->edit['email']['colspan'] = '4';
$o->edit['country']['colspan'] = '4';
$o->edit['is_active']['colspan'] = '4';
$o->edit['create_date']['colspan'] = '4';

// copy all 'edit' setting into 'add', the add form is the same
$o->add = $o->edit;
$o->add_sql = $o->edit_sql;
$o->add_sql_param = $o->edit_sql_param;

// call the controller
$o->run();

?>

</body>
