<?php

namespace App\Models;

use Database\Factories\BoardFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use HasUuids, HasFactory, SoftDeletes;
}
