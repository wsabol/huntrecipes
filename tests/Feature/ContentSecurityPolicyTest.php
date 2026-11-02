<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ContentSecurityPolicyTest extends TestCase {
    public function testEnforcedPolicyAllowsAuthenticationPhotosAndDonationButton(): void {
        config(['services.firebase.auth_domain' => 'recipes.firebaseapp.com']);

        $response = $this->get('/sign-in')->assertOk()->assertHeaderMissing('Content-Security-Policy-Report-Only');
        $policy = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($policy);
        foreach ([
            "script-src 'self' https://apis.google.com https://cdnjs.buymeacoffee.com",
            "connect-src 'self' https://identitytoolkit.googleapis.com https://securetoken.googleapis.com https://recipes.firebaseapp.com",
            "frame-src 'self' https://recipes.firebaseapp.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            'https://*.googleusercontent.com',
            "object-src 'none'",
        ] as $directive) {
            $this->assertStringContainsString($directive, $policy);
        }
    }

    public function testMissingAuthDomainDoesNotAddABroadWildcard(): void {
        config(['services.firebase.auth_domain' => null]);

        $response = $this->get('/sign-in')->assertOk()->assertHeaderMissing('Content-Security-Policy-Report-Only');
        $policy = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($policy);
        $this->assertStringContainsString("frame-src 'self';", $policy);
        $this->assertStringNotContainsString('*.firebaseapp.com', $policy);
    }
}
