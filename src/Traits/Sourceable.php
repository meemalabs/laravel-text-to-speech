<?php

namespace Cion\TextToSpeech\Traits;

trait Sourceable
{
    /**
     * Determines where the text to convert from.
     *
     * @var string
     */
    protected $source = 'text';

    /**
     * Sets the source.
     *
     * @param string $source
     * @return $this
     */
    public function source(string $source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get text from source.
     * @param mixed $data
     * @return string
     */
    protected function getTextFromSource($data)
    {
        switch ($this->source) {
            case 'text':
                return $data;
            case 'path':
                return $this->getTextFromPath($data);
            case 'website':
                return $this->getTextFromWebsite($data);

        }
    }

    /**
     * Get text from the path.
     *
     * @param string $path
     * @return string
     */
    protected function getTextFromPath(string $path)
    {
        return file_get_contents($path);
    }

    /**
     * Get text from the website.
     *
     * @param string $url
     * @return string
     */
    protected function getTextFromWebsite(string $url)
    {
        // WIP
    }
}
