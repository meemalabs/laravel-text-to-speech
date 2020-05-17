<?php

namespace Cion\TextToSpeech;

use Aws\Polly\PollyClient;
use Cion\TextToSpeech\Converters\NullConverter;
use Cion\TextToSpeech\Converters\PollyConverter;
use Exception;
use Illuminate\Support\Manager;

class TextToSpeechManager extends Manager
{
    /**
     * Get a driver instance.
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function engine($name = null)
    {
        return $this->driver($name);
    }

    /**
     * Create an Amazon Polly Converter instance.
     *
     * @return \Cion\TextToSpeech\Converters\PollyConverter
     */
    public function createPollyDriver()
    {
        $this->ensureAwsSdkIsInstalled();

        return new PollyConverter(
           $this->config['tts.services.polly']
       );
    }

    /**
     * Create a Null Converter instance.
     *
     * @return \Cion\TextToSpeech\Converters\NullConverter
     */
    public function createNullDriver()
    {
        return new NullConverter();
    }

    /**
     * Ensure the AWS SDK is installed.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function ensureAwsSdkIsInstalled()
    {
        if (! class_exists(PollyClient::class)) {
            throw new Exception(
                'Please install the AWS SDK PHP using `composer require aws/aws-sdk-php`.'
            );
        }
    }

    /**
     * Get the default Text to Speech driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        $driver = $this->container['config']['tts.driver'];

        if (is_null($driver)) {
            return 'null';
        }

        return $driver;
    }
}
