<?php

namespace Maghead\Extensions\Metadata;

use DateTime;

trait AgeModelTrait
{
    public function getAge(DateTime $since = null)
    {
        $createdOn = $this->getCreatedAt();
        if (!$since) {
            $since = new DateTime();
        }
        return $since->diff($createdOn);
    }
}
