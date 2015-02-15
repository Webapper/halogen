<?php

namespace Webapper\Halogen;

use Webapper;


/**
 * Simple parser & generator for Nette Object Notation.
 *
 * @author     David Grudl
 */
class Neon
{
	const BLOCK = Encoder::BLOCK;
	const CHAIN = '!!chain';

	/**
	 * Returns the NEON representation of a value.
	 * @param  mixed $var
	 * @param  int $options
	 * @return string
	 */
	public static function encode($var, $options = NULL)
	{
		$encoder = new Encoder;
		return $encoder->encode($var, $options);
	}

	/**
	 * Decodes a NEON string.
	 * @param  string $input
	 * @return mixed
	 */
	public static function decode($input)
	{
		$decoder = new Decoder;
		return $decoder->decode($input);
	}
}