<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Tests\Form\DataTransformer;

use Propel\Bundle\PropelBundle\Form\DataTransformer\CollectionToArrayTransformer;
use Propel\Bundle\PropelBundle\Tests\TestCase;

class CollectionToArrayTransformerTest extends TestCase
{
    private $transformer;

    protected function setUp(): void
    {
        $this->transformer = new CollectionToArrayTransformer();
    }

    public function testTransform()
    {
        $result = $this->transformer->transform(new \PropelObjectCollection());

        $this->assertTrue(is_array($result));
        $this->assertCount(0, $result);
    }

    public function testTransformWithNull()
    {
        $result = $this->transformer->transform(null);

        $this->assertTrue(is_array($result));
        $this->assertCount(0, $result);
    }

    public function testTransformThrowsExceptionIfNotPropelObjectCollection()
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $this->transformer->transform(new DummyObject());
    }

    public function testTransformWithData()
    {
        $coll = new \PropelObjectCollection();
        $coll->setData(['foo', 'bar']);

        $result = $this->transformer->transform($coll);

        $this->assertTrue(is_array($result));
        $this->assertCount(2, $result);
        $this->assertEquals('foo', $result[0]);
        $this->assertEquals('bar', $result[1]);
    }

    public function testReverseTransformWithNull()
    {
        $result = $this->transformer->reverseTransform(null);

        $this->assertInstanceOf('\PropelObjectCollection', $result);
        $this->assertCount(0, $result->getData());
    }

    public function testReverseTransformWithEmptyString()
    {
        $result = $this->transformer->reverseTransform('');

        $this->assertInstanceOf('\PropelObjectCollection', $result);
        $this->assertCount(0, $result->getData());
    }

    public function testReverseTransformThrowsExceptionIfNotArray()
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $this->transformer->reverseTransform(new DummyObject());
    }

    public function testReverseTransformWithData()
    {
        $inputData = ['foo', 'bar'];

        $result = $this->transformer->reverseTransform($inputData);
        $data = $result->getData();

        $this->assertInstanceOf('\PropelObjectCollection', $result);

        $this->assertTrue(is_array($data));
        $this->assertCount(2, $data);
        $this->assertEquals('foo', $data[0]);
        $this->assertEquals('bar', $data[1]);
        $this->assertsame($inputData, $data);
    }
}

class DummyObject
{
}
