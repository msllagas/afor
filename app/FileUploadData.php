<?php

namespace App;

use App\Enums\FileCollection;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

readonly class FileUploadData
{
    public function __construct(
        public Model $model,
        public UploadedFile $file,
        public FileCollection $collection,
        public string $path,
        public User $uploadedBy,
    ) {}
}
