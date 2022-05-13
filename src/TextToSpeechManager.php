<?php

namespace Cion\TextToSpeech;

use Aws\Credentials\Credentials;
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

        $config = $this->config['tts.services.polly'];

        $credentials = $this->getCredentials($config['credentials']);

        $client = $this->setPollyClient($config, $credentials);

        return new PollyConverter(
           $client
       );
    }

    /**
     * Sets the polly client.
     *
     * @param  array  $config
     * @param  Credentials  $credentials
     * @return PollyClient
     */
    protected function setPollyClient(array $config, Credentials $credentials)
    {
        return new PollyClient([
            'version'     => $config['version'],
            'region'      => $config['region'],
            'credentials' => $credentials,
        ]);
    }

    /**
     * Get credentials of AWS.
     *
     * @param  array  $credentials
     * @return \Aws\Credentials\Credentials
     */
    protected function getCredentials(array $credentials)
    {
        return new Credentials($credentials['key'], $credentials['secret']);
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
