<?php
ini_set('display_errors', true);
error_reporting(E_ALL^E_NOTICE);
ini_set("max_execution_time", 0);
ini_set('memory_limit', '512M');

include_once  "converter.php";

$selector = "select option[selected]";

$xpath = new JqueryToXPath();
$converter = $xpath->convert($selector, JqueryToXPath::QUERY_ANYWHERE);

echo "<pre>{$selector} => {$converter}</pre>";

$selector = "input[type!='hidden']";

$xpath = new JqueryToXPath();
$converter = $xpath->convert($selector );

echo "<pre>{$selector} => {$converter}</pre>";
$selector = "input[type!='hidden'], select option[selected]";

$xpath = new JqueryToXPath();
$converter = $xpath->convert($selector );

echo "<pre>{$selector} => {$converter}</pre>";
