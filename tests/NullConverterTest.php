<?php

namespace Cion\TextToSpeech\Tests;

use Cion\TextToSpeech\Converters\NullConverter;

class NullConverterTest extends TextToSpeechTestCase
{
    /** @test */
    public function it_should_return_the_path()
    {
        $textToConvert = 'test';

        $result = (new NullConverter)->convert($textToConvert);

        $this->assertSame('TTS/'.md5($textToConvert).'.mp3', $result);
    }
}
