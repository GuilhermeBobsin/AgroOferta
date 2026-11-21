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

    public function test_filters_listings_by_maximum_distance(): void
    {
        $user = User::factory()->create(['latitude' => 0, 'longitude' => 0]);

        Listing::factory()->create([
            'title' => 'Anúncio próximo',
            'latitude' => 0,
            'longitude' => 0,
        ]);
        Listing::factory()->create([
            'title' => 'Anúncio distante',
            'latitude' => 2,
            'longitude' => 0,
        ]);

        $this->actingAs($user)->get('/?distance_km=50')
            ->assertOk()
            ->assertSee('Anúncio próximo')
            ->assertDontSee('Anúncio distante');
    }

    public function test_home_displays_distance_when_the_user_has_a_location(): void
    {
        $user = User::factory()->create(['latitude' => 0, 'longitude' => 0]);
        Listing::factory()->create(['title' => 'Adubo a onze quilômetros', 'latitude' => 0.1, 'longitude' => 0]);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('11 km');
    }

    public function test_product_photos_link_to_the_listing_page(): void
    {
        $listing = Listing::factory()->create(['title' => 'Adubo com foto']);

        $this->get('/')
            ->assertOk()
            ->assertSee(route('listings.show', $listing), false);

        $this->actingAs($listing->user)->get(route('listings.mine'))
            ->assertOk()
            ->assertSee(route('listings.show', $listing), false);
    }

    public function test_user_can_add_and_remove_another_users_listing_from_favorites(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create();

        $this->actingAs($user)->post(route('favorites.store', $listing))
            ->assertSessionHas('status', 'Anúncio salvo nos favoritos.');
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'listing_id' => $listing->id]);

        $this->actingAs($user)->get(route('favorites.index'))
            ->assertOk()
            ->assertSee($listing->title);

        $this->actingAs($user)->delete(route('favorites.destroy', $listing))
            ->assertSessionHas('status', 'Anúncio removido dos favoritos.');
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'listing_id' => $listing->id]);
    }

    public function test_favorite_control_is_available_on_listing_page_but_not_on_home_cards(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create();

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertDontSee('Salvar nos favoritos');

        $this->actingAs($user)->get(route('listings.show', $listing))
            ->assertOk()
            ->assertSee('Salvar nos favoritos');
    }

    public function test_distance_filter_keeps_the_price_order_selected_by_the_user(): void
    {
        $user = User::factory()->create(['latitude' => 0, 'longitude' => 0]);
        Listing::factory()->create(['title' => 'Mais perto e caro', 'price' => 100, 'latitude' => 0, 'longitude' => 0]);
        Listing::factory()->create(['title' => 'Mais longe e barato', 'price' => 10, 'latitude' => 0.1, 'longitude' => 0]);

        $this->actingAs($user)->get('/?distance_km=50&sort=price_asc')
            ->assertOk()
            ->assertSeeInOrder(['Mais longe e barato', 'Mais perto e caro']);
    }

    public function test_filters_by_city(): void
    {
        Listing::factory()->create(['title' => 'Semente local', 'city' => 'Londrina']);
        Listing::factory()->create(['title' => 'Semente distante', 'city' => 'Maringá']);

        $this->get('/?city=Londrina')
            ->assertOk()
            ->assertSee('Semente local')
            ->assertDontSee('Semente distante');
    }

    public function test_exact_location_does_not_display_the_manual_city_publicly(): void
    {
        Listing::factory()->create([
            'city' => 'Salvador',
            'state' => 'BA',
            'latitude' => -23.5505,
            'longitude' => -46.6333,
            'location_precision' => 'exact',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Posição exata marcada')
            ->assertDontSee('Salvador - BA');
    }

    public function test_my_listings_marks_an_exact_location_without_showing_its_manual_city(): void
    {
        $user = User::factory()->create();
        Listing::factory()->for($user)->create([
            'city' => 'Salvador',
            'state' => 'BA',
            'location_precision' => 'exact',
        ]);

        $this->actingAs($user)->get(route('listings.mine'))
            ->assertOk()
            ->assertSee('Posição exata marcada')
            ->assertDontSee('Salvador - BA');
    }

    public function test_guest_cannot_create_listing(): void
    {
        $this->post(route('listings.store'), $this->payload())->assertRedirect(route('login'));
    }

    public function test_listing_city_must_belong_to_the_selected_state(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('listings.store'), $this->payload([
            'city' => 'Porto Alegre',
            'state' => 'SP',
        ]))->assertSessionHasErrors('city');
    }

    public function test_listing_city_is_saved_with_its_official_spelling(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('listings.store'), $this->payload([
            'city' => 'osorio',
            'state' => 'rs',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('Osório', Listing::firstOrFail()->city);
        $this->assertSame('RS', Listing::firstOrFail()->state);
    }

    public function test_listing_uses_city_coordinates_even_when_profile_has_an_exact_position(): void
    {
        $user = User::factory()->create(['city' => 'Osório', 'state' => 'RS', 'latitude' => -29.88, 'longitude' => -50.27]);

        $this->actingAs($user)->post(route('listings.store'), $this->payload())->assertRedirect();

        $listing = Listing::firstOrFail();
        $this->assertEquals('RS', $listing->state);
        $this->assertNotEquals(-29.88, (float) $listing->latitude);
        $this->assertSame('city', $listing->location_precision);
    }

    public function test_listing_uses_exact_coordinates_only_when_explicitly_requested(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('listings.store'), $this->payload([
            'latitude' => -29.88,
            'longitude' => -50.27,
            'use_exact_location' => true,
        ]))->assertRedirect();

        $listing = Listing::firstOrFail();
        $this->assertEquals(-29.88, (float) $listing->latitude);
        $this->assertSame('exact', $listing->location_precision);
    }

    public function test_listing_in_another_city_uses_its_city_coordinates(): void
    {
        $user = User::factory()->create(['city' => 'Osório', 'state' => 'RS', 'latitude' => -29.88, 'longitude' => -50.27]);

        $this->actingAs($user)->post(route('listings.store'), $this->payload(['city' => 'Porto Alegre']));

        $listing = Listing::firstOrFail();
        $this->assertNotNull($listing->latitude);
        $this->assertSame('city', $listing->location_precision);
    }

    public function test_changing_city_replaces_an_old_exact_position_with_city_coordinates(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create(['latitude' => -29.88, 'longitude' => -50.27]);

        $this->actingAs($user)->put(route('listings.update', $listing), $this->payload(['city' => 'Pelotas']));

        $this->assertNotNull($listing->fresh()->latitude);
        $this->assertSame('city', $listing->fresh()->location_precision);
    }

    public function test_profile_city_enables_distance_filter_without_exact_location(): void
    {
        $user = User::factory()->create(['city' => 'Osório', 'state' => 'RS']);
        Listing::factory()->create([
            'title' => 'Anúncio em Osório',
            'latitude' => -29.886,
            'longitude' => -50.269,
        ]);

        $this->actingAs($user)->get('/?distance_km=10')
            ->assertOk()
            ->assertSee('Anúncio em Osório');
    }

    public function test_editing_city_from_the_form_does_not_keep_the_old_exact_position(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create([
            'latitude' => -29.88,
            'longitude' => -50.27,
            'location_precision' => 'exact',
        ]);

        $this->actingAs($user)->put(route('listings.update', $listing), $this->payload([
            'city' => 'Pelotas',
            'latitude' => -29.88,
            'longitude' => -50.27,
        ]));

        $this->assertSame('city', $listing->fresh()->location_precision);
        $this->assertNotEquals(-29.88, (float) $listing->fresh()->latitude);
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

    public function test_participant_can_fetch_only_new_messages_for_live_updates(): void
    {
        [$seller, $buyer, , $negotiation] = $this->dealSetup();
        $first = $negotiation->messages()->create(['user_id' => $buyer->id, 'body' => 'Mensagem antiga']);
        $second = $negotiation->messages()->create(['user_id' => $buyer->id, 'body' => 'Mensagem nova']);

        $this->actingAs($seller)->get(route('negotiations.messages', $negotiation, false).'?after='.$first->id)
            ->assertOk()
            ->assertJsonPath('messages.0.id', $second->id)
            ->assertJsonPath('messages.0.body', 'Mensagem nova')
            ->assertJsonCount(1, 'messages');
    }

    // ---------- Venda e avaliações ----------

    public function test_review_is_blocked_until_listing_is_sold_to_that_buyer(): void
    {
        [$seller, $buyer, $listing, $negotiation] = $this->dealSetup();

        $this->actingAs($buyer)->post(route('reviews.store', $negotiation), ['rating' => 5])->assertForbidden();

        $this->actingAs($seller)->patch(route('listings.status', $listing), ['status' => 'sold', 'buyer_id' => $buyer->id, 'sale_type' => 'all'])
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

        $this->actingAs($seller)->patch(route('listings.status', $listing), ['status' => 'sold', 'buyer_id' => $buyer->id, 'sale_type' => 'all']);

        $this->actingAs($other)->post(route('reviews.store', $otherNegotiation), ['rating' => 1])->assertForbidden();
    }

    public function test_cannot_mark_sold_to_user_who_never_negotiated(): void
    {
        [$seller, , $listing] = $this->dealSetup();
        $stranger = User::factory()->create();

        $this->actingAs($seller)->patch(route('listings.status', $listing), ['status' => 'sold', 'buyer_id' => $stranger->id, 'sale_type' => 'all'])
            ->assertSessionHasErrors('buyer_id');

        $this->assertSame(ListingStatus::Active, $listing->fresh()->status);
    }

    public function test_reactivating_clears_the_buyer(): void
    {
        [$seller, $buyer, $listing] = $this->dealSetup();

        $this->actingAs($seller)->patch(route('listings.status', $listing), ['status' => 'sold', 'buyer_id' => $buyer->id, 'sale_type' => 'all']);
        $this->actingAs($seller)->patch(route('listings.status', $listing), ['status' => 'active']);

        $this->assertNull($listing->fresh()->sold_to_id);
    }

    public function test_partial_sale_reduces_available_quantity_and_keeps_listing_active(): void
    {
        [$seller, $buyer, $listing] = $this->dealSetup();
        $listing->update(['quantity' => 100]);

        $this->actingAs($seller)->patch(route('listings.status', $listing), [
            'status' => 'sold',
            'buyer_id' => $buyer->id,
            'sale_type' => 'quantity',
            'sold_quantity' => 25,
        ])->assertSessionHasNoErrors();

        $this->assertSame(ListingStatus::Active, $listing->fresh()->status);
        $this->assertEquals(75, (float) $listing->fresh()->quantity);
        $this->assertNull($listing->fresh()->sold_to_id);
    }

    public function test_partial_sale_requires_an_available_quantity(): void
    {
        [$seller, $buyer, $listing] = $this->dealSetup();
        $listing->update(['quantity' => null]);

        $this->actingAs($seller)->patch(route('listings.status', $listing), [
            'status' => 'sold',
            'buyer_id' => $buyer->id,
            'sale_type' => 'quantity',
            'sold_quantity' => 1,
        ])->assertSessionHasErrors('sold_quantity');
    }
}
