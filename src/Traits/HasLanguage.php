<?php

namespace Cion\TextToSpeech\Traits;

trait HasLanguage
{
    /**
     * @var string
     */
    protected $language;

    /**
     * Sets the language to be used.
     *
     * @param  string  $language
     * @return $this
     */
    public function language(string $language)
    {
        $this->language = $language;

        return $this;
    }
}
