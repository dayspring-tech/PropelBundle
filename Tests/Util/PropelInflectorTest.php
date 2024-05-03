<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */
namespace Propel\Bundle\PropelBundle\Tests\Util;

use Propel\Bundle\PropelBundle\Tests\TestCase;
use Propel\Bundle\PropelBundle\Util\PropelInflector;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class PropelInflectorTest extends TestCase
{
    /**
     * @dataProvider dataProviderForTestCamelize
     */
    public function testCamelize($word, $expected)
    {
        $this->assertEquals($expected, PropelInflector::camelize($word));
    }

    public static function dataProviderForTestCamelize()
    {
        return [['', ''], [null, null], ['foo', 'foo'], ['Foo', 'foo'], ['fooBar', 'fooBar'], ['FooBar', 'fooBar'], ['Foo_bar', 'fooBar'], ['Foo_Bar', 'fooBar'], ['Foo Bar', 'fooBar'], ['Foo bar Baz', 'fooBarBaz'], ['Foo_Bar_Baz', 'fooBarBaz'], ['foo_bar_baz', 'fooBarBaz']];
    }
}
