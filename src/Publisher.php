<?php

declare(strict_types=1);

namespace bizley\yii2\mercure;

use Closure;
use RuntimeException;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use function call_user_func;
use function implode;
use function is_array;
use function is_string;
use function sprintf;
use function urlencode;

/**
 * Class Publisher
 * @package bizley\yii2\mercure
 * @author Paweł Bizley Brzozowski <pawel@positive.codes>
 *
 * Publishes an update to the hub.
 *
 * Based on the https://github.com/symfony/mercure package by Kévin Dunglas <dunglas@gmail.com>
 */
class Publisher extends Component
{
    /**
     * @var string
     */
    public $hubUrl;

    /**
     * @var string|Closure JWT or an anonymous function returning JWT
     */
    public $jwt;

    /**
     * @var string|array|object Name of the registered HTTP client component, its configuration array, or client object
     * itself. In case of $useYii2Client set to true (see below) you must take care of sending the request yourself.
     */
    public $httpClient;

    /**
     * @var bool Set to true if you are using yii2-httpclient as HTTP client
     * False means that custom HTTP client will be used with overridden sendRequest() method.
     */
    public $useYii2Client = true;

    /**
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if ($this->useYii2Client) {
            $this->httpClient = Instance::ensure($this->httpClient, 'yii\httpclient\Client');
        }
    }

    public function publish(Update $update): string
    {
        $postData = [
            'topic' => $update->getTopics(),
            'data' => $update->getData(),
            'target' => $update->getTargets(),
            'id' => $update->getId(),
            'type' => $update->getType(),
            'retry' => $update->getRetry(),
        ];

        $jwt = null;
        if (is_string($this->jwt)) {
            $jwt = $this->jwt;
        } elseif (null !== $this->jwt) {
            $jwt = call_user_func($this->jwt);
        }

        return $this->sendRequest($postData, $jwt);
    }

    /**
     * Sends prepared request to the hub.
     * In case of using custom HTTP client (non-`yii2` mode) you must override this method with your own implementation.
     * @param array $postData
     * @param string|null $jwt
     * @return string
     */
    public function sendRequest(array $postData, string $jwt = null): string
    {
        if (false === $this->useYii2Client) {
            throw new RuntimeException(
                'Publisher::sendRequest() method must be implemented first prior to using custom HTTP client.'
            );
        }

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        if (null !== $jwt) {
            $headers['Authorization'] = 'Bearer ' . $jwt;
        }

        return $this
            ->httpClient
            ->post($this->hubUrl, $this->buildQuery($postData), $headers)
            ->send()
            ->content;
    }

    public function buildQuery(array $data): string
    {
        $parts = [];
        foreach ($data as $key => $value) {
            if (null === $value) {
                continue;
            }
            if (is_array($value)) {
                foreach ($value as $v) {
                    $parts[] = $this->encode($key, $v);
                }
                continue;
            }
            $parts[] = $this->encode($key, $value);
        }

        return implode('&', $parts);
    }

    public function encode($key, $value): string
    {
        return sprintf('%s=%s', $key, urlencode((string)$value));
    }
}
