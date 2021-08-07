# Text-To-Speech Package for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cion/laravel-text-to-speech.svg?style=flat-square)](https://packagist.org/packages/cion/laravel-text-to-speech)
[![Test](https://github.com/ci-on/laravel-text-to-speech/workflows/Test/badge.svg?branch=master)](https://github.com/ci-on/laravel-text-to-speech/actions)
[![StyleCI](https://github.styleci.io/repos/264578171/shield?branch=master)](https://github.styleci.io/repos/264578171)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ci-on/laravel-text-to-speech/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ci-on/laravel-text-to-speech/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/cion/laravel-text-to-speech.svg?style=flat-square)](https://packagist.org/packages/cion/laravel-text-to-speech)
[![License](https://img.shields.io/github/license/ci-on/laravel-text-to-speech.svg?style=flat-square)](https://github.com/ci-on/laravel-text-to-speech/blob/master/LICENSE.md)
<!-- [
[![Build Status](wip)](ghactions)
 -->

This is a Text-To-Speech package for Laravel. Its primary intention is to use a shared API to easily convert text to speech.

Currently, the only supported driver is Amazon Polly. We are exploring the option to soon support Google WaveNet. Feel free to start a discussion on any possible driver.

## 💡 Usage

``` php
use Cion\TextToSpeech\Facades\TextToSpeech;

// convert text from a text file
$path = TextToSpeech::source('path')
    ->convert('/path/to/text/file');

// you may also pass the string directly to convert() method
$path = TextToSpeech::convert('Convert this string.');

// configure where to save the converted output. In this case, it would save the output file in the storage/output.mp3
$path = TextToSpeech::saveTo('output.mp3')
    ->convert('Hi this is a test.');

// store the converted output directly to S3
$path = TextToSpeech::disk('s3')
    ->saveTo('output.mp3')
    ->convert('Store me to S3.');

// convert website articles & blog posts to an audio file
$path = TextToSpeech::source('website')
    ->convert('https://medium.com/cloud-academy-inc/an-introduction-to-aws-polly-s3-and-php-479490bffcbd');

/**
 * Change the language to be used for the conversion.
 * Note: every language has a specific voice_id.
 * For example in ja-JP language we need to use either Mizuki or Takumi.
 *
 * We can pass an option array in convert to change the voice_id to be used
 **/
$options = ['voice' => 'Takumi'];
$path = TextToSpeech::language('ja-JP')
    ->convert('これはテストです', $options);

// use the SSML text type in the convert() method.
$path = TextToSpeech::ssml()
    ->convert('<speak>Hi There <break /> This is SSML syntax</speak');

// using speech marks.
$output = TextToSpeech::speechMarks(['sentence', 'word', 'viseme', 'ssml'])
    ->convert('This is a test'); // This will return an array.
```

## 🐙 Installation

You can install the package via composer:

```bash
composer require cion/laravel-text-to-speech
```

The package will automatically register itself.

You can optionally publish the config file with:

```bash
php artisan vendor:publish --provider="Cion\TextToSpeech\Providers\TextToSpeechServiceProvider" --tag="config"
```

Next, please add these keys to your `.env` file:

```env
TTS_DRIVER=polly
TTS_DISK=local
TTS_OUTPUT_FORMAT=mp3
TTS_LANGUAGE=en-US
TTS_TEXT_TYPE=text

AWS_VOICE_ID=Amy
AWS_ACCESS_KEY_ID=xxxxxxx
AWS_SECRET_ACCESS_KEY=xxxxxxx
AWS_DEFAULT_REGION=us-east-1
```

The following is the content of the published config file:

```php
return [

    /*
     * The disk on which to store converted mp3 files by default. Choose
     * one of the disks you've configured in config/filesystems.php.
     */
    'disk' => env('TTS_DISK', 'local'),

    /**
     * The default audio format of the converted text-to-speech audio file.
     * Currently, mp3 is the only supported format.
     */
    'output_format' => env('TTS_OUTPUT_FORMAT', 'mp3'),

    /**
     * The driver to be used for converting text-to-speech
     * You can choose polly, or null as of now.
     */
    'driver' => env('TTS_DRIVER', 'polly'),

    /**
     * The default language to be used.
     *
     * You may use any of the following:
     *
     * arb, cmn-CN, cy-GB, da-DK, de-DE, en-AU, en-GB, en-GB-WLS, en-IN, en-US,
     * es-ES, es-MX, es-US, fr-CA, fr-FR, is-IS, it-IT, ja-JP, hi-IN, ko-KR, nb-NO,
     * nl-NL, pl-PL, pt-BR, pt-PT, ro-RO, ru-RU, sv-SE, tr-TR
     */
    'language' => env('TTS_LANGUAGE', 'en-US'),

    'audio' => [
        /**
         * Default path to store the output file.
         */
        'path' => 'TTS',

        /**
         * Default filename formatter.
         */
        'formatter' => \Cion\TextToSpeech\Formatters\DefaultFilenameFormatter::class,
    ],

    /**
     * The default source that will be used.
     */
    'default_source' => 'text',

    /**
     * The default text type to be used.
     * You can use either text or ssml.
     */
    'text_type' => env('TTS_TEXT_TYPE', 'text'),

    /**
     * The source that can be used.
     * You can create your own source by implementing the Source interface.
     *
     * @see \Cion\TextToSpeech\Contracts\Source
     */
    'sources' => [
        'text'    => \Cion\TextToSpeech\Sources\TextSource::class,
        'path'    => \Cion\TextToSpeech\Sources\PathSource::class,
        'website' => \Cion\TextToSpeech\Sources\WebsiteSource::class,
    ],

    'services' => [
        'polly' => [
            /**
             * Voice ID to use for the synthesis.
             * You may use any of the following:.
             *
             * Aditi, Amy, Astrid, Bianca, Brian, Camila, Carla, Carmen, Celine,
             * Chantal, Conchita, Cristiano, Dora, Emma, Enrique, Ewa, Filiz,
             * Geraint, Giorgio, Gwyneth, Hans, Ines, Ivy, Jacek, Jan, Joanna,
             * Joey, Justin, Karl, Kendra, Kimberly, Lea, Liv, Lotte, Lucia,
             * Lupe, Mads, Maja, Marlene, Mathieu, Matthew, Maxim, Mia, Miguel,
             * Mizuki, Naja, Nicole, Penelope, Raveena, Ricardo, Ruben, Russell,
             * Salli, Seoyeon, Takumi, Tatyana, Vicki, Vitoria, Zeina, Zhiyu.
             */
            'voice_id' => env('AWS_VOICE_ID', 'Amy'),

            /**
             * You can request any or all of the speech mark types, but leave it empty if you don't use speech marks.
             * You may add any of the following:.
             *
             * sentence, word, viseme, ssml
             */
            'speech_marks' => [],

            /**
             * IAM Credentials from AWS.
             */
            'credentials' => [
                'key'     => env('AWS_ACCESS_KEY_ID', ''),
                'secret'  => env('AWS_SECRET_ACCESS_KEY', ''),
            ],

            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
        ],
    ],

];
```

## 🧪 Testing

``` bash
composer test
```

## 📈 Changelog

Please see our [releases](https://github.com/meemalabs/laravel-text-to-speech/releases) page for more information on what has changed recently.

## 💪🏼 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🏝 Community

For help, discussion about best practices, or any other conversation that would benefit from being searchable:

[Text-to-Speech on GitHub](https://github.com/meemalabs/laravel-text-to-speech/discussions)

For casual chit-chat with others using this package:

[Join the Meema Discord Server](https://discord.meema.io)

## 🚨 Security

Please review [our security policy](https://github.com/meemalabs/laravel-text-to-speech/security/policy) on how to report security vulnerabilities.

## 🙏🏼 Credits

- [Chris Breuer](https://github.com/Chris1904)
- [Rigel Kent Carbonel](https://github.com/luigel)
- [Harlequin Doyon](https://github.com/harlekoy)
- [Folks at Meema Labs & CION](https://github.com/meemalabs)
- [All Contributors](../../contributors)

## 📄 License

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for more information.

Made with ❤️ by Meema, Inc.
