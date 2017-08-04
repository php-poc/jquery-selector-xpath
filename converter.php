<?php
/**
 *
 * Converts a jQuery selector to its equivalent XPath, which can be used to search in DOMXPath queries or anywhere else
 * At the moment, only class and ID selectors are implemented.
 *
 * TODO: implement all selectors which are still not implemented.
 * @link https://api.jquery.com/category/selectors/
 *
 * @param $jquery_path
 *
 * @return string
 */
function convert_to_xpath($jquery_path)
{
	$converted = array();
	$descendant = "";
	$element = "";

	$items = preg_split("/([\+|\~|\,|\>|\.|\#|\:|\[|\s+]?\w+)/", $jquery_path, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

	foreach($items as $index => &$item)
	{
		switch($item[0])
		{
		case ':':
			// TODO: implement pseudo selectors
			break;

		case ".":
			// class selector
			$class = substr($item, 1);
			$converted[] = "contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')";
			break;

		case "[":
			//TODO: implement attribute selector
			break;

		case "#":
			//ID selector
			$id = substr($item, 1);
			$converted[] = "@id='{$id}'";
			break;

		case " ";
			//Descendant selector
			$item = substr($item, 1);
			$descendant = convert_to_xpath(join("", array_slice($items, $index)));
			break;

		case ",":
			//TODO: implement multiple selector
			break;

		case ">":
			//TODO: implement child selector
			break;

		case "+":
			//TODO: implement next adjacent selector
			break;

		case "~":
			//TODO: implement next sibling selector
			break;


		default:
			//element (when beginning with an alphanumeric character
			$element = $item;
			break;
		}

		if($descendant)
		{
			break;
		}
	}

	if($converted)
	{
		$converted = "[".join(" and ", $converted)."]";
	}
	else
	{
		$converted = "";
	}

	return "/".$element.$converted.$descendant;
}