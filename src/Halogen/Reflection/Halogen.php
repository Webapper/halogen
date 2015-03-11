<?php
/**
 * @author assarte
 * @date 2015.03.08.
 * @time 15:55
 */

namespace Webapper\Halogen\Reflection;

abstract class Halogen implements \Reflector
{
	/**
	 * Type for identification
	 */
	const ID_TYPE = '<abstract>';

	/**
	 * @var string
	 */
	protected $content = '';

	/**
	 * @var Halogen
	 */
	protected $parent;

	/**
	 * @var GenBank
	 */
	protected $genBank;

	/**
	 * @var [Halogen]
	 */
	protected $children = array();

	/**
	 * @var Identifier
	 */
	protected $identifier;

	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * @var string
	 */
	protected $processorClass = 'Webapper\Halogen\Reflection\Processor';

	/**
	 * @var GenBank
	 */
	protected static $commonGenBank;

	/**
	 * @param $content
	 * @param Halogen $parent
	 * @param GenBank $genBank
	 */
	public function __construct($content, Halogen $parent=null, GenBank $genBank=null)
	{
		$this->content = $content;
		$this->parent = $parent;
		$this->genBank = ($genBank or $parent->getGenBank()) or self::$commonGenBank;

		$this->process();
	}

	/**
	 * @param GenBank $genBank
	 */
	public static function registerGenBank(GenBank $genBank)
	{
		self::$commonGenBank = $genBank;
	}

	/**
	 * @return GenBank
	 */
	protected function getGenBank()
	{
		return $this->genBank;
	}

	/**
	 * @return string
	 */
	public function getProcessorClass()
	{
		return $this->processorClass;
	}

	/**
	 * Used by {@link setProcessorClass}, void or throws ProcessorException
	 * @param $processorClass
	 * @throws ProcessorException
	 */
	protected function assertProcessorClass($processorClass)
	{
		if (is_a($processorClass, 'Webapper\Halogen\Reflection\Processor')) throw new ProcessorException('Argument $processorClass must contains a class derived from Webapper\Halogen\Reflection\Processor, "'.$processorClass.'" given.');
	}

	/**
	 * @param $processorClass
	 * @return $this
	 */
	public function setProcessorClass($processorClass)
	{
		$this->assertProcessorClass($processorClass);
		$this->processorClass = $processorClass;

		return $this;
	}

	/**
	 * @return Processor
	 */
	protected function getProcessor()
	{
		$class = $this->processorClass;
		$processor = new $class($this, $this->content);

		return $processor;
	}

	/**
	 * @throws UnsupportedContent
	 */
	protected function process()
	{
		$processor = $this->getProcessor();

		if (!$processor->isSingle()) {
			foreach ($processor->getContainer() as $idx=>$content) {
				$this->children[$idx] = $this->getGenBank()->instanceGenByContent($content, $this);
			}
		}
	}

	/**
	 * @return Halogen
	 */
	public function getParent()
	{
		return $this->parent;
	}

	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @return Identifier
	 */
	public function getIdentifier()
	{
		if (!isset($this->identifier)) $this->makeIdentifier();

		return $this->identifier;
	}

	/**
	 * @return Halogen
	 */
	public function getRoot()
	{
		$root = $this;

		while ($root->getParent() !== null) {
			$root = $root->getParent();
		}

		return $root;
	}

	/**
	 * @return [Halogen]|null returns null if item must have no children
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * Gets extended info for identification
	 * @return null|string
	 */
	protected function getExtendedId()
	{
		return;
	}

	/**
	 * Makes the identifier
	 */
	protected function makeIdentifier()
	{
		$idx = null;

		if ($this->getParent() !== null) {
			foreach ($this->getParent()->getChildren() as $k=>$item) {
				if ($item === $this) {
					$idx = $k;
					break;
				}
			}
		}

		$this->identifier = new Identifier(static::ID_TYPE, $idx, $this->getExtendedId());
	}

	/**
	 * @return string
	 */
	public function identifySelf()
	{
		if (!isset($this->identifier)) $this->makeIdentifier();

		return (string)$this->identifier;
	}

	/**
	 * Gets a "/"-separated path using identification on each item in the parent-tree
	 * @return string
	 */
	public function getPath()
	{
		$path = $this->identifySelf();
		$item = $this->getParent();

		while ($item !== null) {
			$path = $item->identifySelf().'/'.$path;
			$item = $item->getParent();
		}

		return $path;
	}

	/**
	 * Finds a descendant child by the path under this item or under the root if path started with "/"
	 * @param $path
	 * @param bool $strict Sets whether to use isCastedLike() for secondary identification or not - defaults to use
	 * @return bool|Halogen false if item not found on passed path, Halogen item returns when found
	 */
	public function findIn($path, $strict = false)
	{
		if ($this->getChildren() === null) return false;

		$in = $this;
		if ($path{0} == '/') {
			$path = substr($path, 1);
			$in = $this->getRoot();
		}

		$found = false;
		$parts = explode('/', $path);
		$id = array_shift($parts);
		foreach ($in->getChildren() as $item) {
			/* @var $item Halogen */
			if (
				($item->getIdentifier()->isIdentical($id)) or
				(!$strict and $item->getIdentifier()->isCastedLike($id))
			) {
				$subpath = join('/', $parts);

				if (count($parts) > 0) {
					if ($found = $item->findIn($subpath, $strict)) {
						break;
					}
				} else {
					$found = $item;
					break;
				}
			}
		}

		return $found;
	}

	/**
	 * @param int|string $idx
	 * @return $this
	 * @throws HalogenException
	 */
	public function removeChild($idx)
	{
		if (!isset($this->children[$idx])) throw new HalogenException('Child "'.$idx.'" not exists to be removed.');

		unset($this->children[$idx]);

		return $this;
	}

	/**
	 * @param Halogen $child
	 * @param null|int|string $idx
	 * @param null|int|string $beforeIdx
	 * @return $this
	 * @throws HalogenException
	 * @throws \InvalidArgumentException
	 */
	public function appendChild(Halogen $child, $idx=null, $beforeIdx=null)
	{
		if ($idx !== null and isset($this->children[$idx])) throw new HalogenException('Item "'.$idx.'" already exists at: '.$this->getPath());
		if ($idx === $beforeIdx) throw new \InvalidArgumentException('Arguments $idx and $beforeIdx must not the same ("'.$idx.'") at: '.$this->getPath());

		if ($beforeIdx === null) {
			if ($idx !== null) {
				$this->children[$idx] = $child;
			} else {
				$this->children[] = $child;
			}
		} else {
			$list = array();
			$intRecount = 0;

			foreach ($this->children as $k=>$item) {
				if ($k === $beforeIdx) {
					if ($idx !== null) {
						$list[$idx] = $child;
					} else {
						$list[] = $child;
						end($list);
						if (is_int($beforeIdx) and $beforeIdx === key($list)) $intRecount = 1;
					}
				}

				if (is_int($k)) {
					$list[$k + $intRecount] = $item;
				} else {
					$list[$k] = $item;
				}
			}

			$this->children = $list;
		}

		return $this;
	}

	/**
	 * @param int|string $idx
	 * @param Halogen $child
	 * @return $this
	 * @throws HalogenException
	 */
	public function setChild($idx, Halogen $child)
	{
		if (!isset($this->children[$idx])) throw new HalogenException('Item "'.$idx.'" not exists to set at: '.$this->getPath());

		$this->children[$idx] = $child;

		return $this;
	}

	/**
	 * @param int|string $idx
	 * @return bool
	 */
	public function isExists($idx)
	{
		return (isset($this->children[$idx]));
	}

	/**
	 * @param Halogen $reflector
	 * @param bool $return
	 * @return void|string
	 */
	public static function export()
	{
		$args = func_get_args();
		$cargs = count($args);
		if ($cargs == 0 or $cargs > 2) throw new \InvalidArgumentException(__CLASS__.'::'.__METHOD__.'($reflector[, $return]) expecting 1 or 2 arguments, '.$cargs.' given.', 1);

		$reflector = null;
		$return = false;

		switch ($cargs) {
			case 1: {
				list($reflector) = $args;
				break;
			}
			case 2: {
				list($reflector, $return) = $args;
				break;
			}
		}

		if (!($reflector instanceof Halogen)) throw new \InvalidArgumentException(__CLASS__.'::'.__METHOD__.'($reflector[, $return]) expecting parameter 1 as '.__CLASS__.' instance.', 2);
		$return = (bool)$return;

		if ($return) {
			return (string)$reflector;
		} else {
			echo (string)$reflector;
		}
	}

	/**
	 * @return string
	 */
	abstract public function __toString();
}