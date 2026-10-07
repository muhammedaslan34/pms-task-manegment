<?php

namespace Tests\Feature;

use App\Livewire\Tasks\Create;
use App\Models\Task;
use App\Models\TaskImage;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class TaskScreenshotStorageTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.screenshots_disk' => 's3']);
    }

    private function screenshot(string $name = 'shot.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::PNG));
    }

    public function test_screenshots_are_stored_only_on_the_screenshots_disk(): void
    {
        Storage::fake('s3');
        Storage::fake('public');

        Livewire::test(Create::class)
            ->set('title', 'Bucket upload bug')
            ->set('screenshots', [$this->screenshot()])
            ->call('save')
            ->assertHasNoErrors();

        $image = Task::where('title', 'Bucket upload bug')->firstOrFail()->images()->sole();

        $this->assertMatchesRegularExpression('#^screenshots/[0-9a-f-]{36}\.png$#', $image->path);
        Storage::disk('s3')->assertExists($image->path);
        $this->assertSame(base64_decode(self::PNG), Storage::disk('s3')->get($image->path));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_screenshots_are_stored_under_the_configured_directory(): void
    {
        Storage::fake('s3');
        config(['filesystems.screenshots_directory' => 'local/screenshots']);

        Livewire::test(Create::class)
            ->set('title', 'Local upload')
            ->set('screenshots', [$this->screenshot()])
            ->call('save')
            ->assertHasNoErrors();

        $image = Task::where('title', 'Local upload')->firstOrFail()->images()->sole();

        $this->assertMatchesRegularExpression('#^local/screenshots/[0-9a-f-]{36}\.png$#', $image->path);
        Storage::disk('s3')->assertExists($image->path);
    }

    public function test_image_route_streams_the_file_from_the_screenshots_disk(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('screenshots/abc.png', base64_decode(self::PNG));

        $image = Task::factory()->create()->images()->create(['path' => 'screenshots/abc.png']);

        $response = $this->actingAs(User::factory()->create())->get(route('task-images.show', $image));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('max-age=31536000', $response->headers->get('Cache-Control'));
        $this->assertSame(base64_decode(self::PNG), $response->streamedContent());
    }

    public function test_image_route_requires_login(): void
    {
        Storage::fake('s3');
        $image = Task::factory()->create()->images()->create(['path' => 'screenshots/abc.png']);

        $this->get(route('task-images.show', $image))->assertRedirect(route('login'));
    }

    public function test_image_url_is_a_presigned_bucket_url_on_s3(): void
    {
        config([
            'filesystems.screenshots_url_ttl' => 60,
            'filesystems.disks.s3' => [
                'driver' => 's3',
                'key' => 'test-key',
                'secret' => 'test-secret',
                'region' => 'eu-central-2',
                'bucket' => 'test-bucket',
                'endpoint' => 'https://e2.example.test',
                'use_path_style_endpoint' => true,
            ],
        ]);
        Storage::forgetDisk('s3');

        $image = Task::factory()->create()->images()->create(['path' => 'screenshots/abc.png']);
        $url = $image->imageUrl();

        $this->assertStringStartsWith('https://e2.example.test/test-bucket/screenshots/abc.png?', $url);
        $this->assertStringContainsString('X-Amz-Signature=', $url);
        // The SDK subtracts the real clock from the expiry, so allow a one-second tick.
        $this->assertMatchesRegularExpression('/X-Amz-Expires=(3599|3600)(&|$)/', $url);
    }

    public function test_image_url_falls_back_to_the_proxy_route_for_local_disks(): void
    {
        config(['filesystems.screenshots_disk' => 'public']);
        Storage::fake('public');

        $image = Task::factory()->create()->images()->create(['path' => 'screenshots/abc.png']);

        $this->assertSame(route('task-images.show', $image), $image->imageUrl());
    }

    public function test_image_route_returns_404_when_the_file_is_missing(): void
    {
        Storage::fake('s3');

        $image = Task::factory()->create()->images()->create(['path' => 'screenshots/missing.png']);

        $this->actingAs(User::factory()->create())->get(route('task-images.show', $image))->assertNotFound();
    }

    public function test_failed_upload_does_not_create_a_task_or_image_row(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->andThrow(new \RuntimeException('bucket unreachable'));
        Storage::set('s3', $disk);

        Livewire::test(Create::class)
            ->set('title', 'Upload will fail')
            ->set('screenshots', [$this->screenshot()])
            ->call('save')
            ->assertHasErrors('screenshots.upload');

        $this->assertDatabaseMissing('tasks', ['title' => 'Upload will fail']);
        $this->assertSame(0, TaskImage::count());
    }
}
