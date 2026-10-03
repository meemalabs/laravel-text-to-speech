<?php

namespace Cion\TextToSpeech\Converters;

use Cion\TextToSpeech\Contracts\Converter;
use Cion\TextToSpeech\Traits\HasLanguage;
use Cion\TextToSpeech\Traits\Sourceable;
use Cion\TextToSpeech\Traits\SSMLable;
use Cion\TextToSpeech\Traits\Storable;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class SixtyDBConverter implements Converter
{
    use Storable, Sourceable, HasLanguage, SSMLable;

    protected $client;
    protected $config;

    public function __construct(ClientInterface $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function convert(string $data, ?array $options = null)
    {
        $options = $options ?? [];
        if ($this->path && pathinfo($this->path, PATHINFO_EXTENSION) && strtolower(pathinfo($this->path, PATHINFO_EXTENSION)) !== 'wav') {
            throw new InvalidArgumentException('60db output paths must use the .wav extension.');
        }
        $text = $this->getTextFromSource($data);
        $length = preg_match_all('/./us', $text);
        $voice = $options['voice'] ?? ($this->config['voice_id'] ?? '');
        $key = $this->config['api_key'] ?? '';
        $speed = $options['speed'] ?? ($this->config['speed'] ?? 1);
        if (! is_string($key) || trim($key) === '' || ! is_string($voice) || trim($voice) === '') {
            throw new InvalidArgumentException('60db requires an API key and workspace voice ID.');
        }
        if ($length === false || $length < 1 || $length > 5000 || trim($text) === '') {
            throw new InvalidArgumentException('60db requires 1 to 5000 UTF-8 characters.');
        }
        if (! is_numeric($speed) || ! is_finite((float) $speed) || $speed < 0.5 || $speed > 2) {
            throw new InvalidArgumentException('60db speed must be between 0.5 and 2.');
        }
        if (($options['format'] ?? 'wav') !== 'wav' || ($this->textType ?? config('tts.text_type', 'text')) !== 'text') {
            throw new InvalidArgumentException('60db supports plain text and WAV output in this driver.');
        }
        $payload = [
            'text' => $text, 'voice_id' => $voice, 'speed' => (float) $speed,
            'audio_config' => ['audio_encoding' => 'LINEAR16', 'sample_rate_hertz' => 24000],
            'output_format' => 'wav', 'timestamp_type' => 'NONE',
        ];
        $model = $options['model'] ?? ($this->config['model_id'] ?? null);
        if ($model !== null && (! is_string($model) || trim($model) === '')) {
            throw new InvalidArgumentException('60db model must be a nonempty string.');
        }
        if ($model) {
            $payload['model_id'] = $model;
        }
        if ($this->language !== null) {
            $payload['target_language'] = explode('-', $this->language)[0];
        }
        $response = $this->client->request('POST', 'https://api.60db.ai/tts-synthesize', [
            'headers' => ['Authorization' => 'Bearer '.$key], 'json' => $payload,
            'allow_redirects' => false, 'http_errors' => false, 'stream' => true,
            'connect_timeout' => 10, 'read_timeout' => 30, 'timeout' => 60,
        ]);
        $body = $response->getBody();
        try {
            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('60db synthesis failed (HTTP '.$response->getStatusCode().').');
            }
            $bytes = '';
            while (! $body->eof()) {
                $chunk = $body->read(65536);
                if ($chunk === '' && ! $body->eof()) {
                    throw new RuntimeException('60db response stream stopped making progress.');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > 32 * 1024 * 1024) {
                    throw new RuntimeException('60db response exceeds 32 MiB.');
                }
            }
            $kind = strtolower(trim(explode(';', $response->getHeaderLine('Content-Type'))[0]));
            if ($kind === 'application/json') {
                $pcm = $this->decode(json_decode($bytes, true, 32, JSON_THROW_ON_ERROR));
            } elseif (in_array($kind, ['application/x-ndjson', 'application/ndjson', 'text/plain'], true)) {
                $pcm = '';
                foreach (explode("\n", $bytes) as $line) {
                    if (trim($line) !== '') {
                        $pcm .= $this->decode(json_decode($line, true, 32, JSON_THROW_ON_ERROR));
                    }
                }
            } elseif (in_array($kind, ['audio/wav', 'audio/x-wav', 'audio/pcm', 'application/octet-stream'], true)) {
                $pcm = $this->pcm($bytes);
            } else {
                throw new RuntimeException('60db returned an unsupported content type.');
            }
            if ($pcm === '' || strlen($pcm) % 2 !== 0) {
                throw new RuntimeException('60db returned empty or incomplete PCM16 audio.');
            }
            $wav = 'RIFF'.pack('V', 36 + strlen($pcm)).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 24000, 48000, 2, 16)
                .'data'.pack('V', strlen($pcm)).$pcm;
            $this->ensurePathIsNotNull($text, $wav);
            if (! Storage::disk($this->disk ?: config('tts.disk'))->put($this->path, $wav)) {
                throw new RuntimeException('Could not store 60db audio.');
            }

            return $this->path;
        } catch (\JsonException $error) {
            throw new RuntimeException('60db returned invalid JSON.', 0, $error);
        } finally {
            $body->close();
        }
    }

    protected function decode($record, int $depth = 0): string
    {
        if ($depth > 4 || ! is_array($record) || ($record['success'] ?? true) === false || ! empty($record['error']) || ($record['type'] ?? '') === 'error') {
            throw new RuntimeException('60db returned an invalid synthesis response.');
        }
        foreach (['encoding', 'audio_encoding', 'output_format'] as $field) {
            if (isset($record[$field]) && (! is_string($record[$field]) || ! in_array(strtolower($record[$field]), ['linear16', 'pcm', 'pcm16', 'wav'], true))) {
                throw new RuntimeException('60db returned incompatible audio encoding.');
            }
        }
        foreach (['sample_rate' => 24000, 'sample_rate_hertz' => 24000, 'channels' => 1, 'bit_depth' => 16] as $field => $expected) {
            if (isset($record[$field]) && $record[$field] !== $expected) {
                throw new RuntimeException('60db returned incompatible audio metadata.');
            }
        }
        foreach (['audio_config', 'result', 'backendResponse'] as $field) {
            if (isset($record[$field])) {
                $audio = $this->decode($record[$field], $depth + 1);
                if ($field !== 'audio_config') {
                    return $audio;
                }
            }
        }
        $value = $record['audioContent'] ?? ($record['audio_base64'] ?? null);
        if ($value === null) {
            return '';
        }
        $audio = is_string($value) ? base64_decode($value, true) : false;
        if ($audio === false) {
            throw new RuntimeException('60db returned invalid base64 audio.');
        }
        $nested = substr($audio, 0, 1) === '{' ? json_decode($audio, true, 32) : null;

        return is_array($nested) ? $this->decode($nested, $depth + 1) : $this->pcm($audio);
    }

    protected function pcm(string $audio): string
    {
        if (substr($audio, 0, 4) !== 'RIFF') {
            if (in_array(substr($audio, 0, 4), ['OggS', 'fLaC'], true) || substr($audio, 0, 3) === 'ID3') {
                throw new RuntimeException('60db returned compressed audio instead of PCM16.');
            }

            return $audio;
        }
        if (strlen($audio) < 12 || substr($audio, 8, 4) !== 'WAVE' || unpack('Vsize', substr($audio, 4, 4))['size'] !== strlen($audio) - 8) {
            throw new RuntimeException('60db returned an invalid WAV.');
        }
        $validFormat = false;
        $pcm = '';
        for ($offset = 12; $offset + 8 <= strlen($audio);) {
            $id = substr($audio, $offset, 4);
            $size = unpack('Vsize', substr($audio, $offset + 4, 4))['size'];
            $offset += 8;
            if ($size > strlen($audio) - $offset) {
                throw new RuntimeException('60db returned truncated WAV audio.');
            }
            if ($id === 'fmt ' && $size >= 16) {
                $format = unpack('vcodec/vchannels/Vrate/Vbytes/vblock/vbits', substr($audio, $offset, 16));
                $validFormat = $format === ['codec' => 1, 'channels' => 1, 'rate' => 24000, 'bytes' => 48000, 'block' => 2, 'bits' => 16];
            } elseif ($id === 'data') {
                $pcm .= substr($audio, $offset, $size);
            }
            $offset += $size + $size % 2;
        }
        if ($offset !== strlen($audio) || ! $validFormat) {
            throw new RuntimeException('60db WAV must be mono PCM16 at 24000 Hz.');
        }

        return $pcm;
    }

    protected function getExtension()
    {
        return 'wav';
    }
}
