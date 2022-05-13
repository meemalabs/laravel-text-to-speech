<?php

namespace Cion\TextToSpeech\Converters;

use Cion\TextToSpeech\Contracts\Converter;
use Cion\TextToSpeech\Traits\HasLanguage;
use Cion\TextToSpeech\Traits\Sourceable;
use Cion\TextToSpeech\Traits\SSMLable;
use Cion\TextToSpeech\Traits\Storable;

class NullConverter implements Converter
{
    use Storable, Sourceable, HasLanguage, SSMLable;

    /**
     * Converts the text to speech.
     *
     * @param  mixed  $data
     * @param  array|null  $options
     * @return string
     */
    public function convert($data, array $options = null)
    {
        return $this->store($data, null);
    }
}
