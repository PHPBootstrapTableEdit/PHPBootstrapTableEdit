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

    /* optional css to render trashcan icon, instead of checkbox, next an uploaded file */
    #pbte label.file_trash input                { display: none; }
    #pbte label.file_trash input         + span { display: block; height: 26px; width: 26px; opacity: .2; cursor: pointer; background-size: cover; background-image: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgZmlsbD0iY3VycmVudENvbG9yIiBjbGFzcz0iYmkgYmktdHJhc2giIHZpZXdCb3g9IjAgMCAxNiAxNiI+CiAgPHBhdGggZD0iTTUuNSA1LjVBLjUuNSAwIDAgMSA2IDZ2NmEuNS41IDAgMCAxLTEgMFY2YS41LjUgMCAwIDEgLjUtLjVtMi41IDBhLjUuNSAwIDAgMSAuNS41djZhLjUuNSAwIDAgMS0xIDBWNmEuNS41IDAgMCAxIC41LS41bTMgLjVhLjUuNSAwIDAgMC0xIDB2NmEuNS41IDAgMCAwIDEgMHoiLz4KICA8cGF0aCBkPSJNMTQuNSAzYTEgMSAwIDAgMS0xIDFIMTN2OWEyIDIgMCAwIDEtMiAySDVhMiAyIDAgMCAxLTItMlY0aC0uNWExIDEgMCAwIDEtMS0xVjJhMSAxIDAgMCAxIDEtMUg2YTEgMSAwIDAgMSAxLTFoMmExIDEgMCAwIDEgMSAxaDMuNWExIDEgMCAwIDEgMSAxek00LjExOCA0IDQgNC4wNTlWMTNhMSAxIDAgMCAwIDEgMWg2YTEgMSAwIDAgMCAxLTFWNC4wNTlMMTEuODgyIDR6TTIuNSAzaDExVjJoLTExeiIvPgo8L3N2Zz4=); }
    #pbte label.file_trash input:checked + span { opacity: 1; }
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
$o->edit_sql = "select title, email, country, photo, create_date, is_active from markets where id = :id";
$o->edit_sql_param[':id'] = intval($_POST['id'] ?? $_GET['id'] ?? 0);

// use floating input style
$o->floating = true;

// define field attributes
$o->edit['title']['required'] = true;

$o->edit['country']['type'] = 'select';
$o->edit['country']['sql'] = 'select code, title from countries'; // define how to populate the select dropdown

$o->edit['photo']['type'] = 'file';
$o->edit['photo']['file_extension'] = 'png'; // all things file_* are library settings, not html attributes
$o->edit['photo']['file_image_crop_or_resize'] = 'crop';
$o->edit['photo']['file_image_width'] = 100;
$o->edit['photo']['file_image_height'] = 100;
$o->edit['photo']['file_path'] = ''; // empty file_path means store the binary in the database, not the filesystem

$o->edit['create_date']['type'] = 'date';

$o->edit['is_active']['type'] = 'checkbox';
$o->edit['is_active']['div_class'] = 'form-switch'; // add class around input to make checkbox look like a switch
$o->edit['is_active']['label'] = 'Active'; // rename the column

// just for demonstration, manually render input and label for the email field
$o->edit['email']['function'] = function ($data) {

    global $o;
    $value = $o->c($data['value']);
    $label = "<label for='f_email' class='form-label'>Email</label>";
    $html = "<input type='email' name='email' value='$value' class='form-control' id='f_email' placeholder='Email'>";

    return [$label, $html];

};

// 12 column layout - colspan = 4 creates a three column layout
$o->edit['title']['colspan'] = 4;
$o->edit['photo']['colspan'] = 4;
$o->edit['email']['colspan'] = 4;
$o->edit['country']['colspan'] = 4;
$o->edit['is_active']['colspan'] = 4;
$o->edit['create_date']['colspan'] = 4;

// copy all 'edit' setting into 'add', the add form is almost the same settings
$o->add = $o->edit;
$o->add_sql = $o->edit_sql;
$o->add_sql_param = $o->edit_sql_param;

// some defaults when adding a new record
$o->add['is_active']['value'] = "1";
$o->add['create_date']['value'] = date('Y-m-d');

// format dates on index table, format here rather than in sql so column sort still works properly
$o->index['create_date']['function'] = function ($data) {

    if (strlen($data['value'] ?? '') == 10) {
        return date('d/m/Y', strtotime($data['value']));
    }

};

// rename is_active to 'Active' on the index opening table
$o->index['is_active']['label'] = 'Active';

// example hooks - this could be used for server side validation
$no_blah = function () {

    if (($_POST['title'] ?? "") == "blah") {

        $text = "Sorry, 'blah' is not allowed as a title";
        $html = "<div style='display: none;'>this could be json for js to read</div>";

        // returning text for alert, and some html
        return ['text' => $text, 'html' => $html];

        // or, just return text
        return $text;
    }

};

$o->on_insert = $no_blah;
$o->on_update = $no_blah;

// call the controller
$o->run();

?>

</body>
