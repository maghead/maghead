<?php

namespace Maghead\Runtime;

use Universal\Event\EventDispatcher;

trait DispatchTrait
{
    protected function getDispatcher()
    {
        return EventDispatcher::getInstance();
    }

    protected function triggerEvent()
    {
        return call_user_func_array([$this->getDispatcher(), 'trigger'], func_get_args());
    }
}
