<?php

namespace Tests\Feature;

use App\Mail\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class ContactMailTest extends TestCase {
    use RefreshDatabase;

    public function testContactMessageIsRenderedWithTwigAndSent(): void {
        Mail::fake();
        config(['mail.contact_address' => 'owner@example.com']);

        $this->postJson('/api/v1/contact', [
            'name' => 'Recipe Fan',
            'email' => 'fan@example.com',
            'reason' => 'Ask a question',
            'message' => 'A useful message.',
        ])->assertAccepted()->assertJson(['message' => 'Message sent.']);

        Mail::assertSent(ContactMessage::class, fn (ContactMessage $mail) => str_contains($mail->renderedHtml, 'A useful message.') && str_contains($mail->renderedHtml, 'Reason: Ask a question'));
    }

    public function testContactReasonMustBeOneOfTheDropdownOptions(): void {
        Mail::fake();

        foreach ([null, 'Unknown reason'] as $reason) {
            $this->postJson('/api/v1/contact', ['name' => 'Fan', 'email' => 'fan@example.com', 'reason' => $reason, 'message' => 'Hello'])
                ->assertUnprocessable()->assertJsonValidationErrors('reason');
        }

        Mail::assertNothingSent();
    }

    public function testContactNameIsRequired(): void {
        $this->postJson('/api/v1/contact', ['email' => 'fan@example.com', 'message' => 'Hello'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function testContactEmailMustBeValid(): void {
        $this->postJson('/api/v1/contact', ['name' => 'Fan', 'email' => 'invalid', 'message' => 'Hello'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function testContactMessageIsRequired(): void {
        $this->postJson('/api/v1/contact', ['name' => 'Fan', 'email' => 'fan@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('message');
    }
}
