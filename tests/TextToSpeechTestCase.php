<?php

namespace Cion\TextToSpeech\Tests;

use Cion\TextToSpeech\Providers\TextToSpeechServiceProvider;
use Orchestra\Testbench\TestCase;

class TextToSpeechTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [TextToSpeechServiceProvider::class];
    }
}
