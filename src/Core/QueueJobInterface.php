<?php

declare(strict_types=1);

namespace NixPHP\Queue\Core;

use NixPHP\Cli\Core\Output;

interface QueueJobInterface
{
    /**
     * @param Output $output
     *
     * @return void
     */
    public function execute(Output $output): void;
}