<?php

namespace Cion\TextToSpeech\Contracts;

interface Formatter
{
    /**
     * Handles the filename of the audio.
     *
     * @param  string  $text  The text source
     * @param  mixed  $audio  The result audio content.
     * @return string
     */
    public function handle($text, $audio = null): string;
}
