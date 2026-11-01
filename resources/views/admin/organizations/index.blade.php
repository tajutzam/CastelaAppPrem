@extends('layouts.admin', ['heading' => 'Organizations'])

@section('content')
    <div class="page-heading">
        <div>
            <h2>Organizations</h2>
            <p>Lihat organization ChatGPT dan Claude, kelola undangan, serta akses member.</p>
        </div>
    </div>

    <section class="card">
        <div class="card-header d-flex align-items-center justify-content-between gap-3">
            <div>
                <h3 class="card-title">Daftar organizations</h3>
                <div class="text-muted small">Organization terhubung melalui API key pada product offering.</div>
            </div><span class="badge badge-soft-primary">{{ number_format($organizations->total()) }} total</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Organization</th>
                        <th>Provider</th>
                        <th>Email akun</th>
                        <th>Offering</th>
                        <th>Kedaluwarsa</th>
                        <th>Status koneksi</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($organizations as $organization)
                        @php
                            $offering = $organization->productOffering;
                            $product = $offering?->product;
                            $providerKey = strtolower(($product?->slug ?? '') . ' ' . ($product?->name ?? ''));
                            $provider = str_contains($providerKey, 'chatgpt') || str_contains($providerKey, 'openai') ? 'ChatGPT' : (str_contains($providerKey, 'claude') || str_contains($providerKey, 'anthropic') ? 'Claude' : null);
                            $canManage = $provider && filled($offering?->api_key);
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold text-dark">{{ $product?->name ?? 'Offering tidak tersedia' }}</div>
                                <small
                                    class="text-muted">{{ $organization->external_id ?: 'ID provider akan ditampilkan saat tersedia' }}</small>
                            </td>
                            <td><span
                                    class="badge {{ $provider === 'Claude' ? 'badge-soft-primary' : ($provider === 'ChatGPT' ? 'badge-soft-success' : 'badge-soft-danger') }}">{{ $provider ?? 'Belum didukung' }}</span>
                            </td>
                            <td>{{ $organization->email }}</td>
                            <td>{{ $offering?->accountType?->name ?? '-' }}</td>
                            <td class="text-nowrap">{{ $organization->expires_at?->format('d M Y') ?? 'Tidak ditentukan' }}</td>
                            <td><span
                                    class="badge {{ $canManage ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ $canManage ? 'Siap dikelola' : ($provider ? 'API key belum diisi' : 'Provider tidak didukung') }}</span>
                            </td>
                            <td class="text-end pe-4"><button type="button" class="btn btn-primary btn-sm manage-organization"
                                    data-show-url="{{ route('admin.organizations.show', $organization) }}"
                                    data-members-url="{{ route('admin.organizations.members', $organization) }}"
                                    data-invites-url="{{ route('admin.organizations.invites', $organization) }}"
                                    data-invite-url="{{ route('admin.organizations.invite', $organization) }}"
                                    data-member-url="{{ route('admin.organizations.members.remove', [$organization, '__MEMBER__']) }}"
                                    data-cancel-invite-url="{{ route('admin.organizations.invites.cancel', [$organization, '__INVITE__']) }}"
                                    data-can-manage="{{ $canManage ? '1' : '0' }}"
                                    title="{{ $canManage ? 'Kelola organization' : ($provider ? 'API key belum diisi pada product offering' : 'Provider belum didukung') }}"><i
                                        class="fa-solid fa-users-gear me-1"></i>Kelola</button></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-5 text-center text-muted"><i
                                    class="fa-solid fa-building-circle-exclamation fa-2x mb-3 d-block"></i>Belum ada
                                organization. Organization dibuat saat product offering disimpan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($organizations->hasPages())
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 border-top p-3">
                <div class="small text-muted">Menampilkan {{ $organizations->firstItem() }} sampai
                    {{ $organizations->lastItem() }} dari {{ $organizations->total() }} organization</div>
                <div class="metronic-pagination">{{ $organizations->links('admin.master.pagination') }}</div>
            </div>
        @endif
    </section>

    <div class="modal fade" id="organization-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="organization-title">Kelola organization</h5>
                        <div class="small text-muted" id="organization-subtitle"></div>
                    </div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div id="organization-alert" class="alert d-none" role="alert" aria-live="polite"></div>
                    <div id="organization-loading" class="py-5 text-center text-muted"><span
                            class="spinner-border spinner-border-sm me-2"></span>Menghubungkan ke provider...</div>
                    <div id="organization-content" class="d-none">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Undang user</h6>
                            </div>
                            <form id="organization-invite-form" class="p-3 row g-3">
                                <div class="col-md-6"><label for="invite-email" class="form-label">Email</label><input
                                        type="email" name="email" id="invite-email" class="form-control"
                                        placeholder="user@example.com" required></div>
                                <div class="col-md-4"><label for="invite-role" class="form-label">Role</label><select
                                        name="role" id="invite-role" class="form-select" required></select></div>
                                <div class="col-md-4 d-none" id="invite-organization-group"><label for="invite-organization-id" class="form-label">OpenAI Organization</label><select
                                        name="organization_id" id="invite-organization-id" class="form-select"><option value="">Pilih organization</option></select></div>
                                <div class="col-md-4 d-none" id="invite-project-group"><label for="invite-project-id" class="form-label">OpenAI Project ID</label><input
                                        type="text" name="project_id" id="invite-project-id" class="form-control"
                                        placeholder="proj_..."></div>
                                <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100"><i
                                            class="fa-solid fa-paper-plane me-1"></i>Invite</button></div>
                                <div class="col-12 small text-muted">Role yang tersedia mengikuti kebijakan organization
                                    provider.</div>
                            </form>
                            <div id="organization-website-invite" class="p-3 d-none">
                                <p class="mb-3">Akun ChatGPT Plus tidak menyediakan session/member API. Tambahkan member melalui website ChatGPT.</p>
                                <a class="btn btn-primary" id="organization-website-invite-link" href="#" target="_blank" rel="noopener">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Buka pengaturan member ChatGPT
                                </a>
                            </div>
                        </div>

                        <section class="card mb-4" id="members-section">
                            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <h6 class="card-title mb-0">Members</h6><small class="text-muted" id="members-count">0
                                        member</small>
                                </div><button type="button" class="btn btn-light btn-sm d-none" id="members-load-more">Muat
                                    data provider berikutnya</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-4">User</th>
                                            <th>Role</th>
                                            <th>Bergabung</th>
                                            <th class="text-end pe-4">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="members-body"></tbody>
                                </table>
                            </div>
                            <div class="border-top p-3 d-flex align-items-center justify-content-between"><small
                                    class="text-muted" id="members-page-label"></small>
                                <div class="btn-group btn-group-sm"><button class="btn btn-light" id="members-prev"
                                        type="button">Sebelumnya</button><button class="btn btn-light" id="members-next"
                                        type="button">Berikutnya</button></div>
                            </div>
                        </section>

                        <section class="card" id="invites-section">
                            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <h6 class="card-title mb-0">Invitations</h6><small class="text-muted"
                                        id="invites-count">0 invitation</small>
                                </div><button type="button" class="btn btn-light btn-sm d-none" id="invites-load-more">Muat
                                    data provider berikutnya</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-4">Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Dikirim</th>
                                            <th class="text-end pe-4">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invites-body"></tbody>
                                </table>
                            </div>
                            <div class="border-top p-3 d-flex align-items-center justify-content-between"><small
                                    class="text-muted" id="invites-page-label"></small>
                                <div class="btn-group btn-group-sm"><button class="btn btn-light" id="invites-prev"
                                        type="button">Sebelumnya</button><button class="btn btn-light" id="invites-next"
                                        type="button">Berikutnya</button></div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modalElement = document.getElementById('organization-modal');
            const modal = new bootstrap.Modal(modalElement);
            const loading = document.getElementById('organization-loading');
            const content = document.getElementById('organization-content');
            const alertBox = document.getElementById('organization-alert');
            const pageSize = 10;
            let activeOrganization = null;
            let members = { data: [], page: 1, hasMore: false, cursor: null };
            let invites = { data: [], page: 1, hasMore: false, cursor: null };

            const request = async (url, options = {}) => {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        Accept: 'application/json',
                        ...(options.body ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } : {}),
                        ...(options.headers || {}),
                    },
                });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Permintaan ke provider gagal.');
                return payload;
            };
            const notify = (message, type = 'danger') => {
                alertBox.className = `alert alert-${type}`;
                alertBox.textContent = message;
                alertBox.classList.remove('d-none');
            };
            const clearNotify = () => { alertBox.classList.add('d-none'); alertBox.textContent = ''; };
            const setProviderCapabilities = (data) => {
                const capabilities = data.capabilities || {};
                const apiInvite = Boolean(capabilities.invites);
                document.getElementById('organization-invite-form').classList.toggle('d-none', !apiInvite);
                document.getElementById('organization-website-invite').classList.toggle('d-none', !capabilities.invite_via_website);
                document.getElementById('organization-website-invite-link').href = capabilities.management_url || '#';
                document.getElementById('members-section').classList.toggle('d-none', !capabilities.members);
                document.getElementById('invites-section').classList.toggle('d-none', !capabilities.invites);
                document.getElementById('invite-organization-group').classList.toggle('d-none', data.provider !== 'chatgpt');
                document.getElementById('invite-project-group').classList.toggle('d-none', data.provider !== 'chatgpt');
                document.getElementById('invite-project-id').required = data.provider === 'chatgpt';
            };
            const formatDate = (value) => {
                if (!value) return '-';
                const date = typeof value === 'number' ? new Date(value * 1000) : new Date(value);
                return Number.isNaN(date.getTime()) ? '-' : new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(date);
            };
            const createCell = (row, value, className = '') => {
                const cell = document.createElement('td');
                if (className) cell.className = className;
                cell.textContent = value || '-';
                row.append(cell);
                return cell;
            };

            const renderMembers = () => {
                const body = document.getElementById('members-body');
                body.replaceChildren();
                const start = (members.page - 1) * pageSize;
                const visible = members.data.slice(start, start + pageSize);
                visible.forEach((member) => {
                    const row = document.createElement('tr');
                    const userCell = document.createElement('td'); userCell.className = 'ps-4';
                    const name = document.createElement('div'); name.className = 'fw-semibold text-dark'; name.textContent = member.name || member.email || member.id;
                    const email = document.createElement('small'); email.className = 'text-muted'; email.textContent = member.email || '';
                    userCell.append(name, email); row.append(userCell);
                    createCell(row, member.role || '-');
                    createCell(row, formatDate(member.added_at || member.created_at));
                    const actionCell = document.createElement('td'); actionCell.className = 'text-end pe-4';
                    const button = document.createElement('button'); button.type = 'button'; button.className = 'btn btn-light btn-sm text-danger'; button.innerHTML = '<i class="fa-solid fa-user-minus me-1"></i>Kick';
                    button.addEventListener('click', () => removeMember(member)); actionCell.append(button); row.append(actionCell);
                    body.append(row);
                });
                if (!visible.length) {
                    const row = document.createElement('tr'); const cell = document.createElement('td'); cell.colSpan = 4; cell.className = 'py-4 text-center text-muted'; cell.textContent = 'Tidak ada member.'; row.append(cell); body.append(row);
                }
                const pages = Math.max(1, Math.ceil(members.data.length / pageSize));
                document.getElementById('members-count').textContent = `${members.data.length}${members.hasMore ? '+' : ''} member dimuat`;
                document.getElementById('members-page-label').textContent = `Halaman ${members.page} dari ${pages}`;
                document.getElementById('members-prev').disabled = members.page <= 1;
                document.getElementById('members-next').disabled = members.page >= pages;
                document.getElementById('members-load-more').classList.toggle('d-none', !members.hasMore);
            };

            const renderInvites = () => {
                const body = document.getElementById('invites-body');
                body.replaceChildren();
                const start = (invites.page - 1) * pageSize;
                const visible = invites.data.slice(start, start + pageSize);
                visible.forEach((invite) => {
                    const row = document.createElement('tr');
                    createCell(row, invite.email, 'ps-4');
                    createCell(row, invite.role || '-');
                    const status = invite.status || (invite.accepted_at ? 'accepted' : 'pending');
                    const statusCell = document.createElement('td'); const badge = document.createElement('span');
                    badge.className = `badge ${status === 'pending' ? 'badge-soft-primary' : (status === 'accepted' ? 'badge-soft-success' : 'badge-soft-danger')}`; badge.textContent = status; statusCell.append(badge); row.append(statusCell);
                    createCell(row, formatDate(invite.invited_at || invite.created_at));
                    const actionCell = document.createElement('td'); actionCell.className = 'text-end pe-4';
                    if (status === 'pending') {
                        const button = document.createElement('button'); button.type = 'button'; button.className = 'btn btn-light btn-sm text-danger'; button.innerHTML = '<i class="fa-solid fa-xmark me-1"></i>Batalkan';
                        button.addEventListener('click', () => cancelInvite(invite)); actionCell.append(button);
                    } else actionCell.textContent = '-';
                    row.append(actionCell); body.append(row);
                });
                if (!visible.length) {
                    const row = document.createElement('tr'); const cell = document.createElement('td'); cell.colSpan = 5; cell.className = 'py-4 text-center text-muted'; cell.textContent = 'Tidak ada invitation.'; row.append(cell); body.append(row);
                }
                const pages = Math.max(1, Math.ceil(invites.data.length / pageSize));
                document.getElementById('invites-count').textContent = `${invites.data.length}${invites.hasMore ? '+' : ''} invitation dimuat`;
                document.getElementById('invites-page-label').textContent = `Halaman ${invites.page} dari ${pages}`;
                document.getElementById('invites-prev').disabled = invites.page <= 1;
                document.getElementById('invites-next').disabled = invites.page >= pages;
                document.getElementById('invites-load-more').classList.toggle('d-none', !invites.hasMore);
            };

            const loadMore = async (resource) => {
                const state = resource === 'users' ? members : invites;
                const url = new URL(resource === 'users' ? activeOrganization.membersUrl : activeOrganization.invitesUrl, window.location.origin);
                if (state.cursor) url.searchParams.set('after', state.cursor);
                const result = await request(url);
                const next = result.data || {};
                state.data.push(...(next.data || []));
                state.hasMore = Boolean(next.has_more);
                state.cursor = next.last_id || null;
                resource === 'users' ? renderMembers() : renderInvites();
            };

            const removeMember = async (member) => {
                if (!confirm(`Keluarkan ${member.email || member.name || 'user ini'} dari organization?`)) return;
                try {
                    const url = activeOrganization.memberUrl.replace('__MEMBER__', encodeURIComponent(member.id));
                    await request(url, { method: 'DELETE' });
                    members.data = members.data.filter(item => item.id !== member.id);
                    renderMembers(); notify('User berhasil dikeluarkan.', 'success');
                } catch (error) { notify(error.message); }
            };
            const cancelInvite = async (invite) => {
                if (!confirm(`Batalkan invitation untuk ${invite.email}?`)) return;
                try {
                    const url = activeOrganization.cancelInviteUrl.replace('__INVITE__', encodeURIComponent(invite.id));
                    await request(url, { method: 'DELETE' });
                    invites.data = invites.data.filter(item => item.id !== invite.id);
                    renderInvites(); notify('Invitation berhasil dibatalkan.', 'success');
                } catch (error) { notify(error.message); }
            };

            document.querySelectorAll('.manage-organization').forEach(button => button.addEventListener('click', async () => {
                activeOrganization = {
                    showUrl: button.dataset.showUrl,
                    membersUrl: button.dataset.membersUrl,
                    invitesUrl: button.dataset.invitesUrl,
                    inviteUrl: button.dataset.inviteUrl,
                    memberUrl: button.dataset.memberUrl,
                    cancelInviteUrl: button.dataset.cancelInviteUrl,
                };
                members = { data: [], page: 1, hasMore: false, cursor: null };
                invites = { data: [], page: 1, hasMore: false, cursor: null };
                clearNotify(); content.classList.add('d-none'); loading.classList.remove('d-none');
                document.getElementById('organization-title').textContent = 'Kelola organization';
                document.getElementById('organization-subtitle').textContent = '';
                modal.show();
                try {
                    const payload = await request(activeOrganization.showUrl);
                    const data = payload.data;
                    setProviderCapabilities(data);
                    document.getElementById('organization-title').textContent = data.organization.name;
                    document.getElementById('organization-subtitle').textContent = `${data.provider_label} · ${data.organization.email || '-'} · ${data.organization.external_id || 'ID organisasi provider tidak tersedia'}`;
                    const roleSelect = document.getElementById('invite-role'); roleSelect.replaceChildren();
                    data.roles.forEach(role => roleSelect.add(new Option(role, role)));
                    const organizationSelect = document.getElementById('invite-organization-id');
                    organizationSelect.replaceChildren(new Option('Pilih organization', ''));
                    (data.available_organizations || []).forEach(item => organizationSelect.add(new Option(`${item.name || item.id} (${item.id})`, item.id)));
                    organizationSelect.required = false;
                    members = { ...members, ...(data.members || {}) };
                    invites = { ...invites, ...(data.invites || {}) };
                    renderMembers(); renderInvites();
                    loading.classList.add('d-none'); content.classList.remove('d-none');
                } catch (error) {
                    loading.classList.add('d-none');
                    notify(error.message);
                }
            }));

            document.getElementById('organization-invite-form').addEventListener('submit', async (event) => {
                event.preventDefault(); clearNotify();
                const form = event.currentTarget;
                const button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    button.dataset.originalHtml = button.innerHTML;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Mengirim...';
                }
                try {
                    const payload = await request(activeOrganization.inviteUrl, { method: 'POST', body: JSON.stringify(Object.fromEntries(new FormData(form).entries())) });
                    const invite = payload.data;
                    invites.data.unshift({ ...invite, status: invite.status || 'pending' });
                    invites.page = 1; renderInvites(); form.reset();
                    notify(payload.message, 'success');
                } catch (error) { notify(error.message); }
                finally {
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = button.dataset.originalHtml;
                    }
                }
            });

            document.getElementById('members-prev').addEventListener('click', () => { members.page--; renderMembers(); });
            document.getElementById('members-next').addEventListener('click', () => { members.page++; renderMembers(); });
            document.getElementById('invites-prev').addEventListener('click', () => { invites.page--; renderInvites(); });
            document.getElementById('invites-next').addEventListener('click', () => { invites.page++; renderInvites(); });
            document.getElementById('members-load-more').addEventListener('click', async (event) => { event.currentTarget.disabled = true; try { await loadMore('users'); } catch (error) { notify(error.message); } finally { event.currentTarget.disabled = false; } });
            document.getElementById('invites-load-more').addEventListener('click', async (event) => { event.currentTarget.disabled = true; try { await loadMore('invites'); } catch (error) { notify(error.message); } finally { event.currentTarget.disabled = false; } });
        });
    </script>
@endsection
