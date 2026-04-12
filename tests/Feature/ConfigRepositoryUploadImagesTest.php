<?php

namespace Tests\Feature;

use Gametech\Core\Models\Config;
use Gametech\Core\Repositories\ConfigRepository;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConfigRepositoryUploadImagesTest extends TestCase
{
    public function test_upload_images_uses_fileuploadnew_when_present(): void
    {
        Storage::fake();

        $file = UploadedFile::fake()->image('favicon.png');
        $request = Request::create('/admin/config', 'POST', [], [], [
            'fileuploadnew' => $file,
        ]);
        $this->app->instance('request', $request);

        $repository = new ConfigRepository($this->app);
        $order = new class extends Config
        {
            public function save(array $options = []): bool
            {
                return true;
            }
        };

        $repository->uploadImages([], $order);

        $this->assertNotEmpty($order->favicon);
        Storage::assertExists('img/'.$order->favicon);
        Storage::assertExists('img/favicon.png');
    }

    public function test_upload_images_ignores_non_file_inputs(): void
    {
        Storage::fake();

        $request = Request::create('/admin/config', 'POST', [
            'fileupload' => 'undefined',
        ]);
        $this->app->instance('request', $request);

        $repository = new ConfigRepository($this->app);
        $order = new class extends Config
        {
            public function save(array $options = []): bool
            {
                return true;
            }
        };

        $repository->uploadImages([], $order);

        $this->assertSame([], Storage::allFiles('img'));
    }
}
