<?php
/**
 * @author: Arash Dalir (arash.dalir@gmail.com)
 * @url https://github.com/php-poc/jquery-selector-xpath
 *
 */


/**
 * Class XPathException
 */
class JqueryToXPathException extends Exception{}

/**
 * converts a jQuery selector into an XPath equivalent to be used with DOMDocument's DOMXPath->query()
 *
 * Class JqueryToXPath
 */
class JqueryToXPath{
	const QUERY_RELATIVE = "";
	const QUERY_ABSOLUTE = "/";
	const QUERY_ANYWHERE = "//";

	/** @var string - current selector which is being converted */
	private $jquery_selector;

	/** @var  string - main node/element of the query */
	private $node;

	/** @var string[] - converted predicates */
	private $predicates;

	/** @var  string - XPath axis */
	private $axis;

	/** @var JqueryToXPath[] - contains objects to next selector(s) if the query contains multiple selectors - as in $(selector1, selector2, selector3) */
	private $next;

	/** @var JqueryToXPath[] - contains objects for descendants/children's XPaths*/
	private $subpaths;

	private $query_type;

	private $prefix;

	function __construct()
	{
		$this->reset();
	}

	function reset(){
		$this->axis = null;
		$this->node = null;
		$this->jquery_selector = null;
		$this->subpaths = array();
		$this->next = array();
		$this->predicates = array();
		$this->query_type = self::QUERY_ABSOLUTE;
		$this->prefix = "";
	}

	function setNode($node)
	{
		$this->node = $node;
	}

	function addNextSelector(JqueryToXPath $path)
	{
		$this->next[] = $path;
	}

	function addPredicate($predicate)
	{
		if($predicate)
		{
			$this->predicates[] = $predicate;
		}
	}

	function addSubPath(JqueryToXPath $path)
	{
		$this->subpaths[] = $path;
	}

	function setAxis($axis)
	{
		$this->axis = $axis;
	}

	/**
	 * returns prepared XPath parts as string
	 *
	 * @return string
	 *
	 */
	function __toString()
	{
		$path = "";

		$node = $this->node;
		$axis = $this->axis;
		$predicates = join(" and ", $this->predicates);

		if ($predicates)
		{
			$predicates = "[{$predicates}]";
		}

		if($axis)
		{
			if ($node)
			{
				$axis .= "::";
			}
		}

		if (!$axis && !$node)
		{
			$node = "*";
		}

		if($node || $axis || $predicates)
		{

			$path .= "{$this->prefix}{$this->query_type}{$axis}{$node}{$predicates}";
		}

		if($this->subpaths)
		{
			foreach($this->subpaths as $otherXpath)
			{
				$path .= (string)$otherXpath;
			}
		}

		if($this->next)
		{
			foreach($this->next as $otherXpath)
			{
				$path .= " | ".(string)$otherXpath;
			}
		}

		return $path;
	}

	/**
	 *
	 * Converts a jQuery selector to its equivalent XPath, which can be used to search in DOMXPath queries or anywhere else.
	 * At the moment, only class and ID selectors are implemented.
	 *
	 * TODO: implement all selectors which are still not implemented.
	 *
	 * @link https://api.jquery.com/category/selectors/
	 *
	 * @param string $jquery_selector - selector to be converted
	 *
	 * @param bool   $query_type      - one of self::QUERY_ABSOLUTE, self::QUERY_RELATIVE or self::QUERY_ANYWHERE
	 *
	 * @param string   $prefix
	 *
	 * @return string
	 */
	function convert($jquery_selector = null, $query_type = null, $prefix = null)
	{
		$this->reset();

		$this->setQueryType($query_type);

		$this->prefix = $prefix;

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
					if(!empty($match["adjacent"]))
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
					if(!empty($match["multiple"]))
					{
						$other = new JqueryToXPath();
						$other->convert($match["multiple"], $query_type, $prefix);
						$this->addNextSelector($other);
					}
					elseif(!empty($match["child"]))
					{
						$child = new JqueryToXPath();
						$child->convert($match["child"], self::QUERY_ABSOLUTE);
						$this->addSubPath($child);
					}
					elseif(!empty($match["descendant"]))
					{
						$descendant = new JqueryToXPath();
						$descendant->convert($match["descendant"], self::QUERY_ANYWHERE);
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

		return (string)$this;
	}

	function convertID($id)
	{
		$this->addPredicate("@id='{$id}'");
		return $this;
	}

	function convertAttribute($attr, $op, $attr_val)
	{
		$predicate = "";

		switch($op)
		{
		case "=":
		case "!=":
			$predicate = "@{$attr} {$op} '{$attr_val}'";
			break;

		case null:
			$predicate="@{$attr}";
			break;

		default:
			throw new JqueryToXPathException("Operation '{$op}' in '{$attr}{$op}{$attr_val}' is not implemented yet. Please contact package author or implement it yourself.");
			break;
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
			$filter = new JqueryToXPath();
			$filter->setNode("..");
			$this->addSubPath($filter);
			break;

		default:
			throw new JqueryToXPathException("Filter '{$filter}' in '{$filter}={$arg}' is not implemented yet. Please contact package author or implement it yourself.");
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

	function setQueryType($query_type)
	{
		if (isset($query_type))
		{
			if(in_array($query_type, array(self::QUERY_RELATIVE, self::QUERY_ANYWHERE, self::QUERY_ABSOLUTE), true))
			{
				$this->query_type = $query_type;
			}
			else
			{
				$class = __CLASS__;
				throw new JqueryToXPathException("query-type can only be one of {$class}::QUERY_RELATIVE, {$class}::QUERY_ANYWHERE or {$class}::QUERY_ABSOLUTE");
			}
		}
	}
}
