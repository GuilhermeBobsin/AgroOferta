<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['negotiation_id', 'user_id', 'body'];

    public function negotiation()
    {
        return $this->belongsTo(Negotiation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
