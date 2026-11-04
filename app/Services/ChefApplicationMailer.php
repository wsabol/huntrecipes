<?php

namespace App\Services;

use App\Mail\RenderedTwigMail;
use App\Models\ChefApplication;
use App\Support\Twig\TwigRenderer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class ChefApplicationMailer {
    public function __construct(private readonly TwigRenderer $twig) {
    }

    public function sendSubmittedNotice(ChefApplication $application): void {
        $recipient = config('mail.contact_address');
        if (!$recipient) {
            Log::warning('Chef application submitted but no admin notification address is configured.', ['chef_application_id' => $application->id]);
            return;
        }

        $application->loadMissing('user');
        $subject = "New chef application from {$application->user->name}";
        try {
            $html = $this->twig->html('emails/chef-app-submitted.twig', [
                'subject' => $subject,
                'pre_text' => $subject,
                'user' => $application->user,
                'chef_app' => $application,
                'relationship_name' => ChefApplication::relationshipName($application->relationship),
            ]);
            Mail::to($recipient)->send(new RenderedTwigMail($html, $subject));
        } catch (Throwable $exception) {
            Log::error('Unable to send chef application submitted email.', ['chef_application_id' => $application->id, 'exception' => $exception]);
        }
    }

    public function sendDecision(ChefApplication $application, bool $approved): void {
        $application->loadMissing('user');
        $subject = $approved ? 'Your HuntRecipes chef application was approved' : 'Your HuntRecipes chef application';
        $template = $approved ? 'emails/chef-app-approval.twig' : 'emails/chef-app-denial.twig';
        try {
            $html = $this->twig->html($template, [
                'subject' => $subject,
                'pre_text' => $subject,
                'user' => $application->user,
                'chef_app' => $application,
            ]);
            Mail::to($application->user->email)->send(new RenderedTwigMail($html, $subject));
        } catch (Throwable $exception) {
            Log::error('Unable to send chef application decision email.', ['chef_application_id' => $application->id, 'exception' => $exception]);
        }
    }
}
