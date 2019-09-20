<?php

declare(strict_types=1);

namespace bizley\tests;

use bizley\yii2\mercure\Update;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
    public function dataProvider(): array
    {
        return [
            ['a', 'b'],
            [['a', 'b'], 'c'],
            ['a', 'b', ['c']],
            ['a', 'b', ['c', 'd']],
            ['a', 'b', ['c'], 'd'],
            ['a', 'b', ['c'], 'd', 'e'],
            ['a', 'b', ['c'], 'd', 'e', 1],
        ];
    }

    /**
     * @test
     * @param $topics
     * @param string $data
     * @param array $targets
     * @param string|null $id
     * @param string|null $type
     * @param int|null $retry
     * @dataProvider dataProvider
     */
    public function shouldReturnProperValues(
        $topics,
        string $data,
        array $targets = [],
        string $id = null,
        string $type = null,
        int $retry = null
    ): void {
        $update = new Update($topics, $data, $targets, $id, $type, $retry);

        $this->assertSame((array)$topics, $update->getTopics());
        $this->assertSame($data, $update->getData());
        $this->assertSame($targets, $update->getTargets());
        $this->assertSame($id, $update->getId());
        $this->assertSame($type, $update->getType());
        $this->assertSame($retry, $update->getRetry());
    }

    /**
     * @test
     */
    public function shouldThrowException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Update(1, '');
    }
}
