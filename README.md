# Text to Speech Package for Laravel

![Test](https://github.com/ci-on/laravel-text-to-speech/workflows/Test/badge.svg?branch=master)
[![StyleCI](https://github.styleci.io/repos/264578171/shield?branch=master)](https://github.styleci.io/repos/264578171)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/cion/laravel-text-to-speech.svg?style=flat-square)](https://packagist.org/packages/cion/laravel-text-to-speech)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ci-on/laravel-text-to-speech/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ci-on/laravel-text-to-speech/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/cion/laravel-text-to-speech.svg?style=flat-square)](https://packagist.org/packages/cion/laravel-text-to-speech)
[![License](https://img.shields.io/github/license/ci-on/laravel-text-to-speech.svg?style=flat-square)](https://github.com/ci-on/laravel-text-to-speech/blob/master/LICENSE.md)
<!-- [
[![Build Status](wip)](ghactions)
 -->

This is a Text to Speech package for Laravel. Its primary intention is to use a shared API to easily convert text to speech.

Currently, the only supported driver is Amazon Polly. We are exploring the option to soon support Google WaveNet.

## Installation

You can install the package via composer:

```bash
composer require cion/laravel-text-to-speech
```

Add these configuration in your `.env` file.
```env
AWS_VOICE_ID=Amy
AWS_ACCESS_KEY_ID=xxxxxxx
AWS_SECRET_ACCESS_KEY=xxxxxxx
AWS_DEFAULT_REGION=us-east-1

TTS_DRIVER=polly
TTS_DISK=local
TTS_OUTPUT_FORMAT=mp3
```
## Usage

``` php
use Cion\TextToSpeech\Facades\TextToSpeech;

// You can use disk() methods that are supported in Laravel's Filesystem
TextToSpeech::disk('s3')
    ->saveTo('TTS/speech.mp3')
    ->source('path')
    ->convert('/path/to/file');
```

``` php
use Cion\TextToSpeech\Facades\TextToSpeech;

TextToSpeech::disk('local')
    ->saveTo($path)
    ->source('text')
    ->convert($text);
```

``` php
use Cion\TextToSpeech\Facades\TextToSpeech;

// You can also use source() in order to choose the source of the text to be converted
TextToSpeech::saveTo($path)
    ->source('path')
    ->convert($path);
```

``` php
use Cion\TextToSpeech\Facades\TextToSpeech;

// This will save the output in the `storage/TTS` directory
TextToSpeech::convert($text);
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email chris@cion.agency instead of using the issue tracker.

## Credits

- [Rigel Kent Carbonel](https://github.com/luigel)
- [Harlequin Doyon](https://github.com/harlekoy)
- [Folks at CION](https://github.com/ci-on)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

Made with ❤️ by CION Agency
