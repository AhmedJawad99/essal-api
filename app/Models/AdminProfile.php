<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminProfile extends Model
{
    protected $fillable = [
        'job_title',
    ];

    //relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
