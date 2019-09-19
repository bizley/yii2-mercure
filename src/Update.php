<?php

declare(strict_types=1);

namespace bizley\yii2\mercure;

use InvalidArgumentException;
use function is_array;
use function is_string;

/**
 * Class Update
 * @package bizley\yii2\mercure
 * @author Paweł Bizley Brzozowski <pawel@positive.codes>
 *
 * Represents an update to send to the hub.
 *
 * @see https://github.com/dunglas/mercure/blob/master/spec/mercure.md#hub
 * @see https://github.com/dunglas/mercure/blob/master/hub/update.go
 *
 * Based on the https://github.com/symfony/mercure package by Kévin Dunglas <dunglas@gmail.com>
 */
final class Update
{
    /**
     * @var array
     */
    private $topics;

    /**
     * @var string
     */
    private $data;

    /**
     * @var array
     */
    private $targets;

    /**
     * @var string|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $type;

    /**
     * @var int|null
     */
    private $retry;

    /**
     * Update constructor.
     * @param array|string $topics
     * @param string $data
     * @param array $targets
     * @param string|null $id
     * @param string|null $type
     * @param int|null $retry
     */
    public function __construct($topics, string $data, array $targets = [], string $id = null, string $type = null, int $retry = null)
    {
        if (!is_array($topics) && !is_string($topics)) {
            throw new InvalidArgumentException('$topics must be an array of strings or a string');
        }
        $this->topics = (array)$topics;
        $this->data = $data;
        $this->targets = $targets;
        $this->id = $id;
        $this->type = $type;
        $this->retry = $retry;
    }

    public function getTopics(): array
    {
        return $this->topics;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getTargets(): array
    {
        return $this->targets;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getRetry(): ?int
    {
        return $this->retry;
    }
}
