<?php

namespace Cion\TextToSpeech\Sources;

use Cion\TextToSpeech\Contracts\Source as SourceContract;

class TextSource implements SourceContract
{
    /**
     * Handles in getting the text from source.
     *
     * @param  string  $data
     * @return string
     */
    public function handle(string $data): string
    {
        return $data;
    }
}
