<?php

namespace App\Services;

use App\Mail\RenderedTwigMail;
use App\Models\ChefApplication;
use App\Support\Twig\TwigRenderer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class ChefApplicationMailer {
    public function __construct(private readonly TwigRenderer $twig) {}

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
