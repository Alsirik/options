<?php

namespace Siryk\Options\Tests;

use LogicException;
use Siryk\Options\Options;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{

    public function testHas()
    {
        $options = new Options([
            'exist' => 1,
            'setNull' => null,
        ]);
        self::assertTrue($options->has('exist'));
        self::assertTrue($options->has('setNull'));
        self::assertFalse($options->has('has key'));

    }

    public function testSet()
    {
        $options = new Options();
        $options->set('field', 12);
        self::assertEquals(12, $options->get('field'));
    }

    public function testSetMany()
    {
        $options = new Options();
        $options->setMany([
            'one' => 1,
            'two ' => 2,
        ]);
        self::assertEquals(1, $options->get('one'));
        self::assertEquals(2, $options->get('two'));
        self::assertEquals(2, $options->get('two '));
    }

    public function testConstructor()
    {
        $options = new Options([
            'one' => 1,
            'two ' => 2,
        ]);
        self::assertEquals(1, $options->get('one'));
        self::assertEquals(2, $options->get('two'));
        self::assertEquals(null, $options->get('notExist'));
        self::assertEquals(12, $options->get('notExistDefault', 12));
    }

    public function testSetDefaultCallback()
    {
        $options = new Options([]);
        self::assertEquals(null, $options->get('one'));
        self::assertEquals(5, $options->get('one', 5));
        $options->setDefaultCallback('one', function () {
            return 2 * 2;
        });
        self::assertEquals(4, $options->get('one'));
        $options->set('one', 'concreteValue');
        self::assertEquals('concreteValue', $options->get('one'));
    }

    public function testOnly()
    {
        $options = new Options([
            'one' => 1,
            'two' => 2,
            'ten' => '10'
        ]);
        $options->set('three', 3);
        $options->setDefaultCallback('four', function () {
            return 4;
        });

        $res = $options->only(['one', 'three', 'four', 'notExist'], 'default-value');
        self::assertSame(['one' => 1, 'three' => 3, 'four' => 4, 'notExist' => 'default-value'], $res);
    }

    public function testGetAll()
    {
        $options = new Options([
            'one' => 1,
            'ten' => '10'
        ]);
        $options->setDefaultCallback('four', function () {
            return 4;
        });
        $options->setDefaultCallback('ten', function () {
            return 'TEN';
        });
        $all = $options->getAll();
        self::assertSame(['one' => 1, 'ten' => '10', 'four' => 4], $all);
    }

    public function testExceptionOnMissingRequiredField()
    {
        $options = new Options();
        $options->addRequiredField('one');
        $options->addRequiredField('two');
        $options->addRequiredField(['one', 'three']);
        $options->set('three', '3');
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('missing required option(s): "one", "two"');
        $options->getAll();
    }

    public function testExceptionOnUnrecognizedField()
    {
        $options = new Options();
        $options->addAvailableField('one');
        $options->addRequiredField('two');
        $options->set('two', 2);
        $options->set('three', 3);

        $this->expectException(LogicException::class);
        $options->getAll();
    }

    public function testDefaultCallbackIsRunOnce()
    {
        $options = new Options();
        $options->setDefaultCallback('some', function () {
            return uniqid('', true);
        });
        $first = $options->get('some');
        $second = $options->get('some');
        self::assertSame($first, $second);
    }

}
