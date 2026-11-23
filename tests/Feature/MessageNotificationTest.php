<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\MessageNotification;
use App\Models\Negotiation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MessageNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function negotiation(?User $seller = null): Negotiation
    {
        $listing = Listing::factory()->for($seller ?? User::factory()->create())->create();

        return Negotiation::create([
            'listing_id' => $listing->id,
            'buyer_id' => User::factory()->create()->id,
            'seller_id' => $listing->user_id,
        ]);
    }

    private function send(Negotiation $negotiation, User $sender, string $body = 'O produto está disponível?'): MessageNotification
    {
        $this->actingAs($sender)->post(route('negotiations.message', $negotiation), ['body' => $body])
            ->assertRedirect()->assertSessionHasNoErrors();

        return MessageNotification::latest('id')->firstOrFail();
    }

    public function test_buyer_and_seller_only_notify_the_other_participant(): void
    {
        $negotiation = $this->negotiation();
        $incoming = $this->send($negotiation, $negotiation->buyer);
        $reply = $this->send($negotiation, $negotiation->seller, 'Sim, está disponível!');

        $this->assertSame($negotiation->seller_id, $incoming->user_id);
        $this->assertSame($negotiation->buyer_id, $reply->user_id);
        $this->assertNull($incoming->read_at);
        $this->assertNull($reply->read_at);
        $this->assertDatabaseCount('message_notifications', 2);
        $this->assertSame($incoming->message->user_id, $negotiation->buyer_id);
    }

    public function test_invalid_or_unauthorized_messages_do_not_create_notifications(): void
    {
        $negotiation = $this->negotiation();

        $this->actingAs($negotiation->buyer)->post(route('negotiations.message', $negotiation), ['body' => ''])
            ->assertSessionHasErrors('body');
        $this->actingAs(User::factory()->create())->post(route('negotiations.message', $negotiation), ['body' => 'Oi'])
            ->assertForbidden();

        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('message_notifications', 0);
    }

    public function test_feed_and_history_are_private_and_do_not_mark_notifications_read(): void
    {
        $negotiation = $this->negotiation();
        $notification = $this->send($negotiation, $negotiation->buyer, 'Mensagem privada');

        $this->actingAs($negotiation->seller)->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.sender', $negotiation->buyer->name)
            ->assertJsonPath('notifications.0.listing', $negotiation->listing->title)
            ->assertJsonPath('notifications.0.preview', 'Mensagem privada')
            ->assertJsonPath('notifications.0.url', route('negotiations.show', $negotiation))
            ->assertJsonPath('notifications.0.unread', true);
        $this->get(route('notifications.index'))->assertOk()->assertSee('Mensagem privada');
        $this->assertNull($notification->fresh()->read_at);

        $this->actingAs(User::factory()->create())->getJson(route('notifications.feed'))
            ->assertOk()->assertJsonPath('unread_count', 0)->assertJsonCount(0, 'notifications');
        $this->get(route('notifications.index'))->assertOk()->assertDontSee('Mensagem privada');
    }

    public function test_opening_a_conversation_only_reads_its_notifications_for_the_viewer(): void
    {
        $negotiation = $this->negotiation();
        $otherNegotiation = $this->negotiation($negotiation->seller);
        $incoming = $this->send($negotiation, $negotiation->buyer);
        $reply = $this->send($negotiation, $negotiation->seller);
        $unrelated = $this->send($otherNegotiation, $otherNegotiation->buyer);

        $this->actingAs($negotiation->seller)->get(route('negotiations.show', $negotiation))->assertOk();

        $this->assertNotNull($incoming->fresh()->read_at);
        $this->assertNull($reply->fresh()->read_at);
        $this->assertNull($unrelated->fresh()->read_at);
        $this->getJson(route('notifications.feed'))->assertJsonPath('unread_count', 1);
    }

    public function test_polling_requires_explicit_acknowledgment_of_displayed_messages(): void
    {
        $negotiation = $this->negotiation();
        $first = $this->send($negotiation, $negotiation->buyer);

        $this->actingAs($negotiation->seller)->getJson(route('negotiations.messages', $negotiation))
            ->assertOk()->assertJsonPath('messages.0.id', $first->message_id);
        $this->assertNull($first->fresh()->read_at);

        // A message arriving after the fetch must remain unread.
        $newer = $this->send($negotiation, $negotiation->buyer, 'Chegou outra mensagem');
        $this->actingAs($negotiation->seller)->patchJson(route('negotiations.read', $negotiation), ['through_id' => $first->message_id])
            ->assertNoContent();

        $this->assertNotNull($first->fresh()->read_at);
        $this->assertNull($newer->fresh()->read_at);
    }

    public function test_read_receipts_reject_outsiders_and_messages_from_other_conversations(): void
    {
        $negotiation = $this->negotiation();
        $incoming = $this->send($negotiation, $negotiation->buyer);
        $otherNegotiation = $this->negotiation($negotiation->seller);
        $other = $this->send($otherNegotiation, $otherNegotiation->buyer);

        $this->actingAs(User::factory()->create())->patchJson(route('negotiations.read', $negotiation), ['through_id' => $incoming->message_id])
            ->assertForbidden();
        $this->get(route('negotiations.show', $negotiation))->assertForbidden();
        $this->actingAs($negotiation->seller)->patchJson(route('negotiations.read', $negotiation), ['through_id' => $other->message_id])
            ->assertNotFound();
        $this->patchJson(route('negotiations.read', $negotiation), ['through_id' => -1])->assertUnprocessable();

        $this->assertNull($incoming->fresh()->read_at);
        $this->assertNull($other->fresh()->read_at);
    }

    public function test_mark_all_read_only_updates_the_current_users_notifications_and_is_idempotent(): void
    {
        $negotiation = $this->negotiation();
        $incoming = $this->send($negotiation, $negotiation->buyer);
        $reply = $this->send($negotiation, $negotiation->seller);

        $this->actingAs($negotiation->seller)->patchJson(route('notifications.read-all'))->assertNoContent();
        $readAt = $incoming->fresh()->read_at;
        $this->assertNotNull($readAt);
        $this->assertNull($reply->fresh()->read_at);
        $this->travel(1)->minutes();
        $this->patchJson(route('notifications.read-all'))->assertNoContent();
        $this->assertTrue($readAt->equalTo($incoming->fresh()->read_at));
        $this->getJson(route('notifications.feed'))->assertJsonPath('unread_count', 0)->assertJsonCount(1, 'notifications');
    }

    public function test_read_all_also_works_from_the_history_form(): void
    {
        $negotiation = $this->negotiation();
        $incoming = $this->send($negotiation, $negotiation->buyer);

        $this->actingAs($negotiation->seller)->from(route('notifications.index'))->patch(route('notifications.read-all'))
            ->assertRedirect(route('notifications.index'))->assertSessionHas('status');
        $this->assertNotNull($incoming->fresh()->read_at);
    }

    public function test_guests_cannot_access_notifications_or_change_read_state(): void
    {
        $negotiation = $this->negotiation();

        $this->get(route('notifications.index'))->assertRedirect(route('login'));
        $this->getJson(route('notifications.feed'))->assertUnauthorized();
        $this->patchJson(route('notifications.read-all'))->assertUnauthorized();
        $this->patchJson(route('negotiations.read', $negotiation), ['through_id' => 1])->assertUnauthorized();
    }

    public function test_deleting_a_listing_removes_its_notifications(): void
    {
        $negotiation = $this->negotiation();
        $this->send($negotiation, $negotiation->buyer);
        $negotiation->listing->delete();

        $this->assertDatabaseCount('message_notifications', 0);
        $this->actingAs($negotiation->seller)->getJson(route('notifications.feed'))
            ->assertOk()->assertJsonPath('unread_count', 0)->assertJsonCount(0, 'notifications');
    }

    public function test_feed_is_bounded_and_history_is_paginated_with_escaped_message_previews(): void
    {
        $negotiation = $this->negotiation();
        for ($i = 0; $i < 21; $i++) {
            $message = $negotiation->messages()->create(['user_id' => $negotiation->buyer_id, 'body' => '<script>alert("oi")</script>']);
            MessageNotification::create(['user_id' => $negotiation->seller_id, 'message_id' => $message->id]);
        }

        $this->actingAs($negotiation->seller)->getJson(route('notifications.feed'))
            ->assertOk()->assertJsonPath('unread_count', 21)->assertJsonCount(8, 'notifications')
            ->assertJsonPath('notifications.0.id', MessageNotification::max('id'));
        $this->get(route('notifications.index'))->assertOk()
            ->assertViewHas('notifications', fn ($notifications) => $notifications->count() === 20 && $notifications->total() === 21)
            ->assertSee('<script>alert("oi")</script>')
            ->assertDontSee('<script>alert("oi")</script>', false);
        $this->get(route('notifications.index', ['page' => 2]))->assertOk()
            ->assertViewHas('notifications', fn ($notifications) => $notifications->count() === 1);
    }

    public function test_negotiations_display_the_recipients_unread_count(): void
    {
        $negotiation = $this->negotiation();
        $this->send($negotiation, $negotiation->buyer);

        $this->actingAs($negotiation->seller)->get(route('negotiations.index'))
            ->assertOk()->assertSee('1 mensagem não lida')->assertSee('notification-panel');
        $this->actingAs($negotiation->buyer)->get(route('negotiations.index'))
            ->assertOk()->assertDontSee('1 mensagem não lida');
    }

    public function test_summary_only_counts_notifications_without_loading_conversations(): void
    {
        $negotiation = $this->negotiation();
        $this->send($negotiation, $negotiation->buyer);
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->actingAs($negotiation->seller)->getJson(route('notifications.feed', ['summary' => 1]))
            ->assertOk()->assertExactJson(['unread_count' => 1]);

        $this->assertCount(1, array_filter($queries, fn ($sql) => str_contains($sql, 'message_notifications')));
        $this->assertEmpty(array_filter($queries, fn ($sql) => preg_match('/from "(messages|negotiations|listings)"/', $sql)));
    }

    public function test_polling_notifications_does_not_consume_the_message_sending_limit(): void
    {
        $negotiation = $this->negotiation();
        $this->actingAs($negotiation->buyer);

        for ($i = 0; $i < 21; $i++) {
            $this->getJson(route('notifications.feed', ['summary' => 1]))->assertOk();
        }

        $this->post(route('negotiations.message', $negotiation), ['body' => 'Mensagem após as consultas'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('message_notifications', 1);
    }
}
