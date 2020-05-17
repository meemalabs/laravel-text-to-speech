<?php

namespace Cion\TextToSpeech\Traits;

use Illuminate\Support\Facades\Storage;

trait Storable
{
    /**
     * Determines where to save the converted file.
     *
     * @var string
     */
    protected $disk;

    /**
     * Determines the path where to save the converted file.
     *
     * @var string
     */
    protected $path;

    /**
     * Set where to store the converted file.
     *
     * @param string $disk
     * @return $this
     */
    public function disk(string $disk)
    {
        $this->disk = $disk;

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
        $this->path = $path;

        return $this;
    }

    /**
     * Execute the store.
     *
     * @param mixed $resultContent
     * @return string
     */
    protected function store($resultContent)
    {
        $this->ensurePathIsNotNull();

        $storage = Storage::disk($this->disk ?: config('tts.disk'));

        $storage->put($this->path, $resultContent);

        return $this->path;
    }

    /**
     * Ensures the path not to be null if it is null it will set a default path.
     *
     * @return void
     */
    protected function ensurePathIsNotNull()
    {
        $filename = $this->path ?: 'TTS/'.now()->timestamp;

        if (! $this->hasExtension($filename)) {
            $filename .= '.'.$this->getExtension();
        }

        $this->path = $filename;
    }

    /**
     * Determine if filename includes file extension.
     *
     * @param  string  $filename
     * @return bool
     */
    protected function hasExtension($filename)
    {
        return (bool) pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * Get audio file extension.
     *
     * @return string
     */
    protected function getExtension()
    {
        return config('tts.output_format');
    }
}
