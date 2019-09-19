<?php

namespace bizley\tests;

use bizley\yii2\mercure\Publisher;

class MockPublisher extends Publisher
{
    public $useYii2Client = false;

    public function sendRequest(array $postData, string $jwt = null): string
    {
        return $jwt ?? 'null';
    }
}
