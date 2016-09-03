<?php

/*
** Locale String Pre-processor
*/

namespace Losp;

define ('LOSP', '1.0.0.1');

class FormatException extends \Exception
{
	public function __construct ($message)
	{
		parent::__construct ($message);
	}
}

class ParseException extends \Exception
{
	public function __construct ($node, $message)
	{
		parent::__construct ($message);

		$this->node = $node;
	}
}

class Locale
{
	const ENCODING = 'UTF-8';
	const ESCAPE = '\\';
	const EVALUATOR_BEGIN = '{';
	const EVALUATOR_END = '}';
	const MODIFIER_DECLARE = ':';
	const MODIFIER_NEXT = ',';
	const TYPE_MODIFIER = 0;
	const TYPE_PLAIN = 1;
	const TYPE_VARIABLE = 2;

	private	$formatters;
	private	$modifiers;

	public function	__construct ($encoding, $language, $source, $cache = null)
	{
		// Build or load language formatters from strings or cache
		if ($cache === null || (@include $cache) === false)
		{
			if (!file_exists ($source))
				throw new \Exception ('strings path "' . $source . '" doesn\'t exist');

			// Browse for language files and convert to formatters
			$formatters = array ();
			$references = array ();

			self::convert ($formatters, $references, $encoding, $language, $source);

			// Resolve aliased references
			foreach ($references as $target => $reference)
			{
				if (!isset ($formatters[$reference]))
					throw new \Exception ('invalid reference to key "' . $reference . '" for alias "' . $target . '"');

				$formatters[$target] = $formatters[$reference];
			}

			// Save to cache
			if ($cache !== null)
			{
				$contents = '<?php $formatters = ' . self::export ($formatters, true) . '; ?>';

				if (file_put_contents ($cache, $contents, LOCK_EX) === false)
					throw new \Exception ('unable to write cache file "' . $cache . '" to disk');
			}
		}

		// Check variable consistency
		if (!isset ($formatters))
			throw new \Exception ('missing $formatters variable in cache');

		// Initalize members
		$this->formatters = $formatters;
		$this->modifiers = array
		(
			'add'	=> function ($lhs, $rhs) { return $lhs + $rhs; },
			'case'	=> function ($value)
			{
				$pairs = array_slice (func_get_args (), 1);

				for ($i = 0; $i + 1 < count ($pairs); $i += 2)
				{
					if ($pairs[$i] == $value)
						return $pairs[$i + 1];
				}

				return $i < count ($pairs) ? $pairs[$i] : null;
			},
			'date'	=> function ($time, $format) { return date ($format, $time); },
			'def'	=> function ($value, $default) { return $value ?: $default; },
			'div'	=> function ($lhs, $rhs) { return (int)($lhs / $rhs); },
			'eq'	=> function ($lhs, $rhs, $true = '1', $false = null) { return $lhs == $rhs ? $true : $false; },
			'ge'	=> function ($lhs, $rhs, $true = '1', $false = null) { return $lhs >= $rhs ? $true : $false; },
			'gt'	=> function ($lhs, $rhs, $true = '1', $false = null) { return $lhs > $rhs ? $true : $false; },
			'if'	=> function ($condition, $true = '1', $false = null) { return $condition ? $true : $false; },
			'ifset'	=> function ($condition, $true = '1', $false = null) { return $condition !== null ? $true : $false; },
			'le'	=> function ($lhs, $rhs, $true = '1', $false = null) { return $lhs <= $rhs ? $true : $false; },
			'lt'	=> function ($lhs, $rhs, $true = '1', $false = null) { return $lhs < $rhs ? $true : $false; },
			'mod'	=> function ($lhs, $rhs) { return $lhs % $rhs; },
			'mul'	=> function ($lhs, $rhs) { return $lhs * $rhs; },
			'ne'	=> function ($lhs, $rhs, $true = '1', $false = null) { return $lhs != $rhs ? $true : $false; },
			'pad'	=> function ($string, $length, $char = ' ') { return str_pad ($string, abs ($length), $char, $length < 0 ? STR_PAD_LEFT : STR_PAD_RIGHT); },
			'sub'	=> function ($lhs, $rhs) { return $lhs - $rhs; }
		);
	}

	public function assign ($modifier, $callback)
	{
		$this->modifiers[$modifier] = $callback;
	}

	public function format ($key, $params = null)
	{
		try
		{
			if (!isset ($this->formatters[$key]))
				throw new FormatException ('unknown formatter');

			return (string)$this->apply ($this->formatters[$key], $params);
		}
		catch (FormatException $exception)
		{
			throw new \Exception ($exception->getMessage () . ' for key "' . $key . '"');
		}
	}

	private function apply ($chunks, $params)
	{
		$out = null;

		foreach ($chunks as $chunk)
		{
			switch ($chunk[0])
			{
				case self::TYPE_MODIFIER:
					if (!isset ($this->modifiers[$chunk[1]]))
						throw new FormatException ('unknown modifier "' . $chunk[1] . '"');

					$arguments = array ();

					for ($i = 2; $i < count ($chunk); ++$i)
						$arguments[] = $this->apply ($chunk[$i], $params);

					$result = call_user_func_array ($this->modifiers[$chunk[1]], $arguments);

					if ($result !== null)
						$out .= (string)$result;

					break;

				case self::TYPE_VARIABLE:
					$source =& $params;

					for ($i = 1; $i < count ($chunk); ++$i)
					{
						$array = (array)$source;

						if (isset ($array[$chunk[$i]]))
							$source =& $array[$chunk[$i]];
						else
						{
							unset ($source);

							break;
						}
					}

					if (isset ($source))
						$out .= (string)$source;

					break;

				default:
					$out .= $chunk[1];

					break;
			}
		}

		return $out;
	}

	private static function	convert (&$formatters, &$references, $encoding, $language, $path)
	{
		// Recurse into directory
		if (is_dir ($path))
		{
			foreach (scandir ($path) as $name)
			{
				if ($name !== '.' && $name !== '..')
					self::convert ($formatters, $references, $encoding, $language, $path . '/' . $name);
			}

			return;
		}

		// Read strings from XML file
		$document = new \DOMDocument ();

		if (!$document->load ($path))
			throw new \Exception ('can\'t load file "' . $path . '"');

		$locale = $document->documentElement;

		if ($locale->nodeType !== XML_ELEMENT_NODE || $locale->nodeName !== 'locale')
			throw new \Exception ('root node in file "' . $path . '" must be named "locale"');

		if (!$locale->hasAttribute ('language'))
			throw new \Exception ('root node in file "' . $path . '" is missing "language" attribute');

		if ($locale->getAttribute ('language') !== $language)
			return;

		foreach ($locale->childNodes as $child)
		{
			try
			{
				self::read ($formatters, $references, $encoding, $child, '');
			}
			catch (ParseException $exception)
			{
				throw new \Exception ($exception->getMessage () . ' in file "' . $path . '" at line ' . $exception->node->getLineNo ());
			}
		}
	}

	private static function	export ($input)
	{
		if (is_array ($input))
		{
			$out = '';

			if (array_reduce (array_keys ($input), function (&$result, $item) { return $result === $item ? $item + 1 : null; }, 0) !== count ($input))
			{
				foreach ($input as $key => $value)
					$out .= ($out !== '' ? ',' : '') . self::export ($key) . '=>' . self::export ($value);
			}
			else
			{
				foreach ($input as $value)
					$out .= ($out !== '' ? ',' : '') . self::export ($value);
			}

			return 'array(' . $out . ')';
		}

		return var_export ($input, true);
	}

	private static function parse ($encoding, $string, $outer, &$index)
	{
		$chunks = array ();
		$length = strlen ($string);
		$plain = '';

		while ($index < $length && ($outer || ($string[$index] !== self::EVALUATOR_END && $string[$index] !== self::MODIFIER_NEXT)))
		{
			// Found modifier or variable, parse name
			if ($string[$index] === self::EVALUATOR_BEGIN)
			{
				// Parse modifier or variable name
				for ($i = $index + 1; $i < $length && $string[$i] !== self::EVALUATOR_END && $string[$i] !== self::MODIFIER_DECLARE; )
					++$i;

				// Ensure name is followed by a valid character
				if ($i < $length)
				{
					// Flush pending plain string if any
					if ($plain !== '')
					{
						$chunks[] = array (self::TYPE_PLAIN, mb_convert_encoding ($plain, $encoding, self::ENCODING));
						$plain = '';
					}

					$name = substr ($string, $index + 1, $i - $index - 1);

					// Found modifier, parse arguments
					if ($string[$i] === self::MODIFIER_DECLARE)
					{
						$arguments = array ();

						for (++$i; $i < $length && $string[$i] !== self::EVALUATOR_END; )
						{
							if (count ($arguments) > 0)
							{
								if ($string[$i] !== self::MODIFIER_NEXT)
									break;

								++$i;
							}

							$arguments[] = self::parse ($encoding, $string, false, $i);
						}

						// Was a modifier, append to chunks
						if ($i < $length && $string[$i] === self::EVALUATOR_END)
						{
							$chunks[] = array_merge (array (self::TYPE_MODIFIER, $name), $arguments);
							$index = $i + 1;
						}

						// Syntax error, consider as plain
						else
							$plain .= $string[$index++];
					}

					// Was a value, append to chunks
					else
					{
						$chunks[] = array_merge (array (self::TYPE_VARIABLE), explode ('.', $name));
						$index = $i + 1;
					}
				}

				// Syntax error, consider as plain
				else
					$plain .= $string[$index++];
			}
			else
			{
				if ($string[$index] === self::ESCAPE && $index + 1 < $length)
					++$index;

				$plain .= $string[$index++];
			}
		}

		// Flush remaining characters
		if ($plain !== '')
			$chunks[] = array (self::TYPE_PLAIN, mb_convert_encoding ($plain, $encoding, self::ENCODING));

		return $chunks;
	}

	private static function	read (&$formatters, &$references, $encoding, $node, $prefix)
	{
		if ($node->nodeType !== XML_ELEMENT_NODE)
			return;

		switch ($node->nodeName)
		{
			case 'section':
				$prefix .= $node->getAttribute ('prefix');

				foreach ($node->childNodes as $child)
					self::read ($formatters, $references, $encoding, $child, $prefix);

				break;

			case 'string':
				if (!$node->hasAttribute ('key'))
					throw new ParseException ($node, 'missing "key" attribute');

				$key = $prefix . $node->getAttribute ('key');

				if ($node->hasAttribute ('alias'))
					$references[$key] = $node->getAttribute ('alias');
				else
				{
					$i = 0;

					$formatters[$key] = self::parse ($encoding, $node->nodeValue, true, $i);
				}

				break;
		}
	}
}

?>
