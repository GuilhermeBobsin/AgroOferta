<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\Negotiation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_the_users_listings_and_negotiations_only(): void
    {
        $user = User::factory()->create();
        Listing::factory()->for($user)->create(['title' => 'Adubo disponível']);

        $seller = User::factory()->create();
        $negotiatedListing = Listing::factory()->for($seller)->create(['title' => 'Milho para venda']);
        Negotiation::create([
            'listing_id' => $negotiatedListing->id,
            'buyer_id' => $user->id,
            'seller_id' => $seller->id,
        ]);

        $otherBuyer = User::factory()->create();
        $otherSeller = User::factory()->create();
        $unrelatedListing = Listing::factory()->for($otherSeller)->create(['title' => 'Oferta de outra pessoa']);
        Negotiation::create([
            'listing_id' => $unrelatedListing->id,
            'buyer_id' => $otherBuyer->id,
            'seller_id' => $otherSeller->id,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Adubo disponível')
            ->assertSee('Milho para venda')
            ->assertDontSee('Oferta de outra pessoa');
    }

    public function test_closed_sales_filter_shows_only_sold_listings(): void
    {
        $user = User::factory()->create();
        Listing::factory()->for($user)->create(['title' => 'Venda concluída', 'status' => ListingStatus::Sold]);
        Listing::factory()->for($user)->create(['title' => 'Venda em andamento', 'status' => ListingStatus::Active]);

        $this->actingAs($user)->get(route('listings.mine', ['sales' => 'closed']))
            ->assertOk()
            ->assertSee('Venda concluída')
            ->assertDontSee('Venda em andamento');
    }

    public function test_open_sales_filter_excludes_closed_listings(): void
    {
        $user = User::factory()->create();
        Listing::factory()->for($user)->create(['title' => 'Venda em andamento', 'status' => ListingStatus::Active]);
        Listing::factory()->for($user)->create(['title' => 'Venda concluída', 'status' => ListingStatus::Sold]);

        $this->actingAs($user)->get(route('listings.mine', ['sales' => 'open']))
            ->assertOk()
            ->assertSee('Venda em andamento')
            ->assertDontSee('Venda concluída');
    }
}
