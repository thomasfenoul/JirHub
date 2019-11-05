<?php

namespace App\Handler;

use App\Repository\GitHub\CommitRepository;

class ChangelogHandler
{
    /** @var CommitRepository */
    private $repository;

    public function __construct(CommitRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getProductionChangelog(): array
    {
        $result = $this->repository->getChangelog('master', 'dev');

        $messages = array_column(
            array_column($result['commits'], 'commit'),
            'message'
        );

        return array_map(function (string $message) {
            return explode(PHP_EOL, $message)[0];
        }, $messages);
    }
}
