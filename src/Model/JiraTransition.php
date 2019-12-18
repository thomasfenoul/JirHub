<?php

namespace App\Model;

class JiraTransition
{
    private $id;
    private $comment;

    public function __construct(int $id, string $comment = '')
    {
        $this->id      = $id;
        $this->comment = $comment;
    }

    public function toArray(): array
    {
        return [
            'update' => [
                'comment' => [
                    [
                        'add' => [
                            'body' => [
                                'type'    => 'doc',
                                'version' => 1,
                                'content' => [
                                    ['text' => $this->comment, 'type' => 'text'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'transition' => [
                'id' => (string) $this->id,
            ],
        ];
    }
}
