<?php

namespace Cion\TextToSpeech\Converters;

use Cion\TextToSpeech\Contracts\Converter;

class NullConverter implements Converter
{
    /**
     * Converts the text to speech.
     *
     * @param mixed $data
     * @param array $options
     * @return void
     */
    public function convert($data, array $options = null)
    {
        //
    }

    /**
     * Set where to store the converted file.
     *
     * @param string $disk
     * @return $this
     */
    public function disk(string $disk)
    {
        return $this;
    }

    /**
     * Set path to where to store the converted file.
     *
     * @param string $path
     * @return $this
     */
    public function saveTo(string $path)
    {
        return $this;
    }

    /**
     * Sets the source.
     *
     * @param string $source
     * @return $this
     */
    public function source(string $source)
    {
        return $this;
    }
}
