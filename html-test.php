<?php
/**
 * Created by PhpStorm.
 * User: ada
 * Date: 07-Aug-17
 * Time: 09:44
 */

include_once dirname(__FILE__).'/converter.php';

// Please use your own HTML files here. I'm not allowed to upload our html samples as the contents contain sensitive data
$html = file_get_contents(dirname(__FILE__).'/examples/html1.html');

$dom = new DOMDocument();
$dom->recover = true;
@$dom->loadHTML($html);

$xpathConverter = new XPath();

$xpathQuery = new DOMXPath($dom);

$query = $xpathConverter->convert("script:contains('var flashvars')");
$query = $query->preparePath(true);
$nodes = $xpathQuery->query($query);
?><!DOCTYPE html>
<html>
<head>
</head>
<body>
<pre>
	<?php
	if($nodes->length)
	{
		/** @var DOMNode $node */
		foreach ($nodes as $node)
		{
			echo htmlentities(print_r(array("query" => $query, "path" => $node->getNodePath(), "value" => $node->nodeValue, "node" => $node), true));
		}
	}
	?>
</pre>
</body>
</html>