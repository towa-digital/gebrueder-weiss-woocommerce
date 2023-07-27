<?php

uses(\WP_UnitTestCase::class);
use Towa\GebruederWeissWooCommerce\OAuth\OAuthToken;


test('it can get the access token', function () {
    $token = new OAuthToken("test", time() + 3600);

    expect($token->getToken())->toBe("test");
});

test('it can retrieve the expires in time', function () {
    $token = new OAuthToken("test", time() + 3600);

    expect($token->getExpiresIn())->toBe(3600);
});

test('it can determine if the token is valid', function () {
    $token = new OAuthToken("test", time() + 3600);

    expect($token->isValid())->toBeTrue();
});

test('it can determine if the token is invalid', function () {
    $token = new OAuthToken("test", time() - 3600);

    expect($token->isValid())->toBeFalse();
});

test('it can serialize and unserialize the token', function () {
    $token = new OAuthToken("test-1", 1628168570);
    $serialized = $token->serialize();
    $newToken = new OAuthToken("", 0);

    $newToken->unserialize($serialized);

    expect($newToken->getToken())->toBe("test-1");
    expect($newToken->getExpiresIn())->toBe(1628168570 - time());
});
