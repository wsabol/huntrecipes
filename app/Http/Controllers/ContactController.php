<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use App\Support\Twig\TwigRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller {
    public function __invoke(Request $request, TwigRenderer $twig) {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email'], 'reason' => ['required', 'string', 'in:Report a problem,Request a feature,Ask a question,Something else'], 'message' => ['required', 'string', 'max:5000']]);
        $html = $twig->html('emails/support-contact.twig', $data + ['subject' => 'HuntRecipes Contact', 'pre_text' => 'New HuntRecipes contact message']);
        Mail::to(config('mail.contact_address'))->send(new ContactMessage($html, $data['email']));

        return response()->json(['message' => 'Message sent.'], 202);
    }
}
