<?php

use App\Enums\FileCollection;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    Storage::fake('public');

    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->user)->create();
});

test('workspace owner can update the name and description of their workspace', function () {
    $member = User::factory()->create();
    $this->workspace->users()->attach($member->id);

    $response = $this->actingAs($this->user)
        ->patchJson(route('workspaces.update', [
            'workspace' => $this->workspace->id,
        ]), [
            'name'        => 'Updated Workspace Name',
            'description' => 'Updated Workspace Description',
        ]);

    $response->assertStatus(302); // back

    $this->assertDatabaseHas('workspaces', [
        'id'          => $this->workspace->id,
        'name'        => 'Updated Workspace Name',
        'description' => 'Updated Workspace Description',
    ]);

});

test('workspace members can update the name and description of the workspace', function () {
    $member = User::factory()->create();
    $this->workspace->users()->attach($member->id);

    $response = $this->actingAs($member)
        ->patchJson(route('workspaces.update', [
            'workspace' => $this->workspace->id,
        ]), [
            'name'        => 'Updated Workspace Name',
            'description' => 'Updated Workspace Description',
        ]);

    $response->assertStatus(302);
    $this->assertDatabaseHas('workspaces', [
        'id'          => $this->workspace->id,
        'name'        => 'Updated Workspace Name',
        'description' => 'Updated Workspace Description',
    ]);
});

test('non-members cannot update the workspace they are not member of', function () {
    $nonMember = User::factory()->create();

    $response = $this->actingAs($nonMember)
        ->patchJson(route('workspaces.update', [
            'workspace' => $this->workspace->id,
        ]), [
            'name'        => 'Updated Workspace Name',
            'description' => 'Updated Workspace Description',
        ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('workspaces', [
        'id'          => $this->workspace->id,
        'name'        => $this->workspace->name,
        'description' => $this->workspace->description,
    ]); // nothing changes

});

test('workspace name cannot exceed 65 characters', function () {
    $this->actingAs($this->user)
        ->patchJson(route('workspaces.update',
            [
                'workspace' => $this->workspace->id,
            ]), [
                'name' => str_repeat('a', 66),
            ])->assertStatus(422);
});

test('workspace owner can update their workspace logo', function () {
    $fileUploaded = UploadedFile::fake()->image('logo.png');
    $response = $this->actingAs($this->user)
        ->patchJson(route('workspaces.update', [
            'workspace' => $this->workspace->id,
        ]), [
            'logo' => $fileUploaded,
        ]);

    $response->assertStatus(302);

    $file = $this->workspace->logoFile()->first();
    Storage::disk('public')->assertExists($file->path);

    $expectedPath = "workspaces/{$this->workspace->id}/logo/{$fileUploaded->hashName()}";

    $this->assertDatabaseHas('files', [
        'id'            => $file->id,
        'collection'    => FileCollection::WORKSPACE_LOGO->value,
        'fileable_id'   => $this->workspace->id,
        'fileable_type' => Workspace::class,
        'disk'          => 'public',
        'path'          => $expectedPath,
    ]);
    $this->assertEquals($expectedPath, $file->path);
});

test('workspace members can update their workspace logo', function () {
    $member = User::factory()->create();
    $this->workspace->users()->attach($member->id);

    $fileUploaded = UploadedFile::fake()->create('logo.png', '500', 'image/png');
    $response = $this->actingAs($member)
        ->patchJson(route('workspaces.update', [
            'workspace' => $this->workspace->id,
        ]), [
            'logo' => $fileUploaded,
        ]);

    $response->assertStatus(302);

    $file = $this->workspace->logoFile()->first();
    Storage::disk('public')->assertExists($file->path);

    $expectedPath = "workspaces/{$this->workspace->id}/logo/{$fileUploaded->hashName()}";

    $this->assertDatabaseHas('files', [
        'id'            => $file->id,
        'collection'    => FileCollection::WORKSPACE_LOGO->value,
        'fileable_id'   => $this->workspace->id,
        'fileable_type' => Workspace::class,
        'disk'          => 'public',
        'path'          => $expectedPath,
    ]);

    $this->assertEquals($expectedPath, $file->path);
});

test('non-members cannot update the workspace logo they are not member of', function () {
    $nonMember = User::factory()->create();

    $fileUploaded = UploadedFile::fake()->create('logo.png', '500', 'image/png');
    $response = $this->actingAs($nonMember)
        ->patchJson(route('workspaces.update', [
            'workspace' => $this->workspace->id,
        ]), [
            'logo' => $fileUploaded,
        ]);

    $response->assertStatus(403);

    $this->assertDatabaseEmpty('files'); // not uploaded
});

test('deletes existing logo of a workspace if new logo has been added', function () {
    // First upload
    $firstFileUploaded = UploadedFile::fake()->image('logo.png');
    $this->actingAs($this->user)
        ->patchJson(route('workspaces.update',
            [
                'workspace' => $this->workspace->id,
            ]), [
                'logo' => $firstFileUploaded,
            ])
        ->assertStatus(302);

    $firstFile = $this->workspace->logoFile()->first();
    Storage::disk('public')->assertExists($firstFile->path);

    // Second upload
    $secondFileUploaded = UploadedFile::fake()->image('logo2.png');
    $this->actingAs($this->user)
        ->patchJson(route('workspaces.update',
            [
                'workspace' => $this->workspace->id,
            ]), [
                'logo' => $secondFileUploaded,
            ])
        ->assertStatus(302);

    $secondFile = $this->workspace->fresh()->logoFile()->first();

    // Old file deleted from storage and database
    Storage::disk('public')->assertMissing($firstFile->path);
    $this->assertDatabaseMissing('files', ['id' => $firstFile->id]);

    // New file exists in storage and database
    $expectedSecondFilePath = "workspaces/{$this->workspace->id}/logo/{$secondFileUploaded->hashName()}";
    Storage::disk('public')->assertExists($secondFile->path);
    $this->assertDatabaseHas('files', [
        'id'            => $secondFile->id,
        'collection'    => FileCollection::WORKSPACE_LOGO->value,
        'fileable_id'   => $this->workspace->id,
        'fileable_type' => Workspace::class,
        'disk'          => 'public',
        'path'          => $expectedSecondFilePath,
    ]);
    $this->assertEquals($expectedSecondFilePath, $secondFile->path);
});

test('logo upload validates file type', function (string $filename, string $mimeType, int $status, bool $realImage) {
    $file = $realImage
        ? UploadedFile::fake()->image($filename)
        : UploadedFile::fake()->create($filename, 500, $mimeType);

    $response = $this->actingAs($this->user)
        ->patchJson(route('workspaces.update',
            [
                'workspace' => $this->workspace->id,
            ]), [
                'logo' => $file,
            ]);

    $response->assertStatus($status);

    if ($status === 422) {
        $response->assertJsonValidationErrors(['logo']);
        $this->assertDatabaseEmpty('files');
    } else {
        $this->assertDatabaseCount('files', 1);
    }

})->with([
    'jpeg is allowed' => ['image.jpeg', 'image/jpeg', 302, true],
    'png is allowed'  => ['image.png', 'image/png', 302, true],
    'jpg is allowed'  => ['image.jpg', 'image/jpg', 302, true],
    'webp is allowed' => ['image.webp', 'image/webp', 302, true],
    'pdf is rejected' => ['file.pdf', 'application/pdf', 422, false],
    'svg is rejected' => ['file.svg', 'image/svg+xml', 422, false],
    'mp4 is rejected' => ['file.mp4', 'video/mp4', 422, false],
    'txt is rejected' => ['file.txt', 'text/plain', 422, false],
]);

test('logo upload validates file size', function (int $sizeInKb, int $status) {
    $response = $this->actingAs($this->user)
        ->patchJson(route('workspaces.update',
            [
                'workspace' => $this->workspace->id,
            ]), [
                'logo' => UploadedFile::fake()->image('logo.png')->size($sizeInKb),
            ]);

    $response->assertStatus($status);

    if ($status === 422) {
        $response->assertJsonValidationErrors(['logo']);
        $this->assertDatabaseEmpty('files');
    } else {
        $this->assertDatabaseCount('files', 1);
    }

})->with([
    'exactly 2048kb is allowed' => [2048, 302],
    'below 2048kb is allowed'   => [1024, 302],
    'above 2048kb is rejected'  => [2049, 422],
    '10mb is rejected'          => [10240, 422],
]);

test('unauthenticated users cannot update a workspace', function () {
    $this->patchJson(route('workspaces.update',
        [
            'workspace' => $this->workspace->id,
        ]), [
            'name' => 'Updated Workspace Name',
        ])->assertStatus(401);

    $this->assertDatabaseHas('workspaces', [
        'id'          => $this->workspace->id,
        'name'        => $this->workspace->name,
        'description' => $this->workspace->description,
    ]); // nothing changes
});
