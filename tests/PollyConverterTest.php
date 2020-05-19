<?php

namespace Cion\TextToSpeech\Tests;

use Aws\Polly\PollyClient;
use Cion\TextToSpeech\Converters\PollyConverter;
use Illuminate\Support\Facades\Storage;

class PollyConverterTest extends TextToSpeechTestCase
{
    /**
     * @var PollyClient
     */
    protected $client;

    /**
     * Setup client and results.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->client = $this->getMockBuilder(PollyClient::class)
            ->setMethods(['synthesizeSpeech'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockResult = $this->getMockBuilder(\Aws\Result::class)
            ->setMethods(['get', 'getContents'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockResult->expects($this->once())
            ->method('get')
            ->with('AudioStream')
            ->willReturnSelf();

        $this->client->expects($this->once())
            ->method('synthesizeSpeech')
            ->withAnyParameters()
            ->willReturn($mockResult);
    }

    /** @test */
    public function it_should_save_to_the_specified_disk()
    {
        Storage::fake('s3');

        $converter = new PollyConverter($this->client);

        $path = $converter->disk('s3')
            ->convert('test');

        Storage::assertExists($path);
    }

    /** @test */
    public function it_should_save_to_the_specified_path()
    {
        Storage::fake('local');

        $converter = new PollyConverter($this->client);

        $path = $converter->disk('local')
            ->convert('test');

        Storage::assertExists($path);
    }

    /** @test */
    public function it_should_save_audio_file_format()
    {
        Storage::persistentFake('local');

        $converter = new PollyConverter($this->client);

        $path = $converter->disk('local')
            ->convert('test');

        Storage::assertExists($path);

        $this->assertTrue(strpos($path, '.mp3') !== false);
    }

    /** @test */
    public function it_should_specify_path_source_and_be_able_to_retrieve_text()
    {
        Storage::fake('s3');

        $converter = new PollyConverter($this->client);

        $path = $converter->disk('local')
                ->source('path')
                ->convert('tests/TestFiles/test.txt');

        Storage::assertExists($path);
    }
}
