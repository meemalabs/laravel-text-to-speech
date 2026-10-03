<?php

namespace Cion\TextToSpeech\Tests;

use Cion\TextToSpeech\Contracts\Source;
use Cion\TextToSpeech\Converters\SixtyDBConverter;
use Cion\TextToSpeech\Sources\TextSource;
use Cion\TextToSpeech\TextToSpeechManager;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

class SixtyDBConverterTest extends TestCase
{
    private $root;
    private $previousContainer;
    private $previousFacade;
    private $container;
    private $history = [];

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/sixtydb-'.bin2hex(random_bytes(8));
        mkdir($this->root);
        $this->previousContainer = Container::getInstance();
        $this->previousFacade = Facade::getFacadeApplication();
        $this->container = new Container();
        Container::setInstance($this->container);
        $this->container->instance('config', new Repository([
            'tts' => ['disk' => 'local', 'text_type' => 'text', 'driver' => 'sixtydb', 'services' => ['sixtydb' => ['api_key' => 'test', 'voice_id' => 'workspace-voice']]],
            'filesystems' => ['default' => 'local', 'disks' => ['local' => ['driver' => 'local', 'root' => $this->root]]],
        ]));
        $this->container->instance('filesystem', new FilesystemManager($this->container));
        $this->container->bind(Source::class, TextSource::class);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->container);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root.'/*') as $file) {
            unlink($file);
        }
        rmdir($this->root);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->previousFacade);
        Container::setInstance($this->previousContainer);
    }

    private function converter($body, $type = 'application/json', $status = 200)
    {
        $stack = HandlerStack::create(new MockHandler([new Response($status, ['Content-Type' => $type], $body)]));
        $stack->push(Middleware::history($this->history));

        return (new SixtyDBConverter(new Client(['handler' => $stack]), ['api_key' => 'test', 'voice_id' => 'workspace-voice']))->saveTo('speech.wav');
    }

    public function testItStoresPcmAsWavAndUsesWorkspaceVoice(): void
    {
        $converter = $this->converter(json_encode(['audioContent' => base64_encode("\x01\x00\x02\x00")]));
        $this->assertSame('speech.wav', $converter->language('en-US')->convert('Hello', ['speed' => 1.5, 'model' => 'chosen-model']));
        $wav = file_get_contents($this->root.'/speech.wav');
        $this->assertSame('RIFF', substr($wav, 0, 4));
        $this->assertSame("\x01\x00\x02\x00", substr($wav, 44));
        $request = $this->history[0]['request'];
        $this->assertSame('https://api.60db.ai/tts-synthesize', (string) $request->getUri());
        $this->assertSame('Bearer test', $request->getHeaderLine('Authorization'));
        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame('workspace-voice', $payload['voice_id']);
        $this->assertSame('en', $payload['target_language']);
        $this->assertSame(24000, $payload['audio_config']['sample_rate_hertz']);
        $this->assertSame('chosen-model', $payload['model_id']);
        $this->assertFalse($this->history[0]['options']['allow_redirects']);
    }

    public function testItHandlesNdjsonAndDoubleEncodedEnvelopes(): void
    {
        $inner = json_encode(['audioContent' => base64_encode("\x01\x00")]);
        $body = json_encode(['audioContent' => base64_encode($inner)])."\n".json_encode(['audio_base64' => base64_encode("\x02\x00")]);
        $this->converter($body, 'application/x-ndjson')->convert('Hello');
        $this->assertSame("\x01\x00\x02\x00", substr(file_get_contents($this->root.'/speech.wav'), 44));
    }

    public function testItAcceptsValidWavAndPcmBeginningWithBrace(): void
    {
        $pcm = "{\x00\x02\x00";
        $wav = 'RIFF'.pack('V', 40).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 24000, 48000, 2, 16).'data'.pack('V', 4).$pcm;
        $this->converter($wav, 'audio/wav')->convert('Hello');
        $this->assertSame($wav, file_get_contents($this->root.'/speech.wav'));
        $this->converter(json_encode(['audioContent' => base64_encode($pcm)]))->convert('Hello');
        $this->assertSame($wav, file_get_contents($this->root.'/speech.wav'));
    }

    public function testItRejectsErrorAndInvalidAudioWithoutSaving(): void
    {
        foreach (['{"success":false}', '{"audioContent":"!"}', '{"sample_rate":16000,"audioContent":"AAA="}', '{"audioContent":"AA=="}', '{}', 'not-json'] as $body) {
            try {
                $this->converter($body)->convert('Hello');
                $this->fail('Invalid synthesis response accepted');
            } catch (\RuntimeException $error) {
                $this->assertFileDoesNotExist($this->root.'/speech.wav');
            }
        }
        $this->expectException(\RuntimeException::class);
        $this->converter('denied', 'application/json', 401)->convert('Hello');
    }

    public function testItRejectsUnsupportedInputBeforeSending(): void
    {
        foreach ([['', []], ['Hello', ['speed' => 3]], ['Hello', ['format' => 'mp3']], ['Hello', ['model' => []]], [str_repeat('a', 5001), []]] as $case) {
            try {
                $this->converter('{}')->convert($case[0], $case[1]);
                $this->fail('Invalid input accepted');
            } catch (\InvalidArgumentException $error) {
                $this->assertSame([], $this->history);
            }
        }
        $this->expectException(\InvalidArgumentException::class);
        $this->converter('{}')->ssml()->convert('<speak>Hello</speak>');
    }

    public function testItRejectsCompressedTruncatedAndOversizedBodies(): void
    {
        foreach ([['ID3compressed', 'audio/pcm'], ['RIFF'.pack('V', 99).'WAVE', 'audio/wav'], ['unexpected', 'text/html'], [str_repeat("\x00", 32 * 1024 * 1024 + 2), 'audio/pcm']] as $case) {
            try {
                $this->converter($case[0], $case[1])->convert('Hello');
                $this->fail('Invalid binary audio accepted');
            } catch (\RuntimeException $error) {
                $this->assertFileDoesNotExist($this->root.'/speech.wav');
            }
        }
    }

    public function testStorageFailureDoesNotReturnASuccessPath(): void
    {
        $this->container->instance('filesystem', new class
        {
            public function disk($name)
            {
                return $this;
            }

            public function put($path, $bytes)
            {
                return false;
            }
        });
        Facade::clearResolvedInstances();
        $this->expectException(\RuntimeException::class);
        $this->converter('{"audioContent":"AAA="}')->convert('Hello');
    }

    public function testManagerResolvesTheNewDriver(): void
    {
        $this->assertInstanceOf(SixtyDBConverter::class, (new TextToSpeechManager($this->container))->engine('sixtydb'));
    }
}
