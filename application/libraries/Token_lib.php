<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH . 'libraries/tokens/Token.php');

/**
 * Token library
 *
 * Library with utilities to manage tokens
 *
 * @link    github.com/jekkos/opensourcepos
 * @since   3.1
 * @author  SteveIreland
 */

class Token_lib
{
	private $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	/**
	 * Expands all of the tokens found in a given text string and returns the results.
	 */
	public function render($tokened_text)
	{

		// Transform legacy "$" tokens to their brace token equivalent
		if(strpos($tokened_text, '$') !== FALSE)
		{
			$tokened_text = str_replace('$YCO', '{YCO}', $tokened_text);
			$tokened_text = str_replace('$CO', '{CO}', $tokened_text);
			$tokened_text = str_replace('$SCO', '{SCO}', $tokened_text);
			$tokened_text = str_replace('$CU', '{CU}', $tokened_text);
		}

		// Apply the transformation for the "%" tokens if any are used
		if(strpos($tokened_text, '%') !== FALSE)
		{
			$tokened_text = strftime($tokened_text);
		}

		// Call scan to build an array of all of the tokens used in the text to be transformed
		$token_tree = $this->scan($tokened_text);

		if(empty($token_tree))
		{
			if(strpos($tokened_text, '%') !== FALSE)
			{
				return strftime($tokened_text);
			}
			else
			{
				return $tokened_text;
			}
		}

		$token_values = array();
		$tokens_to_replace = array();
		$this->generate($token_tree, $tokens_to_replace, $token_values);

		return str_replace($tokens_to_replace, $token_values, $tokened_text);
	}

	/**
	 * Parses out the all of the tokens enclosed in braces {} and subparses on the colon : character where supplied
	 */
	public function scan($text)
	{
		// Matches tokens with the following pattern: [$token:$length]
		preg_match_all('/
      \{             # [ - pattern start
      ([^\s\{\}:]+)  # match $token not containing whitespace : { or }
      (?:
      :              # : - separator
      ([^\s\{\}:]+)     # match $length not containing whitespace : { or }
      )?
      \}             # ] - pattern end
      /x', $text, $matches);

		$tokens = $matches[1];
		$lengths = $matches[2];

		$token_tree = array();
		for($i = 0; $i < count($tokens); $i++) {
			$token_tree[$tokens[$i]][$lengths[$i]] = $matches[0][$i];
		}

		return $token_tree;
	}

	public function generate($used_tokens, &$tokens_to_replace, &$token_values)
	{
		foreach($used_tokens as $token_code => $token_info)
		{
			// Generate value here based on the key value
			$token_value = (new Token())->replace($token_code);

			foreach($token_info as $length => $token_spec)
			{
				$tokens_to_replace[] = $token_spec;
				if(!empty($length))
				{
					$token_values[] = str_pad($token_value, $length, '0', STR_PAD_LEFT);
				}
				else
				{
					$token_values[] = $token_value;
				}
			}
		}
		return $token_values;
	}
}

?>
