<?php

namespace Cion\TextToSpeech\Traits;

use Cion\TextToSpeech\Contracts\Source;

trait Sourceable
{
    /**
     * Determines where the text to convert from.
     *
     * @var string
     */
    protected $source;

    /**
     * Sets the source.
     *
     * @param  string  $source
     * @return $this
     */
    public function source(string $source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get text from source.
     *
     * @param  string  $data
     * @return string
     */
    protected function getTextFromSource(string $data): string
    {
        if ($this->source !== null) {
            app()->bind(Source::class, config('tts.sources.'.$this->source));
        }

        return app(Source::class)->handle($data);
    }
}
