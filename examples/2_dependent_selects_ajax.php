<?php

// html reply to display province matching the requested country

declare(strict_types=1); 
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

$dbh = new PDO('sqlite:example.db');
//$dbh = new PDO("mysql:host=127.0.0.1;dbname=example;", "example", "example");
//$dbh = new PDO("pgsql:host=127.0.0.1;dbname=example;", "example", "example");

require "../src/PHPBootstrapTableEdit.php";
//require "../vendor/autoload.php";

$o = new PHPBootstrapTableEdit\PHPBootstrapTableEdit($dbh);

$country = strtoupper($_GET['country'] ?? '');

$o->edit['province']['required'] = true;
$o->edit['province']['type'] = 'select';
$o->edit['province']['sql'] = 'select code, title from provinces where country = :country';
$o->edit['province']['sql_param'] = array(':country' => $country);

// we don't need to repopulate a previous value, the initial load is rendered by the main script
// this select dropdown is only needed when changing countries, so there's never a province selection
$no_value = '';

echo $o->get_input('province', $no_value, 'edit', true);
