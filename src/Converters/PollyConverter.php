<?php

namespace Cion\TextToSpeech\Converters;

use Aws\Polly\PollyClient;
use Aws\Result;
use Cion\TextToSpeech\Contracts\Converter;
use Cion\TextToSpeech\Traits\HasLanguage;
use Cion\TextToSpeech\Traits\Sourceable;
use Cion\TextToSpeech\Traits\Storable;
use Illuminate\Support\Arr;

class PollyConverter implements Converter
{
    use Storable, Sourceable, HasLanguage;

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
        $text = $this->getTextFromSource($data);

        if ($this->isTextAboveLimit($text)) {
            $text = $this->getChunkText($text);
        }

        $result = $this->synthesizeSpeech($text, $options);

        if ($result instanceof Result) {
            // Store audio file to disk
            return $this->store(
                $this->getTextFromSource($data),
                $this->getResultContent($result)
            );
        }

        return $this->store(
            $this->getTextFromSource($data),
            $this->mergeOutputs($result)
        );
    }

    /**
     * Request to Amazon Polly to synthesize speech.
     *
     * @param string|array $text
     * @param array $options
     * @return array|\Aws\Result
     */
    protected function synthesizeSpeech($text, array $options = null)
    {
        if (is_string($text)) {
            return $this->client->synthesizeSpeech([
                'LanguageCode' => $this->getLanguage(),
                'VoiceId'      => $this->voice($options),
                'OutputFormat' => $this->format($options),
                'TextType'     => 'ssml',
                'Text'         => '<speak>'.$text.'</speak>',
            ]);
        }

        $results = [];

        foreach ($text as $item) {
            $result = $this->client->synthesizeSpeech([
                'LanguageCode' => $this->getLanguage(),
                'VoiceId'      => $this->voice($options),
                'OutputFormat' => $this->format($options),
                'Text'         => $item,
            ]);

            array_push($results, $result);
        }

        return $results;
    }

    /**
     * Merges the output from amazon polly.
     *
     * @return mixed
     */
    protected function mergeOutputs(array $results)
    {
        $mergedResult = null;
        foreach ($results as $result) {
            $mergedResult .= $this->getResultContent($result);
        }

        return $mergedResult;
    }

    /**
     * Checks the length of the text if more than 3000.
     *
     * @param string $text
     * @return bool
     */
    protected function isTextAboveLimit(string $text)
    {
        return strlen($text) > 2000;
    }

    /**
     * Chunk the given text into array.
     *
     * @param string $text
     * @param int $size
     * @return array
     */
    protected function getChunkText(string $text, int $size = 2000)
    {
        return explode("\n", wordwrap($text, $size));
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
     * Get the language.
     *
     * @return string
     */
    protected function getLanguage()
    {
        return $this->language ?? config('tts.language', 'en-US');
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
