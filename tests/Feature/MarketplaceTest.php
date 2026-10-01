<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Negotiation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Observação: a ordenação/filtro por distância usa funções SQL que o SQLite não tem;
 * teste-a em MySQL/MariaDB/Postgres.
 */
class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'category_id' => Category::factory()->create()->id,
            'title' => 'Semente de soja',
            'price' => 150,
            'unit' => 'saca',
            'city' => 'Osório',
            'state' => 'rs',
        ], $override);
    }

    /** @return array{0: User, 1: User, 2: Listing, 3: Negotiation} */
    private function dealSetup(): array
    {
        $seller = User::factory()->create(['phone' => '(55) 99999-8888']);
        $buyer = User::factory()->create();
        $listing = Listing::factory()->for($seller)->create();
        $negotiation = Negotiation::create(['listing_id' => $listing->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id]);

        return [$seller, $buyer, $listing, $negotiation];
    }

    // ---------- Anúncios ----------

    public function test_home_lists_only_active_listings(): void
    {
        Listing::factory()->create(['title' => 'Visivel Ativo']);
        Listing::factory()->paused()->create(['title' => 'Escondido Pausado']);
        Listing::factory()->sold()->create(['title' => 'Escondido Vendido']);

        $this->get('/')->assertOk()->assertSee('Visivel Ativo')
            ->assertDontSee('Escondido Pausado')->assertDontSee('Escondido Vendido');
    }

    public function test_filters_by_price_and_sorts_by_lowest_price(): void
    {
        Listing::factory()->create(['title' => 'Caro', 'price' => 900]);
        Listing::factory()->create(['title' => 'Barato', 'price' => 10]);
        Listing::factory()->create(['title' => 'Medio', 'price' => 100]);

        $this->get('/?price_max=500&sort=price_asc')
            ->assertSeeInOrder(['Barato', 'Medio'])
            ->assertDontSee('Caro');
    }

    public function test_guest_cannot_create_listing(): void
    {
        $this->post(route('listings.store'), $this->payload())->assertRedirect(route('login'));
    }

    public function test_listing_in_users_city_inherits_profile_coordinates(): void
    {
        $user = User::factory()->create(['city' => 'Osório', 'state' => 'RS', 'latitude' => -29.88, 'longitude' => -50.27]);

        $this->actingAs($user)->post(route('listings.store'), $this->payload())->assertRedirect();

        $listing = Listing::firstOrFail();
        $this->assertEquals('RS', $listing->state);
        $this->assertEquals(-29.88, (float) $listing->latitude);
    }

    public function test_listing_in_another_city_has_no_coordinates(): void
    {
        $user = User::factory()->create(['city' => 'Osório', 'state' => 'RS', 'latitude' => -29.88, 'longitude' => -50.27]);

        $this->actingAs($user)->post(route('listings.store'), $this->payload(['city' => 'Porto Alegre']));

        $this->assertNull(Listing::firstOrFail()->latitude);
    }

    public function test_changing_city_clears_old_coordinates(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create(['latitude' => -29.88, 'longitude' => -50.27]);

        $this->actingAs($user)->put(route('listings.update', $listing), $this->payload(['city' => 'Pelotas']));

        $this->assertNull($listing->fresh()->latitude);
    }

    public function test_only_owner_can_edit_or_delete(): void
    {
        $listing = Listing::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('listings.edit', $listing))->assertForbidden();
        $this->actingAs($stranger)->put(route('listings.update', $listing), $this->payload())->assertForbidden();
        $this->actingAs($stranger)->delete(route('listings.destroy', $listing))->assertForbidden();
        $this->assertModelExists($listing);
    }

    public function test_paused_listing_is_hidden_from_others_but_visible_to_owner(): void
    {
        $listing = Listing::factory()->paused()->create();

        $this->get(route('listings.show', $listing))->assertNotFound();
        $this->actingAs($listing->user)->get(route('listings.show', $listing))->assertOk();
    }

    public function test_replacing_and_deleting_image_removes_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('listings.store'), $this->payload(['image' => UploadedFile::fake()->image('a.jpg')]));
        $listing = Listing::firstOrFail();
        $old = $listing->image_path;
        Storage::disk('public')->assertExists($old);

        $this->actingAs($user)->put(route('listings.update', $listing), $this->payload(['image' => UploadedFile::fake()->image('b.jpg')]));
        $new = $listing->fresh()->image_path;
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);

        $this->actingAs($user)->delete(route('listings.destroy', $listing));
        Storage::disk('public')->assertMissing($new);
    }

    // ---------- WhatsApp ----------

    public function test_whatsapp_link_is_only_shown_to_logged_users(): void
    {
        [$seller, $buyer, $listing] = $this->dealSetup();

        $this->get(route('listings.show', $listing))->assertOk()->assertDontSee('wa.me');
        $this->actingAs($buyer)->get(route('listings.show', $listing))->assertSee('wa.me/5555999998888', false);
    }

    // ---------- Negociação ----------

    public function test_cannot_negotiate_own_or_inactive_listing(): void
    {
        $own = Listing::factory()->create();
        $paused = Listing::factory()->paused()->create();
        $user = User::factory()->create();

        $this->actingAs($own->user)->post(route('negotiations.store', $own))->assertForbidden();
        $this->actingAs($user)->post(route('negotiations.store', $paused))->assertForbidden();
    }

    public function test_outsider_cannot_read_or_post_messages(): void
    {
        [, , , $negotiation] = $this->dealSetup();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->get(route('negotiations.show', $negotiation))->assertForbidden();
        $this->actingAs($outsider)->post(route('negotiations.message', $negotiation), ['body' => 'oi'])->assertForbidden();
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_participants_can_exchange_messages(): void
    {
        [$seller, $buyer, , $negotiation] = $this->dealSetup();

        $this->actingAs($buyer)->post(route('negotiations.message', $negotiation), ['body' => 'Faz por 140?'])->assertRedirect();
        $this->actingAs($seller)->get(route('negotiations.show', $negotiation))->assertSee('Faz por 140?');
    }

    // ---------- Venda e avaliações ----------

    public function test_review_is_blocked_until_listing_is_sold_to_that_buyer(): void
    {
        [$seller, $buyer, $listing, $negotiation] = $this->dealSetup();

        $this->actingAs($buyer)->post(route('reviews.store', $negotiation), ['rating' => 5])->assertForbidden();

        $this->actingAs($seller)->patch(route('listings.status', $listing), ['status' => 'sold', 'buyer_id' => $buyer->id])
            ->assertSessionHasNoErrors();

        $this->actingAs($buyer)->post(route('reviews.store', $negotiation), ['rating' => 5, 'comment' => 'Ótimo'])->assertRedirect();
        $this->assertDatabaseHas('reviews', ['reviewer_id' => $buyer->id, 'reviewed_id' => $seller->id, 'rating' => 5]);

        // seller também pode avaliar o comprador, mas só uma vez cada
        $this->actingAs($seller)->post(route('reviews.store', $negotiation), ['rating' => 4])->assertRedirect();
        $this->actingAs($buyer)->post(route('reviews.store', $negotiation), ['rating' => 1])->assertForbidden();
        $this->assertDatabaseCount('reviews', 2);
    }

    public function test_other_buyers_cannot_review_after_sale_to_someone_else(): void
    {
        [$seller, $buyer, $listing] = $this->dealSetup();
        $other = User::factory()->create();
        $otherNegotiation = Negotiation::create(['listing_id' => $listing->id, 'buyer_id' => $other->id, 'seller_id' => $seller->id]);

        $this->actingAs($seller)->patch(route('listings.status', $listing), ['status' => 'sold', 'buyer_id' => $buyer->id]);

        $this->actingAs($other)->post(route('reviews.store', $otherNegotiation), ['rating' => 1])->assertForbidden();
    }

    public function test_cannot_mark_sold_to_user_who_never_negotiated(): void
    {
        [$seller, , $listing] = $this->dealSetup();
        $stranger = User::factory()->create();

        $this->actingAs($seller)->patch(route('listings.status', $listing), ['status' => 'sold', 'buyer_id' => $stranger->id])
            ->assertSessionHasErrors('buyer_id');

        $this->assertSame(ListingStatus::Active, $listing->fresh()->status);
    }

    public function test_reactivating_clears_the_buyer(): void
    {
        [$seller, $buyer, $listing] = $this->dealSetup();

        $this->actingAs($seller)->patch(route('listings.status', $listing), ['status' => 'sold', 'buyer_id' => $buyer->id]);
        $this->actingAs($seller)->patch(route('listings.status', $listing), ['status' => 'active']);

        $this->assertNull($listing->fresh()->sold_to_id);
    }
}
