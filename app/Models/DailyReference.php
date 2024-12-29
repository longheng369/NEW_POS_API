<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReference extends Model
{
    use HasFactory;

    protected $table = 'daily_references';

    protected $fillable = ['date', 'reference_count'];
}
