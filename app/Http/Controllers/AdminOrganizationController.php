<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\ProductOffering;
use App\Services\ChatGPTService;
use App\Services\ClaudeService;
use App\Services\OrganizationAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class AdminOrganizationController extends Controller
{
    public function index(): View
    {
        return view('admin.organizations.index', [
            'organizations' => Organization::query()
                ->with(['productOffering.product', 'productOffering.accountType'])
                ->whereHas('productOffering.product')
                ->latest()
                ->paginate(10, ['*'], 'organizations_page')
                ->withQueryString(),
        ]);
    }

    public function show(Organization $organization): JsonResponse
    {
        try {
            [$offering, $provider, $service] = $this->resolveProvider($organization);
            $organizationInfo = [
                'id' => $organization->external_id,
                'name' => $offering->product->name,
                'email' => $organization->email,
                'expires_at' => $organization->expires_at?->format('d M Y'),
            ];

            $availableOrganizations = [];
            if ($service instanceof ClaudeService) {
                $remoteOrganization = $service->organization($offering);
                $organizationInfo['external_id'] = $remoteOrganization['id'] ?? $organization->external_id;
                $organizationInfo['name'] = $remoteOrganization['name'] ?? $organizationInfo['name'];
            }

            $capabilities = $service->capabilities();

            return response()->json([
                'data' => [
                    'provider' => $provider,
                    'provider_label' => $provider === 'chatgpt' ? 'ChatGPT / OpenAI' : 'Claude / Anthropic',
                    'organization' => $organizationInfo,
                    'capabilities' => $capabilities,
                    'available_organizations' => $availableOrganizations,
                    'members' => $capabilities['members'] ? $service->list($offering, 'users') : ['data' => [], 'has_more' => false, 'last_id' => null],
                    'invites' => $capabilities['invites'] ? $service->list($offering, 'invites') : ['data' => [], 'has_more' => false, 'last_id' => null],
                    'roles' => $service->roles(),
                ],
            ]);
        } catch (Throwable $exception) {
            return $this->providerError($exception);
        }
    }

    public function members(Organization $organization, Request $request): JsonResponse
    {
        return $this->listProviderResource($organization, $request, 'users');
    }

    public function invites(Organization $organization, Request $request): JsonResponse
    {
        return $this->listProviderResource($organization, $request, 'invites');
    }

    public function invite(Organization $organization, Request $request): JsonResponse
    {
        try {
            [$offering, , $service] = $this->resolveProvider($organization);
            if (! $service->capabilities()['invites']) {
                throw new RuntimeException('Invite ChatGPT Plus dilakukan melalui website ChatGPT.');
            }

            $data = $request->validate([
                'email' => ['required', 'email', 'max:255'],
                'role' => ['required', Rule::in($service->roles())],
                'project_id' => ['nullable', 'string', 'max:255'],
                'organization_id' => ['nullable', 'string', 'max:255'],
            ]);

            return response()->json([
                'message' => 'Undangan berhasil dikirim.',
                'data' => $service->invite($offering, $data['email'], $data['role'], $data['project_id'] ?? null),
            ], 201);
        } catch (Throwable $exception) {
            return $this->providerError($exception);
        }
    }

    public function removeMember(Organization $organization, string $memberId): JsonResponse
    {
        try {
            [$offering, , $service] = $this->resolveProvider($organization);

            return response()->json([
                'message' => 'User berhasil dikeluarkan dari organization.',
                'data' => $service->removeMember($offering, $memberId),
            ]);
        } catch (Throwable $exception) {
            return $this->providerError($exception);
        }
    }

    public function cancelInvite(Organization $organization, string $inviteId): JsonResponse
    {
        try {
            [$offering, , $service] = $this->resolveProvider($organization);

            return response()->json([
                'message' => 'Undangan berhasil dibatalkan.',
                'data' => $service->cancelInvite($offering, $inviteId),
            ]);
        } catch (Throwable $exception) {
            return $this->providerError($exception);
        }
    }

    private function listProviderResource(Organization $organization, Request $request, string $resource): JsonResponse
    {
        try {
            [$offering, , $service] = $this->resolveProvider($organization);
            $after = $request->query('after');

            return response()->json(['data' => $service->list($offering, $resource, $after)]);
        } catch (Throwable $exception) {
            return $this->providerError($exception);
        }
    }

    /** @return array{ProductOffering, string, OrganizationAdminService} */
    private function resolveProvider(Organization $organization): array
    {
        $organization->loadMissing('productOffering.product');
        $offering = $organization->productOffering;

        if (! $offering || ! $offering->product) {
            throw new RuntimeException('Organization ini tidak terhubung ke product offering.');
        }

        $productKey = Str::lower($offering->product->slug.' '.$offering->product->name);

        if (Str::contains($productKey, ['chatgpt', 'openai'])) {
            return [$offering, 'chatgpt', app(ChatGPTService::class)];
        }

        if (Str::contains($productKey, ['claude', 'anthropic'])) {
            return [$offering, 'claude', app(ClaudeService::class)];
        }

        throw new RuntimeException('Product offering ini belum didukung. Nama produk harus mengandung ChatGPT/OpenAI atau Claude/Anthropic.');
    }

    private function providerError(Throwable $exception): JsonResponse
    {
        $status = $exception instanceof RuntimeException && $exception->getCode() >= 400 && $exception->getCode() < 500
            ? $exception->getCode()
            : 422;

        return response()->json(['message' => $exception->getMessage()], $status);
    }
}
