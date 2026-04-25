<?php

use App\Enums\FileCollection;
use App\FileUploadData;
use App\Models\User;
use App\Models\Workspace;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    Storage::fake('public');

    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->user)->create();

    $this->service = app(FileUploadService::class);
});

test('it uploads a file', function () {
    $file = UploadedFile::fake()->image('logo.png');

    $data = new FileUploadData(
        model: $this->workspace,
        file: $file,
        collection: FileCollection::WORKSPACE_LOGO,
        path: "workspaces/{$this->workspace->id}/logo",
        uploadedBy: $this->user,
    );

    $this->service->upload($data);

    $this->assertDatabaseCount('files', 1);
    Storage::disk('public')
        ->assertExists("workspaces/{$this->workspace->id}/logo/{$file->hashName()}");
});

test('replace deletes existing file and uploads new one', function () {
    $firstFile = UploadedFile::fake()->image('first.png');
    $secondFile = UploadedFile::fake()->image('second.png');

    $this->service->upload(new FileUploadData(
        model: $this->workspace,
        file: $firstFile,
        collection: FileCollection::WORKSPACE_LOGO,
        path: "workspaces/{$this->workspace->id}/logo",
        uploadedBy: $this->user,
    ));

    $firstRecord = $this->workspace->logoFile()->first();
    Storage::disk('public')->assertExists($firstRecord->path);

    $this->service->replace(new FileUploadData(
        model: $this->workspace,
        file: $secondFile,
        collection: FileCollection::WORKSPACE_LOGO,
        path: "workspaces/{$this->workspace->id}/logo",
        uploadedBy: $this->user,
    ));

    $secondRecord = $this->workspace->fresh()->logoFile()->first();

    // old file gone
    Storage::disk('public')->assertMissing($firstRecord->path);
    $this->assertDatabaseMissing('files', ['id' => $firstRecord->id]);

    // new file exists
    Storage::disk('public')->assertExists($secondRecord->path);
    $this->assertDatabaseHas('files', ['id' => $secondRecord->id]);
    $this->assertDatabaseCount('files', 1);
});

test('replace uploads file even if no existing file is present', function () {
    $file = UploadedFile::fake()->image('logo.png');

    $this->service->replace(new FileUploadData(
        model: $this->workspace,
        file: $file,
        collection: FileCollection::WORKSPACE_LOGO,
        path: "workspaces/{$this->workspace->id}/logo",
        uploadedBy: $this->user,
    ));

    $this->assertDatabaseCount('files', 1);
    Storage::disk('public')->assertExists($this->workspace->fresh()->logoFile()->first()->path);
});

test('delete removes file from storage and database', function () {
    $file = UploadedFile::fake()->image('logo.png');

    $this->service->upload(new FileUploadData(
        model: $this->workspace,
        file: $file,
        collection: FileCollection::WORKSPACE_LOGO,
        path: "workspaces/{$this->workspace->id}/logo",
        uploadedBy: $this->user,
    ));

    $record = $this->workspace->logoFile()->first();
    Storage::disk('public')->assertExists($record->path);

    app(FileUploadService::class)->delete($this->workspace, FileCollection::WORKSPACE_LOGO);

    Storage::disk('public')->assertMissing($record->path);
    $this->assertDatabaseEmpty('files');
});

test('delete does nothing if no file exists', function () {
    $this->service->delete($this->workspace, FileCollection::WORKSPACE_LOGO);

    $this->assertDatabaseEmpty('files');
});
