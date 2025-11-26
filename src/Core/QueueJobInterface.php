<?php

declare(strict_types=1);

namespace NixPHP\Queue\Core;

use NixPHP\Cli\Core\Output;

interface QueueJobInterface
{
    public function execute(Output $output): void;
}