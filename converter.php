<?php
/**
 *
 * Converts a jQuery selector to its equivalent XPath, which can be used to search in DOMXPath queries or anywhere else
 * At the moment, only class and ID selectors are implemented.
 *
 * @TODO: implement all selectors which are still not implemented. @link https://api.jquery.com/category/selectors/
 *
 * @param $jquery_path
 *
 * @return string
 */
function convert_to_xpath($jquery_path)
{
	$converted = array();
	$children = "";
	$element = "";

	$items = preg_split("/([\.|\#|\:|\[|\s+]?\w+)/", $jquery_path, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

	foreach($items as $index => &$item)
	{
		switch($item[0])
		{
		case ':':
			break;

		case ".":
			$class = substr($item, 1);
			$converted[] = "contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')";
			break;

		case "[":
			break;

		case "#":
			$id = substr($item, 1);
			$converted[] = "@id='{$id}'";
			break;

		case " ";
			$item = substr($item, 1);
			$children = convert_to_xpath(join("", array_slice($items, $index)));
			break;

		default:
			$element = $item;
			break;
		}

		if($children)
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

	return "/".$element.$converted.$children;
	//return "//div[contains(concat(' ', normalize-space(@class), ' '), ' {$jquery_path} ')]";
}