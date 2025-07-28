<?php

namespace NixPHP\Queue;

use NixPHP\Queue\Core\Queue;
use function NixPHP\app;

function queue(): Queue
{
    return app()->container()->get('queue');
}