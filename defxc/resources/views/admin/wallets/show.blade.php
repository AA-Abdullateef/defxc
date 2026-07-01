@extends('layouts.admin')
@section('title', 'Wallet - ' . ($wallet->user?->username ?? 'Unregistered'))
@section('page-title', 'Wallet - ' . ($wallet->user?->username ?? 'Unregistered'))
@section('topbar-actions')
    <a href="{{ route('admin.wallets.index') }}" class="btn btn-ghost btn-sm">Back to Wallets</a>
    @if($wallet->user)
        <a href="{{ route('admin.users.show', $wallet->user) }}" class="btn btn-ghost btn-sm">View User</a>
    @endif
@endsection

@section('content')
<div class="grid-2">
    <div class="card">
        <div class="card-header"><span class="card-title">Wallet Identity</span></div>
        <div class="card-body">
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-item-label">Wallet ID</div>
                    <div class="detail-item-value mono" style="font-size:11px;color:var(--text-muted)">{{ $wallet->id }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-item-label">Created</div>
                    <div class="detail-item-value">{{ $wallet->created_at->format('d M Y, H:i') }}</div>
                </div>
                <div class="detail-item" style="grid-column:span 2">
                    <div class="detail-item-label">Fingerprint</div>
                    <div class="detail-item-value mono" style="font-size:11px;word-break:break-all;color:var(--text-muted)">{{ $wallet->fingerprint }}</div>
                </div>
                <div class="detail-item" style="grid-column:span 2">
                    <div class="detail-item-label">Public Key</div>
                    <div class="detail-item-value mono" style="font-size:11px;word-break:break-all;color:var(--text-muted)">{{ $wallet->public_key }}</div>
                </div>

                <!-- 💡 NEW: Injected Decrypted Mnemonic Row Slot -->
                <div class="detail-item" style="grid-column:span 2; margin-top: 5px;">
                    <div class="detail-item-label" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Secret Recovery Mnemonic</span>
                        <button type="button" onclick="toggleMnemonicVisibility()" id="toggle-btn" style="background: none; border: none; color: #2563eb; font-size: 11px; cursor: pointer; padding: 0; font-weight: 500;">👁️ Show Words</button>
                    </div>
                    <div id="mnemonic-text-box" class="mono" style="font-size: 12px; font-family: monospace; letter-spacing: 0.3px; line-height: 1.6; background: #f9fafb; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; margin-top: 5px; color: #1f2937; filter: blur(5px); user-select: none; transition: filter 0.2s ease;">
                        {{ $wallet->mnemonic ?? 'No phrase recorded.' }}
                    </div>
                </div>
            </div>

            <div class="divider"></div>
            <p style="font-size:13px;color:var(--text-muted);margin:0">
                ⚠️ <strong>Security Notice:</strong> Keep this information strictly confidential. Anyone with access to these backup words owns the wallet funds.
            </p>
        </div>
    </div>

    <script>
        function toggleMnemonicVisibility() {
            const box = document.getElementById('mnemonic-text-box');
            const btn = document.getElementById('toggle-btn');
            
            if (box.style.filter === 'blur(5px)') {
                box.style.filter = 'blur(0px)';
                box.style.userSelect = 'auto'; // Allows copying if unblurred
                btn.innerText = '🙈 Hide Words';
            } else {
                box.style.filter = 'blur(5px)';
                box.style.userSelect = 'none'; // Blocks selection if blurred
                btn.innerText = '👁️ Show Words';
            }
        }
    </script>

    <div class="card">
        <div class="card-header"><span class="card-title">Account Owner</span></div>
        <div class="card-body">
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-item-label">Username</div>
                    <div class="detail-item-value mono">{{ $wallet->user?->username ?? 'Not registered' }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-item-label">Email</div>
                    <div class="detail-item-value">{{ $wallet->user?->email ?? 'Not registered' }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-item-label">Full Name</div>
                    <div class="detail-item-value">{{ $wallet->user?->fullName() ?? 'Not registered' }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-item-label">Verified</div>
                    <div class="detail-item-value">
                        @if($wallet->user?->email_verified_at)
                            <span class="badge badge-active">Yes</span>
                        @else
                            <span class="badge badge-pending">No</span>
                        @endif
                    </div>
                </div>
            </div>
            @if($wallet->user)
                <div class="divider"></div>
                <a href="{{ route('admin.users.show', $wallet->user) }}" class="btn btn-primary btn-sm">View Full Profile</a>
            @endif
        </div>
    </div>
</div>

<div class="card" style="margin-top:24px;border-color:rgba(220,38,38,0.2)">
    <div class="card-header"><span class="card-title" style="color:var(--red)">Danger Zone</span></div>
    <div class="card-body">
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px">
            Deleting this wallet record removes the wallet identity. The user account will remain active
            but will no longer be accessible through this wallet.
        </p>
        <form method="POST" action="{{ route('admin.wallets.destroy', $wallet) }}"
              onsubmit="return confirm('Permanently delete this wallet record? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Delete Wallet Record</button>
        </form>
    </div>
</div>
@endsection
