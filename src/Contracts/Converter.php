<?php

namespace Cion\TextToSpeech\Contracts;

interface Converter
{
    /**
     * Converts text to speech.
     *
     * @param  string  $text
     * @param  array|null  $options
     * @return mixed
     */
    public function convert(string $text, array $options = null);

    /**
     * Set where to store the converted file.
     *
     * @param  string  $disk
     * @return $this
     */
    public function disk(string $disk);

    /**
     * Set path to where to save the converted file.
     *
     * @param  string  $path
     * @return $this
     */
    public function saveTo(string $path);

    /**
     * Sets the source where the text from.
     *
     * @param  string  $source
     * @return $this
     */
    public function source(string $source);

    /**
     * Sets the language to be used.
     *
     * @param  string  $language
     * @return $this
     */
    public function language(string $language);

    /**
     * Sets the text type to ssml default is text.
     *
     * @return $this
     */
    public function ssml();
}
