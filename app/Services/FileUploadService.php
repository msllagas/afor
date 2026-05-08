<?php

namespace App\Services;

use App\DTOs\FileUploadData;
use App\Enums\FileCollection;
use App\Models\File;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

readonly class FileUploadService
{
    public function __construct(
        #[Config('filesystems.default')]
        protected string $disk
    ) {}

    public function upload(FileUploadData $data): void
    {
        $storedPath = $data->file->store($data->path, $this->disk);

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
    }

    public function replace(FileUploadData $data): void
    {
        $this->delete($data->model, $data->collection);
        $this->upload($data);
    }

    public function delete(Model $model, FileCollection $collection): void
    {
        $file = File::query()
            ->where('fileable_id', $model->id)
            ->where('fileable_type', $model::class)
            ->where('collection', $collection->value)
            ->first();

        if ($file) {
            Storage::disk($file->disk)->delete($file->path);
            $file->delete();
        }
    }
}
