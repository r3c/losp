<?php

/*
** Locale String Pre-processor
*/

namespace Losp;

define ('LOSP',	'1.0.0.0');

class	Locale
{
	const	ESCAPE = '\\';
	const	EVALUATOR_BEGIN = '{';
	const	EVALUATOR_END = '}';
	const	MODIFIER_DECLARE = ':';
	const	MODIFIER_NEXT = ',';
	const	TYPE_MODIFIER = 0;
	const	TYPE_PLAIN = 1;
	const	TYPE_VARIABLE = 2;

	private	$formatters;
	private	$modifiers;

	public function	__construct ($encoding, $language, $source, $cache = null)
	{
		// Build or load language formatters from strings or cache
		if ($cache !== null && file_exists ($cache))
			require ($cache);
		else
		{
			if (!file_exists ($source))
				throw new \Exception ('unable to load strings from source');

			// Browse for language files and convert to formatters
			$formatters = array ();

			self::convert ($formatters, $encoding, $language, $source);

			// Save to cache
			if ($cache !== null)
			{
				$contents = '<?php ' .
					'$formatters = ' . self::export ($formatters, true) . '; ' .
				'?>';

				if (file_put_contents ($cache, $contents, LOCK_EX) === false)
					throw new \Exception ('unable to create cache');
			}
		}

		// Check variable consistency
		if (!isset ($formatters))
			throw new \Exception ('missing $formatters variable in cache');

		// Initalize members
		$this->formatters = $formatters;
		$this->modifiers = array ();	
	}

	public function	assign ($modifier, $callback)
	{
		$this->modifiers[$modifier] = $callback;
	}

	public function	format ($key, $params = null)
	{
		if (!isset ($this->formatters[$key]))
			throw new \Exception ('missing formatter for key "' . $key . '"');

		return $this->apply ($this->formatters[$key], $params);
	}

	private function	apply ($chunks, $params)
	{
		$null = null;
		$out = '';

		foreach ($chunks as $chunk)
		{
			switch ($chunk[0])
			{
				case self::TYPE_MODIFIER:
					if (!isset ($this->modifiers[$chunk[1]]))
						throw new \Exception ('missing modifier "' . $chunk[1] . '"');

					$arguments = array ();

					foreach ($chunk[2] as $argument)
						$arguments[] = $this->apply ($argument, $params);

					$out .= call_user_func_array ($this->modifiers[$chunk[1]], $arguments);

					break;

				case self::TYPE_VARIABLE:
					$source =& $params;

					foreach ($chunk[1] as $member)
					{
						if (is_object ($source) && isset ($source->$member))
							$source =& $source->$member;
						else if (is_array ($source) && isset ($source[$member]))
							$source =& $source[$member];
						else
						{
							$source =& $null;

							break;
						}
					}

					if ($source !== null)
						$out .= $source;

					break;

				default:
					$out .= $chunk[1];

					break;
			}
		}

		return $out;
	}

	private static function	convert (&$formatters, $encoding, $language, $path)
	{
		// Recurse into directory
		if (is_dir ($path))
		{
			foreach (scandir ($path) as $name)
			{
				if ($name !== '.' && $name !== '..')
					self::convert ($formatters, $encoding, $language, $path . '/' . $name);
			}

			return;
		}

		// Read strings from XML file
		$document = new \DOMDocument ();

		if (!$document->load ($path))
			throw new \Exception ('file "' . $path . '" can\'t be loaded');

		$locale = $document->documentElement;

		if ($locale->nodeType !== XML_ELEMENT_NODE || $locale->nodeName !== 'locale')
			throw new \Exception ('file "' . $path . '" has an invalid root node');

		if ($locale->getAttribute ('language') !== $language)
			return;

		foreach ($locale->childNodes as $child)
		{
			try
			{
				self::read ($formatters, $encoding, $child, '');
			}
			catch (\Exception $exception)
			{
				throw new \Exception ('file "' . $path . '" is invalid: ' . $exception->getMessage ());
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
						$chunks[] = array (self::TYPE_PLAIN, mb_convert_encoding ($plain, $encoding, 'UTF-8'));
						$plain = '';
					}

					$name = substr ($string, $index + 1, $i - $index - 1);

					// Was a modifier, parse arguments
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

						// Append modifier expression
						if ($i < $length && $string[$i] === self::EVALUATOR_END)
						{
							$chunks[] = array (self::TYPE_MODIFIER, $name, $arguments);
							$index = $i + 1;
						}

						// Syntax error, consider as plain
						else
							$plain .= $string[$index++];
					}

					// Was a value, append variable expression
					else
					{
						$chunks[] = array (self::TYPE_VARIABLE, explode ('.', $name));
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
			$chunks[] = array (self::TYPE_PLAIN, mb_convert_encoding ($plain, $encoding, 'UTF-8'));

		return $chunks;
	}

	private static function	read (&$formatters, $encoding, $node, $prefix)
	{
		if ($node->nodeType !== XML_ELEMENT_NODE)
			return;

		switch ($node->nodeName)
		{
			case 'section':
				foreach ($node->childNodes as $child)
					self::read ($formatters, $encoding, $child, $prefix . $node->getAttribute ('prefix'));

				break;

			case 'string':
				$key = $node->getAttribute ('key');
				$i = 0;

				if ($key !== '')
					$formatters[$prefix . $key] = self::parse ($encoding, $node->nodeValue, true, $i);

				break;
		}
	}
}

?>
