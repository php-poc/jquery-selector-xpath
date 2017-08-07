<?php
include_once  "converter.php";

$selector = "script:contains('var flashvars')";

$xpath = new JqueryToXPath();
$xpath->convert($selector, true);

echo "<pre>{$xpath}</pre>";
