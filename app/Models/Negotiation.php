<?php

namespace App\Models;

use App\Enums\ListingStatus;
use Illuminate\Database\Eloquent\Model;

class Negotiation extends Model
{
    protected $fillable = ['listing_id', 'buyer_id', 'seller_id'];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->oldest();
    }

    public function hasParticipant(User $user): bool
    {
        return in_array($user->id, [$this->buyer_id, $this->seller_id]);
    }

    /** A venda foi fechada com o comprador desta negociação. */
    public function isClosedWithBuyer(): bool
    {
        return $this->listing->status === ListingStatus::Sold
            && $this->listing->sold_to_id === $this->buyer_id;
    }
}
