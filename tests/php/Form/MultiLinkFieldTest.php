<?php

namespace SilverStripe\LinkField\Tests\Form;

use ArrayIterator;
use ReflectionMethod;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\Model\List\ArrayList;
use PHPUnit\Framework\Attributes\DataProvider;

class MultiLinkFieldTest extends SapphireTest
{
    public static function getDataForProvider(): array
    {
        return [
            'empty string' => [
                'value' => '',
                'expected' => [],
            ],
            'non-comma-separated string' => [
                'value' => 'this is a string',
                'expected' => ['this is a string'],
            ],
            'non-comma-separated non-numeric string' => [
                'value' => ' 1, a, 2',
                'expected' => ['1, a, 2'],
            ],
            'comma-separated string' => [
                'value' => '1,2,3,4',
                'expected' => [1, 2, 3, 4],
            ],
            'comma-separated string with whitesapce' => [
                'value' => " 1,2 , 3, 4 \n ",
                'expected' => [1, 2, 3, 4],
            ],
            'number' => [
                'value' => 1234,
                'expected' => [1234],
            ],
            'arraylist' => [
                'value' => new ArrayList([['ID' => 1], ['ID' => 54]]),
                'expected' => [1, 54],
            ],
            'non-array iterable' => [
                'value' => new ArrayIterator([1, 'string', []]),
                'expected' => [1, 'string', []],
            ],
            'empty array' => [
                'value' => [],
                'expected' => [],
            ],
            'array with values' => [
                'value' => [1, 'string', []],
                'expected' => [1, 'string', []],
            ],
        ];
    }

    public static function provideConvertValueToArray(): array
    {
        return MultiLinkFieldTest::getDataForProvider();
    }

    #[DataProvider('provideConvertValueToArray')]
    public function testConvertValueToArray(mixed $value, array $expected): void
    {
        $field = new MultiLinkField('');
        $reflectionMethod = new ReflectionMethod($field, 'convertValueToArray');
        $reflectionMethod->setAccessible(true);
        $this->assertSame($expected, $reflectionMethod->invoke($field, $value));
    }

    public static function provideSetSubmittedValue(): array
    {
        return array_merge(MultiLinkFieldTest::getDataForProvider(), [
            'comma-separated string with brackets' => [
                'value' => '[1,2,3,4]',
                'expected' => [1, 2, 3, 4],
            ],
        ]);
    }

    #[DataProvider('provideSetSubmittedValue')]
    public function testSetSubmittedValue(mixed $value, array $expected): void
    {
        $field = new MultiLinkField('');
        $field->setSubmittedValue($value);
        $this->assertSame($expected, $field->getValue($value));
    }
}
