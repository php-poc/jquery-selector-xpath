<?php
include_once  "converter.php";

$selector = "script:contains('var flashvars')";

$xpath = new XPath();
$xpath->convert($selector);
$xpath = $xpath->preparePath(true);

echo "<pre>{$xpath}</pre>";
