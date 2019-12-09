<?php

namespace App\Repository\GitHub\Constant;

class PullRequestUpdatableFields
{
    const BODY  = 'body';
    const TITLE = 'title';

    public static function getConstants(): array
    {
        return [
            self::BODY,
            self::TITLE,
        ];
    }
}
