<?php

use Plank\Publisher\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * Get the path to a tests migration file.
 */
function migrationPath(string $path = ''): string
{
    return realpath(__DIR__).'/Helpers/Database/'.str($path)->trim('/');
}
