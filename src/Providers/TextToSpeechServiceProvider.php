<?php

namespace Cion\TextToSpeech\Providers;

use Cion\TextToSpeech\Contracts\Formatter;
use Cion\TextToSpeech\Contracts\Source;
use Cion\TextToSpeech\Facades\TextToSpeech;
use Cion\TextToSpeech\TextToSpeechManager;
use Illuminate\Support\ServiceProvider;

class TextToSpeechServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/config.php' => config_path('tts.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/config.php', 'tts');

        $this->registerTextToSpeechManager();

        $this->registerAliases();

        $this->bindFilenameFormatter();

        $this->bindSource();
    }

    /**
     * Registers the Text to speech manager.
     *
     * @return void
     */
    protected function registerTextToSpeechManager()
    {
        $this->app->singleton('tts', function ($app) {
            return new TextToSpeechManager($app);
        });
    }

    /**
     * Register aliases.
     *
     * @return void
     */
    protected function registerAliases()
    {
        $this->app->alias(TextToSpeech::class, 'TTS');
    }

    /**
     * Binds the filename formatter.
     *
     * @return void
     */
    protected function bindFilenameFormatter()
    {
        $this->app->bind(Formatter::class, config('tts.audio.formatter'));
    }

    /**
     * Binds the default source.
     *
     * @return void
     */
    protected function bindSource()
    {
        $this->app->bind(Source::class, config('tts.sources.'.config('tts.default_source')));
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'tts',
        ];
    }
}
