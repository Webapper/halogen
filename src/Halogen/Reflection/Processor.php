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

	public function __construct(Halogen $halogen, $content, $unindent=false)
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

		if ($unindent) {
			foreach ($this->container as $k=>$v) {
				$this->container[$k] = $this->unindent($v, $k);
			}
		}
	}

	public function getContainer()
	{
		return $this->container;
	}

	public function isSingle()
	{
		// detects any of "-"/":" followed by "\n + indent" and opening braces outside of a string-sequence
		$reIsContainer = '%((?(?=(\s*)\-\s*\n\2\s+)(?P<array>\-)|(?(?=[\{\[\(])(?P<brace>[\{\[\(])|(?(?=(\s*)\:\s*\n\5\s+)(?P<block>\:)|(?(?=[\'"])([\'"])([^\'"\\\\]*(?:\\.[^\'"\\\\]*)*)\7|[^\'"])))))*%s';
		$match = null;
		preg_match($reIsContainer, $this->itemContent, $match);

		return (!isset($match['brace']) and !isset($match['block']) and !isset($match['array']));
	}

	protected function parseContent($content)
	{
		return $content;
	}

	protected function shiftKeyFrom(&$content)
	{
		$reKey = '%^(-\s*)?(\w+)\:(.*)%';
		$matches = null;
		$key = null;

		if (preg_match($reKey, $content, $matches)) {
			$key = $matches[2];
			$content = $matches[3];
		}

		return $key;
	}

	protected function unindent($content, $blockIdx)
	{
		$lines = explode("\n", $content);

		foreach ($lines as $k=>$line) {
			if (trim($line) === '') continue;
			if ($line{0} !== "\t") throw new ProcessorException('Bad indention on '.$this->halogen->getPath().' in block #'.$blockIdx.' at line '.$k);

			$lines[$k] = substr($line, 1);
		}

		$result = join("\n", $lines);

		return $result;
	}
}