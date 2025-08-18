<?php

namespace App\Services;

class ShopifyService
{
    public function __construct(
        private readonly string $apiKey = '',
        private readonly string $apiSecret,
        private readonly string $scopes,
        private readonly string $redirectUri,
        private readonly string $hostName,
    ) {}
}