<?php

namespace NixPHP\Queue\Core;

interface QueueJobInterface
{
    public function handle(): void;
}