<?php

namespace Cion\TextToSpeech\Formatters;

use Cion\TextToSpeech\Contracts\Formatter as FormatterContract;

class DefaultFilenameFormatter implements FormatterContract
{
    /**
     * Handles the filename of the audio.
     *
     * @param  string  $text  The text source.
     * @param  mixed  $audio  The result audio content.
     * @return string
     */
    public function handle($text, $audio = null): string
    {
        return md5($text);
    }
}
