<?php

declare(strict_types=1);

namespace app\commands;

use bizley\yii2\mercure\Update;
use Yii;
use yii\console\Controller;

class RunController extends Controller
{
    public function actionIndex(): int
    {
        \var_dump(Yii::$app->publisher->publish(new Update(
            'http://example.com/books/1',
            json_encode(\random_int(10000, 99999))
        )));

        return 0;
    }
}
