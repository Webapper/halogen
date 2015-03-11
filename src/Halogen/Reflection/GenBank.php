<?php
/**
 * @author assarte
 * @date 2015.03.11.
 * @time 2:05
 */

namespace Webapper\Halogen\Reflection;

class GenBank
{
	/**
	 * @var array
	 */
	protected $gens = array();

	/**
	 * @var array
	 */
	protected $priorities = array();

	/**
	 * @var null|string
	 */
	protected $fallbackGen;

	/**
	 * @return Halogen
	 */
	public static function create()
	{
		return new static();
	}

	public function getFallbackGen()
	{
		return $this->fallbackGen;
	}

	/**
	 * Sets the fallback-gen, which will always the last item in the priority order, regardless its original priority
	 * passed to {@link register}
	 * @param null|string $genClassName Passing null will unsets the fallback-gen
	 * @return $this
	 */
	public function setFallbackGen($genClassName) {
		if ($genClassName !== null and !is_a($genClassName, 'Webapper\Halogen\Reflection\Halogen', true)) throw new \InvalidArgumentException('Argument $genClassName expected as a valid class name which extends Halogen, given "'.$genClassName.'".');

		$this->fallbackGen = $genClassName;

		return $this;
	}

	/**
	 * @param string $genClassName
	 * @param null|int $priority
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function register($genClassName, $priority=null)
	{
		if (!is_a($genClassName, 'Webapper\Halogen\Reflection\Halogen', true)) throw new \InvalidArgumentException('Argument $genClassName expected as a valid class name which extends Halogen, given "'.$genClassName.'".');
		if ($priority === null) $priority = count($this->gens);

		$this->priorities[] = $priority;
		$this->gens[] = $genClassName;

		return $this;
	}

	/**
	 * Gets gens in priority order
	 * @return array
	 * @throws \LogicException Thrown when a fallback-gen which was set is not {@link register registered}
	 */
	public function getGens()
	{
		if ($this->fallbackGen !== null and !in_array($this->fallbackGen, $this->gens)) throw new \LogicException('Fallback-gen "'.$this->fallbackGen.'" is unregistered.');

		asort($this->priorities);
		$gens = array();
		$fallbackIdx = null;

		foreach ($this->priorities as $idx=>$priority) {
			if ($this->fallbackGen !== null and $this->gens[$idx] == $this->fallbackGen) {
				$fallbackIdx = $idx;
				continue;
			}
			$gens[] = $this->gens[$idx];
		}

		if ($fallbackIdx !== null) {
			$gens[] = $this->fallbackGen;
		}

		return $gens;
	}

	/**
	 * Instances a gen based on passed content and binds it to passed parent, new instance will inheriting the parent's gen-bank
	 * @param string $content
	 * @param Halogen $parent
	 * @return Halogen
	 * @throws UnsupportedContent
	 */
	public function instanceGenByContent($content, Halogen $parent) {
		foreach ($this->getGens() as $gen) {
			try {
				$newGen = new $gen($content, $parent);
				return $newGen;
			} catch (UnsupportedContent $e) {}
		}

		throw new UnsupportedContent('Passed content cannot be interpreted by registered gens.');
	}

	/**
	 * @param string $type
	 * @return string
	 * @throws UnsupportedGen
	 */
	public function getGenByType($type)
	{
		foreach ($this->gens as $gen) {
			if (constant($gen.'::ID_TYPE') === $type) {
				return $gen;
			}
		}

		throw new UnsupportedGen();
	}
}