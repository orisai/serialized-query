# Serialized Query

(De)Serialize data for storing in URL query parameter

## Content

- [Setup](#setup)
- [Usage](#usage)
- [Type casting and values filtering](#type-casting-and-values-filtering)

## Setup

Install with [Composer](https://getcomposer.org)

```sh
composer require orisai/serialized-query
```

## Usage

Serialize values

```php
use Orisai\SerializedQuery\QuerySerializer;

$input = [
	'a' => 'val',
	'b' => [
		'c' => 1,
		'd' => 2,
	],
	'foo' => [
		'bar' => [
			'baz' => true,
		],
	],
];

$queryParam = QuerySerializer::serialize($parameters);
// Result
$queryParam === 'a_val-b_~c_1-d_2~-foo_~bar_~baz_1~~';
```

Parse values

```php
use Orisai\SerializedQuery\QuerySerializer;

$output = QuerySerializer::parse('a_val-b_~c_1-d_2~-foo_~bar_~baz_1~~');
// Result
$output === [
	'a' => 'val',
	'b' => [
		'c' => '1',
		'd' => '2',
	],
	'foo' => [
		'bar' => [
			'baz' => '1',
		],
	],
];
```

## Type casting and values filtering

Query string can't store anything except strings and as an input can't be trusted. Therefore, it is up to library user
to validate all values and convert them to correct data types.

Non-string and non-array values are converted to string with following logic:

```
true: '1'
false: '0'
null: ''
42: '42'
6.66: '6.66'
object: <exception is thrown>
resource: <exception is thrown>
```
