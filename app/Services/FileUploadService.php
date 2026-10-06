<?php

namespace App\Services;

use App\DTOs\FileUploadData;
use App\Enums\FileCollection;
use App\Models\File;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

readonly class FileUploadService
{
    public function __construct(
        #[Config('filesystems.default')]
        protected string $disk
    ) {}

    /**
     * Store the file and record it. A file that can't be recorded is removed from storage again.
     *
     * @throws RuntimeException when the file can't be stored
     */
    public function upload(FileUploadData $data): void
    {
        $storedPath = $data->file->store($data->path, $this->disk);

        if ($storedPath === false) {
            throw new RuntimeException('The uploaded file could not be stored.');
        }

        try {
            File::query()->create([
                'fileable_id'       => $data->model->id,
                'fileable_type'     => $data->model::class,
                'collection'        => $data->collection->value,
                'disk'              => $this->disk,
                'path'              => $storedPath,
                'original_filename' => $data->file->getClientOriginalName(),
                'mime_type'         => $data->file->getMimeType(),
                'size'              => $data->file->getSize(),
                'uploaded_by'       => $data->uploadedBy?->id,
            ]);
        } catch (Throwable $exception) {
            Storage::disk($this->disk)->delete($storedPath);

            throw $exception;
        }
    }

    /**
     * Swap the collection's file for a new one. The current file is only removed once the new one is saved.
     */
    public function replace(FileUploadData $data): void
    {
        $currentFile = $this->find($data->model, $data->collection);

        $this->upload($data);

        if ($currentFile) {
            $this->remove($currentFile);
        }
    }

    public function delete(Model $model, FileCollection $collection): void
    {
        $file = $this->find($model, $collection);

        if ($file) {
            $this->remove($file);
        }
    }

    /**
     * Remove the files from storage and their records, which may already be gone along with what they belonged to.
     *
     * @param  iterable<File>  $files
     */
    public function deleteMany(iterable $files): void
    {
        foreach ($files as $file) {
            $this->remove($file);
        }
    }

    private function find(Model $model, FileCollection $collection): ?File
    {
        return File::query()
            ->where('fileable_id', $model->id)
            ->where('fileable_type', $model::class)
            ->where('collection', $collection->value)
            ->first();
    }

    private function remove(File $file): void
    {
        Storage::disk($file->disk)->delete($file->path);
        $file->delete();
    }
}
