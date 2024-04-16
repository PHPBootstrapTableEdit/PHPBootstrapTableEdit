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

    <!-- javascript to get new province dropdown when country changes -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script> 
    <script> 
    $(document).ready(function() {

        $(document).on('change', 'select[name=country]', function(){

            var country = $('select[name=country]').val();

            $.get("2_dependent_selects_ajax.php", { country: country }, function(html){
                $('select[name=province]').replaceWith(html);
            });

        });
    });
    </script>    

</head>
<body>


<?php
error_reporting(E_ALL);


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
       p.title as province,
       m.id
from   markets m
left   
join   countries c
on     c.code = m.country 
left   
join   provinces p
on     p.code = m.province
where  ( coalesce(m.title, '') like :search or coalesce(c.title, '') like :search or coalesce(p.title, '') like :search )
order by c.title, p.title
";

// named parameters for search
$o->index_sql_param[':search'] = '%' . trim($_REQUEST['_search'] ?? '') . '%';

// define fields on the edit form
$o->edit_sql = "select title, country, province from markets where id = :id";
$o->edit_sql_param[':id'] = intval($_POST['id'] ?? $_GET['id'] ?? 0);

// get country from sql we just defined, $country is used below to limit provinces
$country = '';
$result = $o->query($o->edit_sql, $o->edit_sql_param);
if(count($result) == 1)
    $country = $result[0]['country'];

// non floating input style
$o->floating = false;

// define field attributes
$o->edit['title']['required'] = true;

$o->edit['country']['required'] = true;
$o->edit['country']['type'] = 'select';
$o->edit['country']['sql'] = 'select code, title from countries'; // define how to populate the select dropdown

$o->edit['province']['required'] = true;
$o->edit['province']['type'] = 'select';
$o->edit['province']['sql'] = 'select code, title from provinces where country = :country';
$o->edit['province']['sql_param'] = array(':country' => $country);

// 12 column layout - colspan = 4 creates a three column layout
$o->edit['title']['colspan'] = '4';
$o->edit['country']['colspan'] = '4';
$o->edit['province']['colspan'] = '4';

// copy all 'edit' setting into 'add', the add form is almost the same settings
$o->add           = $o->edit;
$o->add_sql       = $o->edit_sql;
$o->add_sql_param = $o->edit_sql_param;

// call the controller
$o->run();

?>

</body>
