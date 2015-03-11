<?php
/**
 * @author assarte
 * @date 2015.03.08.
 * @time 20:37
 */

namespace Webapper\Halogen\Reflection;

class Processor
{
	/**
	 * @var array
	 */
	protected $container = array();

	/**
	 * @var string
	 */
	protected $itemContent = '';

	/**
	 * @var Halogen
	 */
	protected $halogen;

	/**
	 * @param Halogen $halogen
	 * @param string $content
	 * @throws ProcessorException
	 * @throws UnsupportedContent See {@parseContent}
	 */
	public function __construct(Halogen $halogen, $content)
	{
		$this->halogen = $halogen;

		$reLinebreaks = '%(\r|\r\n|\n\r)%';
		$content = preg_replace($reLinebreaks, "\n", $content);
		$content = rtrim($content);

		$content = $this->parseContent($content);
		$this->itemContent = $content;

		// captures:
		//                  |-begin-|---------+------------------------ block without trailing line-break OR empty line ----------------------------------|---- end -----|
		//                  |       |         |---+--------------------------------- block without trailing line-break ---------------------------------+-|              |
		//                  |       |         |any|--+------------------------ content (can use escapes, quotes, braces) -------------------------------| |              |
		//                  |       |         |   |  |---- block-open chars: '"{[( ----|---- check if escaped ----|---- block-close chars: '"}]) ----|  | |              |
		$reBlockMatcher = '%(?:^|\n)(?P<block>(.*?(?:((?<![\\\\])[\'"]|((\{)|(\[)|(\()))((?:.(?!(?<![\\\\])\3))*.?)(?(4)(?(5)\}|(?(6)\]|(?(7)\))))|\3))?)*)(?=\n+[\w# ]|$)%s';
		$matches = null;
		preg_match_all($reBlockMatcher, $content, $matches);

		$idx = 0;
		foreach ($matches['block'] as $block) {
			$key = $this->shiftKeyFrom($block);
			if ($key === null) {
				$this->container[$idx] = $block;
				$idx++;
			} else {
				$this->container[$key] = $block;
			}
		}
	}

	/**
	 * Gets the identified raw blocks or item
	 * @return array
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * Checks whether the original content passed to the constructor contains a single- or a block-like item or not
	 * @return bool
	 */
	public function isSingle()
	{
		// detects any of "-"/":" followed by "\n + indent" and opening braces outside of a string-sequence
		$reIsContainer = '%((?(?=(\s*)\-\s*\n+\2\s+)(?P<array>\-)|(?(?=[\{\[\(])(?P<brace>[\{\[\(])|(?(?=(\s*)\:\s*\n+\5\s+)(?P<block>\:)|(?(?=[\'"])([\'"])([^\'"\\\\]*(?:\\.[^\'"\\\\]*)*)\7|[^\'"])))))*%s';
		$match = null;
		preg_match($reIsContainer, $this->itemContent, $match);

		return (!isset($match['brace']) and !isset($match['block']) and !isset($match['array']));
	}

	/**
	 * @param string $content
	 * @return string
	 * @throws UnsupportedContent When passed content is not supported by this Processor
	 */
	protected function parseContent($content)
	{
		return $content;
	}

	/**
	 * Shifts out the key of the passed content, if any
	 * @param $content
	 * @return string
	 */
	protected function shiftKeyFrom(&$content)
	{
		$reKey = '%(?:-\s*)?((?:(?(?=[\'"])([\'"])([^\'"\\]*(?:\\\\.[^\'"\\\\]*)*)\2)|[^:])*)\:(.*)%s';
		$matches = null;
		$key = null;

		if (preg_match($reKey, $content, $matches)) {
			$key = $matches[1];
			$content = $matches[4];
		}

		return $key;
	}
}