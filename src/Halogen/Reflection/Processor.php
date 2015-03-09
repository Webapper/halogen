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

	public function __construct(Halogen $halogen, $content, $unindent=true)
	{
		$this->halogen = $halogen;

		$reLinebreaks = '%(\r|\r\n|\n\r)%';
		$content = preg_replace($reLinebreaks, "\n", $content);
		$content = rtrim($content);

		$this->itemContent = $content;

		// captures:
		//                  |-begin-|---------+------------------------ block without trailing line-break OR empty line ----------------------------------|---- end -----|
		//                  |       |         |---+--------------------------------- block without trailing line-break ---------------------------------+-|              |
		//                  |       |         |any|---+------------------------ content (can use escapes, quotes, braces) ------------------------------| |              |
		//                  |       |         |   |   |--- block-open chars: '"{[( ----|---- check if escaped ----|---- block-close chars: '"}]) ----|  | |              |
		$reBlockMatcher = '%(?:^|\n)(?P<block>(.*?(?:((?<![\\\\])[\'"]|((\{)|(\[)|(\()))((?:.(?!(?<![\\\\])\3))*.?)(?(4)(?(5)\}|(?(6)\]|(?(7)\))))|\3))?)*)(?=\n+[\w# ]|$)%s';
		$matches = null;
		preg_match_all($reBlockMatcher, $content, $matches);

		$this->container = $matches['block'];
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