<?php

namespace NixPHP\Queue\Core;

use NixPHP\Cli\Core\Output;

interface QueueJobInterface
{
    public function handle(Output $output): void;
}