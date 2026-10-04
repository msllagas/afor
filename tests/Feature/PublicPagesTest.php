<?php

use Inertia\Testing\AssertableInertia as Assert;

test('guests can view the public pages', function (string $routeName, string $component) {
    $response = $this->get(route($routeName));

    $response->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'about'          => ['about', 'About'],
    'privacy policy' => ['privacy-policy', 'PrivacyPolicy'],
    'terms of use'   => ['terms-of-use', 'TermsOfUse'],
    'contact'        => ['contact', 'Contact'],
]);
