<?php
class XPathException extends Exception{

}

class XPath{
	private $jquery_selector = null;

	private $node;
	private $predicates = array();
	private $axis;
	/** @var XPath[] */
	private $multiple = array();

	/** @var XPath[] */
	private $subpath = array();

	function setNode($node)
	{
		$this->node = $node;
	}

	function addMultiple(XPath $path)
	{
		$this->multiple[] = $path;
	}

	function addPredicate($predicate)
	{
		if($predicate)
		{
			$this->predicates[] = $predicate;
		}
	}

	function addSubPath(XPath $path)
	{
		$this->subpath[] = $path;
	}

	function setAxis($axis)
	{
		if($axis)
		{
			$axis .= "::";
		}

		$this->axis = $axis;
	}

	function preparePath($anywhere = false)
	{
		$path = "";

		if($anywhere)
		{
			$path = "/";
		}

		$node = $this->node;

		if($this->axis && !$node)
		{
			$node = "self";
		}

		if (!$node)
		{
			$node = "*";
		}

		if($this->node || $this->axis || $this->predicates)
		{
			$predicates = join(" and ", $this->predicates);

			if ($predicates)
			{
				$predicates = "[{$predicates}]";
			}

			$path .= "/{$this->axis}{$node}{$predicates}";
		}

		if($this->subpath)
		{
			foreach($this->subpath as $otherXpath)
			{
				$path .= $otherXpath->preparePath();
			}
		}

		if($this->multiple)
		{
			foreach($this->multiple as $otherXpath)
			{
				$path .= " | ".$otherXpath->preparePath();
			}
		}

		return $path;
	}

	/**
	 *
	 * Converts a jQuery selector to its equivalent XPath, which can be used to search in DOMXPath queries or anywhere else
	 * At the moment, only class and ID selectors are implemented.
	 *
	 * TODO: implement all selectors which are still not implemented.
	 *
	 * @link https://api.jquery.com/category/selectors/
	 *
	 * @param $jquery_selector
	 *
	 * @return XPath
	 */
	function convert($jquery_selector = null)
	{
		if($jquery_selector)
		{
			$this->jquery_selector = $jquery_selector;
		}

		if($this->jquery_selector)
		{
			$expressions = array(
				"(\,\s*(?'multiple'.+))",
				"(\>\s*(?'child'.+))",
				"(\+\s*(?'adjacent'.+))",
				"(\~\s*(?'sibling'.+))",
				"(\[(?'attribute'[\w\-]+)\]|\[(?'attribute'[\w\-]+)(?'op'\W+)(?'attr_quote'\"|\')(?'attr_val'[^\"]+)\k'attr_quote']|\[(?'attribute'[\w\-]+)(?'op'\W+)(?'attr_val'[^\]]+)\])",
				"(\:(?'filter'[\w\-]+)(\((?'func_quote'\"|\')(?'arg'.+)\k'func_quote'\)|\((?'arg'[^\)]+)\)?)?)",
				"(\#(?'id'[\w\-]+))",
				"(\.(?'className'[\w\-]+))",
				"(?'element'\w+)",
				"(\s+(?'descendant'.+))",
			);

			$regex = "/".join("|", $expressions)."/iJ";

			if(preg_match_all($regex, $this->jquery_selector, $matches, PREG_SET_ORDER))
			{
				foreach($matches as $match)
				{
					/*
					TODO: test and verify logic for following cases
					if(!empty($match["multiple"]))
					{
						$other = new XPath();
						$other->convert($match["multiple"]);
						$this->addMultiple($other);
					}
					elseif(!empty($match["child"]))
					{
						$child = new XPath();
						$child->setAxis("child");
						$child->setNode("self");
						$child->convert($match["child"]);
						$this->addSubPath($child);
					}
					elseif(!empty($match["adjacent"]))
					{
						$child = new XPath();
						$child->setAxis("child");
						$child->setNode("parent");
						$child->convert($match["adjacent"]);
						$this->addSubPath($child);
					}
					elseif(!empty($match["sibling"]))
					{
						//TODO: find out the right path for this;
					}
					else
					*/
					if(!empty($match["descendant"]))
					{
						$descendant = new XPath();
						$descendant->convert($match["descendant"]);
						$this->addSubPath($descendant);
					}
					elseif(!empty($match["attribute"]))
					{
						$this->convertAttribute($match["attribute"], $match["op"], $match["attr_val"]);
					}
					elseif(!empty($match["filter"]))
					{
						$this->convertFilter($match["filter"], $match["arg"]);
					}
					elseif(!empty($match["className"]))
					{
						$this->convertClassName($match["className"]);
					}
					elseif(!empty($match["id"]))
					{
						$this->convertID($match["id"]);
					}
					elseif(!empty($match["element"]))
					{
						$this->setNode($match["element"]);
					}
				}
			}
		}

		return $this;
	}

	function convertID($id)
	{
		$this->addPredicate("@id='{$id}'");
		return $this;
	}

	function convertAttribute($attr, $op, $attr_val)
	{
		$predicate = "";

		if ($op)
		{
			switch($op)
			{
			case "=":
				$predicate = "@{$attr}='{$attr_val}'";
				break;

			default:
				throw new XPathException("Operation '{$op}' in '{$attr}{$op}{$attr_val}' is not implemented yet. Please contact package author or implement it yourself.");
				break;
			}
		}
		$this->addPredicate($predicate);

		return $this;
	}

	function convertFilter($filter, $arg)
	{
		$predicate = "";
		switch($filter)
		{
		case "contains":
			/** @see https://www.w3.org/TR/xpath/#section-Text-Nodes - "<" has to be converted to &gt; */
			$arg = str_replace("<","&gt;", $arg);
			$predicate = "contains(text(), '{$arg}')";
			break;

		case "parent":
			$filter = new XPath();
			$filter->setNode("..");
			$this->addSubPath($filter);
			break;

		default:
			throw new XPathException("Filter '{$filter}' in '{$filter}={$arg}' is not implemented yet. Please contact package author or implement it yourself.");
			break;
		}

		$this->addPredicate($predicate);
		return $this;
	}

	function convertClassName($className)
	{
		$this->addPredicate("contains(concat(' ', normalize-space(@class), ' '), ' {$className} ')");
		return $this;
	}
}