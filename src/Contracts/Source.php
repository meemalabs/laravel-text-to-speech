<?php

namespace Cion\TextToSpeech\Contracts;

interface Source
{
    /**
     * Handles in getting the text from source.
     *
     * @param  string  $data
     * @return string
     */
    public function handle(string $data): string;
}
