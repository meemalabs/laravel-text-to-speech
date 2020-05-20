<?php

namespace Cion\TextToSpeech\Traits;

trait SSMLable
{
    /**
     * @var string
     */
    protected $textType = 'text';

    /**
     * Sets the text type to ssml default is text
     *
     * @return $this
     */
    public function ssml()
    {
        $this->textType = 'ssml';

        return $this;
    }
}