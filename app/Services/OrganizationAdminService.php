<?php

namespace App\Services;

use App\Models\ProductOffering;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class OrganizationAdminService
{
    abstract protected function baseUrl(): string;

    abstract protected function headers(ProductOffering $offering): array;

    abstract protected function resourcePath(string $resource): string;

    abstract public function roles(): array;

    /** @return array{members: bool, invites: bool, invite_via_website: bool, management_url: string|null} */
    public function capabilities(): array
    {
        return ['members' => false, 'invites' => false, 'invite_via_website' => true, 'management_url' => null];
    }

    protected function request(ProductOffering $offering, string $method, string $path, array $parameters = []): array
    {
        if (blank($offering->api_key)) {
            throw new RuntimeException('API key untuk product offering ini belum diisi.');
        }

        $request = Http::acceptJson()
            ->withHeaders($this->headers($offering))
            ->timeout(20);

        $response = match (strtoupper($method)) {
            'GET' => $request->get($this->baseUrl().$path, $parameters),
            'POST' => $request->post($this->baseUrl().$path, $parameters),
            'DELETE' => $request->delete($this->baseUrl().$path, $parameters),
            default => throw new RuntimeException('Metode API tidak didukung.'),
        };

        if ($response->failed()) {
            $message = data_get($response->json(), 'error.message')
                ?? data_get($response->json(), 'message')
                ?? 'Provider menolak permintaan. Periksa admin API key dan izin aksesnya.';

            throw new RuntimeException($message, $response->status());
        }

        return $response->json() ?? [];
    }

    public function list(ProductOffering $offering, string $resource, ?string $after = null): array
    {
        $capability = $resource === 'users' ? 'members' : ($resource === 'invites' ? 'invites' : null);

        if ($capability === null || ! $this->capabilities()[$capability]) {
            throw new RuntimeException('Jenis data organisasi tidak didukung.');
        }

        $query = ['limit' => 100];
        if ($after) {
            $query['after'] = $after;
        }

        $response = $this->request($offering, 'GET', $this->resourcePath($resource), $query);

        return [
            'data' => $response['data'] ?? [],
            'has_more' => (bool) ($response['has_more'] ?? false),
            'last_id' => $response['last_id'] ?? null,
        ];
    }

    public function findByEmail(ProductOffering $offering, string $resource, string $email): ?array
    {
        $after = null;

        do {
            $page = $this->list($offering, $resource, $after);

            foreach ($page['data'] as $item) {
                if (isset($item['email']) && strcasecmp(trim($item['email']), trim($email)) === 0) {
                    return $item;
                }
            }

            if (! $page['has_more'] || blank($page['last_id'])) {
                return null;
            }

            $after = $page['last_id'];
        } while (true);
    }

    public function listAll(ProductOffering $offering, string $resource): array
    {
        $items = [];
        $after = null;

        do {
            $page = $this->list($offering, $resource, $after);
            $items = [...$items, ...$page['data']];
            $after = $page['last_id'];
        } while ($page['has_more'] && filled($after));

        return ['data' => $items, 'has_more' => false, 'last_id' => $after];
    }

    public function invite(ProductOffering $offering, string $email, string $role, ?string $projectId = null): array
    {
        if (! $this->capabilities()['invites']) {
            throw new RuntimeException('Undangan untuk provider ini harus dilakukan melalui website provider.');
        }

        return $this->request($offering, 'POST', $this->resourcePath('invites'), [
            'email' => $email,
            'role' => $role,
        ]);
    }

    public function removeMember(ProductOffering $offering, string $memberId): array
    {
        if (! $this->capabilities()['members']) {
            throw new RuntimeException('Member provider ini tidak dapat dikelola melalui API.');
        }

        return $this->request($offering, 'DELETE', $this->resourcePath('users').'/'.rawurlencode($memberId));
    }

    public function cancelInvite(ProductOffering $offering, string $inviteId): array
    {
        if (! $this->capabilities()['invites']) {
            throw new RuntimeException('Undangan provider ini tidak dapat dibatalkan melalui API.');
        }

        return $this->request($offering, 'DELETE', $this->resourcePath('invites').'/'.rawurlencode($inviteId));
    }
}
