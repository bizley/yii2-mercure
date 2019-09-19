<?php

declare(strict_types=1);

namespace bizley\tests;

use bizley\yii2\mercure\Publisher;
use bizley\yii2\mercure\Update;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use yii\base\InvalidConfigException;

class PublisherTest extends TestCase
{
    /**
     * @test
     */
    public function shouldThrowExceptionOnWrongClientConfig(): void
    {
        $this->expectException(InvalidConfigException::class);

        new Publisher();
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnSendRequestCallWithCustomClient(): void
    {
        $this->expectException(RuntimeException::class);

        (new Publisher(['useYii2Client' => false]))->sendRequest([]);
    }

    /**
     * @test
     */
    public function shouldPublishWithStringJwt(): void
    {
        $update = new Update('a', 'b');
        $this->assertSame('abcdef', (new MockPublisher(['jwt' => 'abcdef']))->publish($update));
    }

    /**
     * @test
     */
    public function shouldPublishWithoutJwt(): void
    {
        $update = new Update('a', 'b');
        $this->assertSame('null', (new MockPublisher())->publish($update));
    }

    /**
     * @test
     */
    public function shouldPublishWithClosureJwt(): void
    {
        $update = new Update('a', 'b');
        $this->assertSame('closure', (new MockPublisher(['jwt' => function() {
            return 'closure';
        }]))->publish($update));
    }

    /**
     * @test
     */
    public function shouldEncode(): void
    {
        $publisher = new Publisher(['useYii2Client' => false]);
        $this->assertSame('a=b', $publisher->encode('a', 'b'));
        $this->assertSame('a=111', $publisher->encode('a', 111));
        $this->assertSame('a=%26%24%23', $publisher->encode('a', '&$#'));
    }

    public function dataProvider(): array
    {
        return [
            ['', []],
            ['a=b', ['a' => 'b']],
            ['a=b&c=d', ['a' => 'b', 'c' => 'd']],
            ['a=b', ['a' => 'b', 'c' => null]],
            ['a=b&a=c&a=d', ['a' => ['b', 'c', 'd']]],
        ];
    }

    /**
     * @test
     * @dataProvider dataProvider
     * @param string $expected
     * @param array $data
     */
    public function shouldBuildQuery(string $expected, array $data): void
    {
        $this->assertSame($expected, (new Publisher(['useYii2Client' => false]))->buildQuery($data));
    }
}
