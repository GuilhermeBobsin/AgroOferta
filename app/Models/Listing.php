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

    public const SEARCH_RADIUS_OPTIONS = [10, 25, 50, 100, 200];

    // Haversine (km). SQLite usa min/max escalares em vez de greatest/least.
    private const HAVERSINE = '(6371 * acos(greatest(-1, least(1, cos(radians(?)) * cos(radians(latitude))
        * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))))';

    private const SQLITE_HAVERSINE = '(6371 * acos(max(-1, min(1, cos(radians(?)) * cos(radians(latitude))
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
        $haversine = self::haversineFor($q);
        // PDO entrega floats como texto no SQLite; o cast preserva a comparação numérica do raio.
        $radius = $q->getModel()->getConnection()->getDriverName() === 'sqlite' ? 'cast(? as real)' : '?';

        return $q->whereNotNull('latitude')->whereNotNull('longitude')
            ->select('listings.*')
            ->selectRaw($haversine.' as distance', [$lat, $lng, $lat])
            ->whereRaw($haversine.' <= '.$radius, [$lat, $lng, $lat, $km])
            ->orderByRaw($haversine, [$lat, $lng, $lat]);
    }

    /** Todos os anúncios por distância; sem coordenadas vão para o fim. */
    public function scopeOrderByDistance(Builder $q, float $lat, float $lng): Builder
    {
        $haversine = self::haversineFor($q);

        return $q->select('listings.*')
            ->selectRaw($haversine.' as distance', [$lat, $lng, $lat])
            ->orderByRaw('latitude IS NULL')
            ->orderByRaw($haversine, [$lat, $lng, $lat]);
    }

    private static function haversineFor(Builder $query): string
    {
        return $query->getModel()->getConnection()->getDriverName() === 'sqlite'
            ? self::SQLITE_HAVERSINE
            : self::HAVERSINE;
    }
}
