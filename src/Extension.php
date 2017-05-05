<?php

namespace Bolt\Extension\Postitief\MatchAll;

use Bolt\Extension\Postitief\MatchAll\Controllers\MatchAllController;
use Bolt\Extension\SimpleExtension;

/**
 * ExtensionName extension class.
 *
 * @author Your Name <you@example.com>
 */
class Extension extends SimpleExtension
{
    protected function registerFrontendControllers()
    {
        return [
//            '' => new MatchAllController(),
        ];
    }
}
