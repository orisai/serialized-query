<?php declare(strict_types = 1);

namespace Orisai\SerializedQuery;

use Orisai\Exceptions\Logic\InvalidArgument;
use function array_flip;
use function array_key_last;
use function count;
use function explode;
use function get_debug_type;
use function is_array;
use function is_bool;
use function is_object;
use function is_resource;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function substr;

final class QuerySerializer
{

	private const KeyValueSeparator = '_',
		PairsSeparator = '-',
		ArrayWrapper = '~';

	private const EscapeMap = [
		self::KeyValueSeparator => '二',
		self::PairsSeparator => '三',
		self::ArrayWrapper => '大',
	];

	/**
	 * Parses format key1_value-key2_~subkey_value-subkey2-value~ to array
	 *
	 * @return array<string, mixed>
	 */
	public static function parse(string $parameter): array
	{
		$parameters = [];
		//TODO - páry mohou být zanořené
		foreach (explode(self::PairsSeparator, $parameter) as $pair) {
			//TODO -
			$parsedPair = explode(self::KeyValueSeparator, $pair, 2);

			if (count($parsedPair) !== 2) {
				continue;
			}

			$key = self::unescape($parsedPair[0]);

			//TODO - neřeší ~ pouze na jedné straně
			//		- neřeší duplicitní ~
			//		- klíč taky může obsahovat ~ (ale bude escapovaná, v klíči nemůže být pole)
			//			- pokud se tam dostane, tak je klíč neplatný
			//			- nebo se v klíči escapovat nebude
			$value = $parsedPair[1];
			$value = str_starts_with($value, self::ArrayWrapper) && str_ends_with($value, self::ArrayWrapper)
				? self::parse(substr($value, 1, -1))
				: self::unescape($value);

			$parameters[$key] = $value;
		}

		return $parameters;
	}

	private static function unescape(string $string): string
	{
		return str_replace(
			self::EscapeMap,
			array_flip(self::EscapeMap),
			$string,
		);
	}

	/**
	 * @param array<int|string, mixed> $parameters
	 */
	public static function serialize(array $parameters): string
	{
		$string = '';
		$lastKey = array_key_last($parameters);
		foreach ($parameters as $key => $value) {
			$string .= self::escape((string) $key) . self::KeyValueSeparator . self::valueToString($value);

			if ($key !== $lastKey) {
				$string .= self::PairsSeparator;
			}
		}

		return $string;
	}

	/**
	 * @param mixed $value
	 */
	private static function valueToString($value): string
	{
		if (is_object($value) || is_resource($value)) {
			$type = get_debug_type($value);

			throw InvalidArgument::create()
				->withMessage("Value of type $type is not allowed for serialization.");
		}

		if (is_array($value)) {
			return self::ArrayWrapper . self::serialize($value) . self::ArrayWrapper;
		}

		if (is_bool($value)) {
			return $value ? '1' : '0';
		}

		return self::escape((string) $value);
	}

	private static function escape(string $string): string
	{
		return str_replace(
			array_flip(self::EscapeMap),
			self::EscapeMap,
			$string,
		);
	}

}
