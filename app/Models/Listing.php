<?php

namespace App\Models;

use App\Enums\ListingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Listing extends Model
{
    use HasFactory;

    public const UNITS = ['kg', 'saca', 'tonelada', 'litro', 'unidade', 'hectare'];

    // Haversine (km). Usa só funções comuns a MySQL/MariaDB e Postgres (não funciona em SQLite).
    private const HAVERSINE = '(6371 * acos(greatest(-1, least(1, cos(radians(?)) * cos(radians(latitude))
        * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))))';

    protected $fillable = [
        'user_id', 'category_id', 'title', 'description', 'price', 'unit', 'quantity',
        'negotiable', 'image_path', 'city', 'state', 'latitude', 'longitude', 'status', 'sold_to_id',
    ];

    protected $casts = [
        'negotiable' => 'boolean',
        'status' => ListingStatus::class,
    ];

    protected static function booted(): void
    {
        // Não deixa foto órfã no disco quando o anúncio é excluído
        static::deleting(function (Listing $listing) {
            if ($listing->image_path) {
                Storage::disk('public')->delete($listing->image_path);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function soldTo()
    {
        return $this->belongsTo(User::class, 'sold_to_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function negotiations()
    {
        return $this->hasMany(Negotiation::class);
    }

    public function whatsappUrl(): ?string
    {
        return $this->user->whatsappUrl('Olá! Vi seu anúncio "'.$this->title.'" no AgroOferta.');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', ListingStatus::Active->value);
    }

    /** Só anúncios dentro do raio (km), do mais perto ao mais longe. */
    public function scopeNearby(Builder $q, float $lat, float $lng, float $km = 50): Builder
    {
        return $q->whereNotNull('latitude')->whereNotNull('longitude')
            ->select('listings.*')
            ->selectRaw(self::HAVERSINE.' as distance', [$lat, $lng, $lat])
            ->whereRaw(self::HAVERSINE.' <= ?', [$lat, $lng, $lat, $km])
            ->orderByRaw(self::HAVERSINE, [$lat, $lng, $lat]);
    }

    /** Todos os anúncios por distância; sem coordenadas vão para o fim. */
    public function scopeOrderByDistance(Builder $q, float $lat, float $lng): Builder
    {
        return $q->select('listings.*')
            ->selectRaw(self::HAVERSINE.' as distance', [$lat, $lng, $lat])
            ->orderByRaw('latitude IS NULL')
            ->orderByRaw(self::HAVERSINE, [$lat, $lng, $lat]);
    }
}
