<?php

declare(strict_types=1);

namespace tests\Unit;

use app\models\Color;
use Codeception\Test\Unit;

class ColorFilterTest extends Unit
{
    public function testIsNumericIdAcceptsPositiveIntegers(): void
    {
        $this->assertTrue(Color::isNumericId(1001));
        $this->assertTrue(Color::isNumericId('1001'));
        $this->assertTrue(Color::isNumericId('42'));
    }

    public function testIsNumericIdRejectsSlugsAndInvalidValues(): void
    {
        $this->assertFalse(Color::isNumericId('chernyy'));
        $this->assertFalse(Color::isNumericId('chernyy-2'));
        $this->assertFalse(Color::isNumericId(''));
        $this->assertFalse(Color::isNumericId(0));
        $this->assertFalse(Color::isNumericId('0'));
        $this->assertFalse(Color::isNumericId('1001abc'));
    }
}
