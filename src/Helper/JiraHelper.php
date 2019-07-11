<?php

namespace App\Helper;

class JiraHelper
{
    public static function extractIssueKeyFromString(string $str): ?string
    {
        $matches = [];
        preg_match(getenv('JIRA_ISSUE_REGEX_PATTERN'), $str, $matches);

        if (1 === \count($matches)) {
            return $matches[0];
        }

        return null;
    }

    public static function buildIssueUrlFromIssueName(string $issueName): string
    {
        return getenv('JIRA_HOST') . '/browse/' . $issueName;
    }
}
