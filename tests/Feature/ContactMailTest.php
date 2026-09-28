<?php

namespace Tests\Feature;

use App\Mail\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class ContactMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_message_is_rendered_with_twig_and_sent(): void
    {
        Mail::fake();
        config(['mail.contact_address' => 'owner@example.com']);

        $this->postJson('/api/v1/contact', [
            'name' => 'Recipe Fan',
            'email' => 'fan@example.com',
            'message' => 'A useful message.',
        ])->assertAccepted()->assertJson(['message' => 'Message sent.']);

        Mail::assertSent(ContactMessage::class, fn (ContactMessage $mail) => str_contains($mail->renderedHtml, 'A useful message.'));
    }

    public function test_contact_name_is_required(): void
    {
        $this->postJson('/api/v1/contact', ['email' => 'fan@example.com', 'message' => 'Hello'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_contact_email_must_be_valid(): void
    {
        $this->postJson('/api/v1/contact', ['name' => 'Fan', 'email' => 'invalid', 'message' => 'Hello'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_contact_message_is_required(): void
    {
        $this->postJson('/api/v1/contact', ['name' => 'Fan', 'email' => 'fan@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('message');
    }
}
