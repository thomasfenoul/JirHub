<?php

namespace App\Handler;

use App\External\HerokuApi;
use GuzzleHttp\Exception\GuzzleException;

class HerokuHandler
{
    /** @var HerokuApi */
    private $herokuApi;

    public function __construct(HerokuApi $herokuApi)
    {
        $this->herokuApi = $herokuApi;
    }

    /**
     * @param array $appNames
     * @param array $dynoTypes
     * @param int $quantity
     * @throws GuzzleException
     */
    public function updateDynoQuantity(array $appNames, array $dynoTypes, int $quantity)
    {
        foreach ($appNames as $appName) {
            if (! $this->isAppManageable($appName)) {
                continue;
            }

            foreach ($dynoTypes as $dynoType) {
                $this->herokuApi->updateFormationQuantity($appName, $dynoType, $quantity);
            }
        }
    }

    private function isAppManageable(string $appName):bool
    {
        if (substr($appName, 0, 7) !== "chronos") {
            return false;
        }
        if (strpos($appName, "development") !== false) {
            return false;
        }
        if (strpos($appName, "staging") !== false) {
            return false;
        }
        if (strpos($appName, "production") !== false) {
            return false;
        }

        return true;
    }
}
