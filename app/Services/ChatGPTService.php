<?php

namespace App\Services;

use App\Models\ProductOffering;

class ChatGPTService extends OrganizationAdminService
{
    protected function baseUrl(): string
    {
        return 'https://api.openai.com/v1/';
    }

    protected function headers(ProductOffering $offering): array
    {
        return ['Authorization' => 'Bearer '.$offering->api_key];
    }

    protected function resourcePath(string $resource): string
    {
        return 'organization/'.$resource;
    }

    public function roles(): array
    {
        return ['reader', 'owner'];
    }

    public function capabilities(): array
    {
        return [
            'members' => false,
            'invites' => false,
            'invite_via_website' => true,
            'management_url' => 'https://chatgpt.com/admin/members',
        ];
    }
}
