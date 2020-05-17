<?php

namespace Cion\TextToSpeech\Converters;

use Cion\TextToSpeech\Contracts\Converter;
use Cion\TextToSpeech\Traits\Sourceable;
use Cion\TextToSpeech\Traits\Storable;

class NullConverter implements Converter
{
    use Storable, Sourceable;

    /**
     * Converts the text to speech.
     *
     * @param mixed $data
     * @param array $options
     * @return void
     */
    public function convert($data, array $options = null)
    {
        return $this->store($data);
    }
}
