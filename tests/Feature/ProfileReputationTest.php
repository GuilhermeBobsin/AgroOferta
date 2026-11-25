<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Negotiation;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileReputationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_displays_received_reviews_and_average(): void
    {
        [$user, $reviewer] = $this->createUsersWithReview(5, 'Entrega excelente');
        $this->addReview($user, 3, 'Atendimento razoável');

        $this->get(route('profiles.show', $user))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($reviewer->name)
            ->assertSee('4,0 de 5')
            ->assertSee('2 avaliações')
            ->assertSee('Entrega excelente');
    }

    public function test_my_profile_displays_received_reviews(): void
    {
        [$user, $reviewer] = $this->createUsersWithReview(4, 'Produto conforme anunciado');

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee($reviewer->name)
            ->assertSee('4,0 de 5')
            ->assertSee('Produto conforme anunciado');
    }

    public function test_profiles_display_friendly_empty_reputation_state(): void
    {
        $user = User::factory()->create();

        $this->get(route('profiles.show', $user))
            ->assertOk()
            ->assertSee('Sem avaliações (0 avaliações)');

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Sem avaliações (0 avaliações)');
    }

    /** @return array{0: User, 1: User} */
    private function createUsersWithReview(int $rating, string $comment): array
    {
        $user = User::factory()->create();
        $reviewer = User::factory()->create();
        $listing = Listing::factory()->for($user)->create();
        $negotiation = Negotiation::create([
            'listing_id' => $listing->id,
            'buyer_id' => $reviewer->id,
            'seller_id' => $user->id,
        ]);

        Review::create([
            'negotiation_id' => $negotiation->id,
            'reviewer_id' => $reviewer->id,
            'reviewed_id' => $user->id,
            'rating' => $rating,
            'comment' => $comment,
        ]);

        return [$user, $reviewer];
    }

    private function addReview(User $user, int $rating, string $comment): User
    {
        $reviewer = User::factory()->create();
        $negotiation = Negotiation::create([
            'listing_id' => $user->listings()->firstOrFail()->id,
            'buyer_id' => $reviewer->id,
            'seller_id' => $user->id,
        ]);

        Review::create([
            'negotiation_id' => $negotiation->id,
            'reviewer_id' => $reviewer->id,
            'reviewed_id' => $user->id,
            'rating' => $rating,
            'comment' => $comment,
        ]);

        return $reviewer;
    }
}
