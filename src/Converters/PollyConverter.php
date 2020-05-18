<?php

namespace Cion\TextToSpeech\Converters;

use Aws\Polly\PollyClient;
use Cion\TextToSpeech\Contracts\Converter;
use Cion\TextToSpeech\Traits\Sourceable;
use Cion\TextToSpeech\Traits\Storable;
use Illuminate\Support\Arr;

class PollyConverter implements Converter
{
    use Storable, Sourceable;

    /**
     * Client instance of Polly.
     *
     * @var \Aws\Polly\PollyClient
     */
    protected $client;

    /**
     * Construct converter.
     *
     * @param PollyClient $client
     */
    public function __construct(PollyClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get the Polly Client.
     *
     * @return \Aws\Polly\PollyClient
     */
    public function getClient(): PollyClient
    {
        return $this->client;
    }

    /**
     * Converts the text to speech.
     *
     * @param string $data
     * @param array $options
     * @return string
     */
    public function convert(string $data, array $options = null)
    {
        $result = $this->client->synthesizeSpeech([
            'VoiceId'      => $this->voice($options),
            'OutputFormat' => $this->format($options),
            'Text'         => $this->getTextFromSource($data),
        ]);

        // Store audio file to disk
        return $this->store(
            $this->getTextFromSource($data),
            $this->getResultContent($result)
        );
    }

    /**
     * Get the text to speech voice ID.
     *
     * @param  array $options
     * @return string
     */
    protected function voice($options)
    {
        $default = config('tts.services.polly.voice_id', 'Amy');

        return Arr::get($options, 'voice', $default);
    }

    /**
     * Get the audio format.
     *
     * @param  array $options
     * @return string
     */
    protected function format($options)
    {
        $default = config('tts.output_format', 'mp3');

        return Arr::get($options, 'format', $default);
    }

    /**
     * Get the content of the result from AWS Polly.
     *
     * @param mixed $result
     * @return mixed
     */
    protected function getResultContent($result)
    {
        return $result->get('AudioStream')->getContents();
    }
}
