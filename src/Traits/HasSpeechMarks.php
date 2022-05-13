<?php

namespace Cion\TextToSpeech\Traits;

trait HasSpeechMarks
{
    /**
     * @var array
     */
    protected $speechMarks = [];

    /**
     * Sets the speech mark types.
     *
     * @param  array  $speechMarks
     * @return $this
     */
    public function speechMarks(array $speechMarks)
    {
        $this->speechMarks = $speechMarks;

        return $this;
    }
}
