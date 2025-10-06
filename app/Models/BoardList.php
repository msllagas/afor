<?php

namespace App\Models;

use Database\Factories\BoardListFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoardList extends Model
{
    /** @use HasFactory<BoardListFactory> */
    use HasUuids, HasFactory, SoftDeletes;

    protected $guarded = ['id'];
}
