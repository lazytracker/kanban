<?php
// app/Models/Organization.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'shortname1', 'shortname2'
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}