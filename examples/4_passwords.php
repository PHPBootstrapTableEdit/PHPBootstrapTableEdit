<?php ob_start();?>
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
error_reporting(E_ALL);

$dbh = new PDO('sqlite:example.db');
//$dbh = new PDO("mysql:host=127.0.0.1;dbname=example;", "example", "example");
//$dbh = new PDO("pgsql:host=127.0.0.1;dbname=example;", "example", "example");

require "../src/PHPBootstrapTableEdit.php";

$o = new PHPBootstrapTableEdit\PHPBootstrapTableEdit($dbh);

$o->table_name = 'markets';
$o->identity_name = 'id';

// define opening index table, to render the [edit] link, the last column must be the identity id
$o->index_sql = "
select m.title,
       m.email,
       m.id
from   markets m
where  ( coalesce(m.title, '') like :search or coalesce(m.email, '') like :search )
order by 2 desc
";

// named parameters for search
$o->index_sql_param[':search'] = '%' . trim($_REQUEST['_search'] ?? '') . '%';

// non floating input style
$o->floating = false;

// define fields on the edit form
// don't show the password hash, note empty string aliased as password
$o->edit_sql = "select title, email, '' as password from markets where id = :id";
$o->edit_sql_param[':id'] = intval($_POST['id'] ?? $_GET['id'] ?? 0);

// define field attributes
$o->edit['title']['required'] = true;

$o->edit['email']['required'] = true;
$o->edit['email']['type'] = 'email';

$o->edit['password']['label'] = 'Reset Password';
$o->edit['password']['type'] = 'password';
$o->edit['password']['pattern'] = '.{4,}';
$o->edit['password']['title'] = 'new passwords must be 4 characters or more';
$o->edit['password']['placeholder'] = 'optional';

// 12 column layout
// using '4' colspan makes a three column layout
$o->edit['title']['colspan'] = '4';
$o->edit['email']['colspan'] = '4';
$o->edit['password']['colspan'] = '4';

// copy all 'edit' setting into 'add', the add form is almost the same settings
$o->add = $o->edit;
$o->add_sql = $o->edit_sql;
$o->add_sql_param = $o->edit_sql_param;

// add is a little different from edit
$o->add['password']['required'] = true;
$o->add['password']['placeholder'] = '';

// when adding a password, just hash it
$o->on_insert = function () {

    $_POST['password'] = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

};

$o->on_update = function () {

    global $o;

    $password = trim($_POST['password'] ?? '');

    // we do have input, hash it
    if (strlen($password) > 0) {
        $_POST['password'] = password_hash($password, PASSWORD_DEFAULT);
        return;
    }

    // no password posted
    // we want password optional
    // $o->edit_sql dictates what fields are updated, redefine edit_sql and exclude 'password' field
    $o->edit_sql = "select title, email from markets where id = :id";

    // alternative method - populate $_POST with the current hash
    //$sql = "select password from markets where id = :id";
    //$result = $o->query($sql, array(':id' => $_REQUEST['id']));
    //$_POST['password'] = $result[0]['password'];

};

// call the controller
$o->run();

?>

</body>
