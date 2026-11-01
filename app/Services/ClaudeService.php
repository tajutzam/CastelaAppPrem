<?php

namespace App\Services;

use App\Models\ProductOffering;

class ClaudeService extends OrganizationAdminService
{
    protected function baseUrl(): string
    {
        return 'https://api.anthropic.com/v1/';
    }

    protected function headers(ProductOffering $offering): array
    {
        return [
            'x-api-key' => $offering->api_key,
            'anthropic-version' => '2023-06-01',
        ];
    }

    protected function resourcePath(string $resource): string
    {
        return 'organizations/'.$resource;
    }

    public function roles(): array
    {
        return ['user', 'developer', 'billing', 'claude_code_user'];
    }

    public function organization(ProductOffering $offering): array
    {
        return $this->request($offering, 'GET', 'organizations/me');
    }
}
