<?php
/**
 * @author assarte
 * @date 2015.03.08.
 * @time 17:53
 */

namespace Webapper\Halogen\Reflection;

class Identifier
{
	/**
	 * Format regex for "type[[blockIdx]][:extended]" - parts can contains - (dash), _ (underscore), a-Z, 0-9, . (dot)
	 */
	const RE_FORMAT = '%^(?P<type>[-_a-z0-9\.]+?)((\[(?P<blockIdx>[-_a-z0-9\.]+?)\])?(:(?P<extended>[-_a-z0-9\.]+?))?)?$%i';

	/**
	 * @var string
	 */
	protected $type = '';

	/**
	 * @var string|int
	 */
	protected $blockIdx;

	/**
	 * @var string
	 */
	protected $extended;

	/**
	 * @param string $type
	 * @param string|int $blockIdx
	 * @param string $extended
	 */
	public function __construct($type, $blockIdx = null, $extended = null)
	{
		$this->type = $type;
		$this->blockIdx = $blockIdx;
		$this->extended = $extended;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return int|null|string
	 */
	public function getBlockIdx()
	{
		return $this->blockIdx;
	}

	/**
	 * @return null|string
	 */
	public function getExtended()
	{
		return $this->extended;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		$result = $this->type;

		if ($this->blockIdx !== null) $result .= '['.$this->blockIdx.']';
		if ($this->extended !== null) $result .= ':'.$this->extended;

		return $result;
	}

	/**
	 * @param string $to
	 * @return bool
	 */
	public function isIdentical($to)
	{
		$parts = array();
		if (!preg_match(static::RE_FORMAT, $to, $parts)) throw new \InvalidArgumentException('Passed argument $to has illegal format: '.$to);

		$type = $parts['type'];
		$blockIdx = (isset($parts['blockIdx'])? $parts['blockIdx'] : null);
		$extended = (isset($parts['extended'])? $parts['extended'] : null);

		return ($type === $this->type and $blockIdx === $this->blockIdx and $extended === $this->extended);
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function isInTypeLike($id)
	{
		$parts = array();
		if (!preg_match(static::RE_FORMAT, $id, $parts)) throw new \InvalidArgumentException('Passed argument $to has illegal format: '.$to);

		$type = $parts['type'];

		return ($type === $this->type);
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function isIndexedLike($id)
	{
		$parts = array();
		if (!preg_match(static::RE_FORMAT, $id, $parts)) throw new \InvalidArgumentException('Passed argument $to has illegal format: '.$to);

		$blockIdx = (isset($parts['blockIdx'])? $parts['blockIdx'] : null);

		return ($blockIdx === $this->blockIdx);
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function isExtendedLike($id)
	{
		$parts = array();
		if (!preg_match(static::RE_FORMAT, $id, $parts)) throw new \InvalidArgumentException('Passed argument $to has illegal format: '.$to);

		$extended = (isset($parts['extended'])? $parts['extended'] : null);

		return ($extended === $this->extended);
	}

	/**
	 * Using only type- and blockIdx parts of id
	 * @param $idxId
	 * @return bool
	 */
	public function isAt($idxId)
	{
		return ($this->isInTypeLike($idxId) and $this->isIndexedLike($idxId));
	}

	/**
	 * Using only type- and extended parts of id
	 * @param $extId
	 * @return bool
	 */
	public function isCastedLike($extId)
	{
		return ($this->isInTypeLike($extId) and $this->isExtendedLike($extId));
	}
}