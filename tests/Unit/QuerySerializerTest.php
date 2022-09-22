<?php declare(strict_types = 1);

namespace Tests\Orisai\SerializedQuery\Unit;

use Generator;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\SerializedQuery\QuerySerializer;
use Orisai\VFS\VFS;
use PHPUnit\Framework\TestCase;
use stdClass;
use function file_put_contents;
use function fopen;

final class QuerySerializerTest extends TestCase
{

	/**
	 * @param array<int|string, mixed> $parameters
	 *
	 * @dataProvider provideBase
	 */
	public function testBase(string $query, array $parameters): void
	{
		self::assertSame($query, QuerySerializer::serialize($parameters));
		self::assertSame($parameters, QuerySerializer::parse($query));
	}

	public function provideBase(): Generator
	{
		yield [
			'',
			[],
		];

		yield [
			'key_',
			['key' => ''],
		];

		yield [
			'_',
			['' => ''],
		];

		yield [
			'_value',
			['' => 'value'],
		];

		yield [
			'key1_1-key2_2',
			[
				'key1' => '1',
				'key2' => '2',
			],
		];

		yield [
			'_1-key2_2',
			[
				'' => '1',
				'key2' => '2',
			],
		];

		yield [
			'key1_1-_2',
			[
				'key1' => '1',
				'' => '2',
			],
		];

		yield [
			'1_1',
			[
				1 => '1',
			],
		];

		//TODO - nefunguje
		//yield [
		//	'a_1-b_~c_2-d_3~-foo_~bar_~baz_4~~',
		//	[
		//		'a' => '1',
		//		'b' => [
		//			'c' => '2',
		//			'd' => '3',
		//		],
		//		'foo' => [
		//			'bar' => [
		//				'baz' => '4',
		//			],
		//		],
		//	]
		//];
	}

	/**
	 * @param array<int|string, mixed> $input
	 * @param array<mixed>             $output
	 *
	 * @dataProvider provideTypeCasting
	 */
	public function testTypeCasting(string $query, array $input, array $output): void
	{
		self::assertSame($query, QuerySerializer::serialize($input));
		self::assertSame($output, QuerySerializer::parse($query));
	}

	public function provideTypeCasting(): Generator
	{
		yield [
			'key_value',
			['key' => 'value'],
			['key' => 'value'],
		];

		yield [
			'key_1',
			['key' => true],
			['key' => '1'],
		];

		yield [
			'key_0',
			['key' => false],
			['key' => '0'],
		];

		yield [
			'key_',
			['key' => null],
			['key' => ''],
		];

		yield [
			'key_1',
			['key' => 1],
			['key' => '1'],
		];

		yield [
			'key_1.1',
			['key' => 1.1],
			['key' => '1.1'],
		];
	}

	public function testEscaping(): void
	{
		//yield [
		//	'~key_1~',
		//	['~key' => '1~'],
		//];

		//TODO
		self::assertTrue(true);
	}

	/**
	 * @param array<mixed> $output
	 *
	 * @dataProvider provideInvalidQuery
	 */
	public function testInvalidQuery(string $query, array $output): void
	{
		self::assertSame($output, QuerySerializer::parse($query));
	}

	public function provideInvalidQuery(): Generator
	{
		//TODO - kombinace s validními
		yield [
			'foo',
			[],
		];

		yield [
			'-',
			[],
		];

		yield [
			'~',
			[],
		];

		yield [
			'key_1-~',
			['key' => '1'],
		];

		yield [
			'~~',
			[],
		];

		yield [
			'-_',
			['' => ''],
		];

		yield [
			'_-',
			['' => ''],
		];
	}

	/**
	 * @param array<int|string, mixed> $parameters
	 *
	 * @dataProvider provideDuplicateKeys
	 */
	public function testDuplicateKeys(string $query, array $parameters): void
	{
		self::assertSame($parameters, QuerySerializer::parse($query));
	}

	public function provideDuplicateKeys(): Generator
	{
		yield [
			'key_1-key_2',
			[
				'key' => '2',
			],
		];

		yield [
			'_-_',
			[
				'' => '',
			],
		];
	}

	/**
	 * @param array<int|string, mixed> $parameters
	 *
	 * @dataProvider provideUnsupportedDataTypes
	 */
	public function testUnsupportedDataTypes(array $parameters, string $message): void
	{
		$this->expectException(InvalidArgument::class);
		$this->expectExceptionMessage($message);

		QuerySerializer::serialize($parameters);
	}

	public function provideUnsupportedDataTypes(): Generator
	{
		yield [
			[
				new stdClass(),
			],
			'Value of type stdClass is not allowed for serialization.',
		];

		yield [
			[
				'key' => [
					'foo' => new stdClass(),
				],
			],
			'Value of type stdClass is not allowed for serialization.',
		];

		$scheme = VFS::register();
		file_put_contents("$scheme://test.txt", '');

		yield [
			[
				fopen("$scheme://test.txt", 'r'),
			],
			'Value of type resource (stream) is not allowed for serialization.',
		];
	}

}
