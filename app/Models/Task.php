<?php
// app/Models/Task.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'completion_date',
        'priority',
        'status',
        'created_by'
    ];

    protected $casts = [
        'completion_date' => 'datetime',
        'created_at' => 'datetime'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPriorityColorAttribute()
    {
        return match(true) {
            $this->priority >= 8 => 'critical',
            $this->priority >= 6 => 'high',
            $this->priority >= 4 => 'medium',
            default => 'low'
        };
    }

    public function isCreatedBy($user)
    {
        return $this->created_by === $user->id;
    }
}