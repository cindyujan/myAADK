<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeluargaKlienUpdateRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'klien_id', 
        'waris',
        'requested_data', 
        'status'
    ];

    public function keluargaKlien()
    {
        return $this->belongsTo(KeluargaKlien::class, 'klien_id');
    }
}
