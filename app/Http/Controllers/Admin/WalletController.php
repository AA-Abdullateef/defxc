<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * List all wallets (mnemonic accounts) with their associated user.
     */
    public function index(Request $request)
    {
        $query = Wallet::with('user')->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('fingerprint', 'like', "%{$s}%")
                  ->orWhere('public_key', 'like', "%{$s}%")
                  ->orWhereHas('user', fn ($userQuery) =>
                      $userQuery->where('username', 'like', "%{$s}%")
                          ->orWhere('email', 'like', "%{$s}%")
                  );
            });
        }

        $wallets = $query->paginate(25)->withQueryString();

        return view('admin.wallets.index', compact('wallets'));
    }

    /**
     * Show a single wallet with user details.
     * The mnemonic is hidden by default — admin can reveal it if needed.
     */
    public function show(Wallet $wallet)
    {
        $wallet->load('user.profile');

        return view('admin.wallets.show', compact('wallet'));
    }

    /**
     * Reveal the decrypted mnemonic — requires confirmation.
     * Action is logged in the audit trail.
     */
    public function revealMnemonic(Wallet $wallet)
    {
        AuditLog::record(
            action:      'wallet.mnemonic_reveal_blocked',
            actorId:     auth()->id(),
            actorType:   'admin',
            subjectType: 'wallet',
            subjectId:   $wallet->id,
        );

        return back()->with('error', 'Mnemonic cannot be revealed because it is never stored.');
    }

    /**
     * Detach a wallet from its user — does NOT delete the user account.
     */
    public function destroy(Wallet $wallet)
    {
        AuditLog::record(
            action:      'wallet.deleted',
            actorId:     auth()->id(),
            actorType:   'admin',
            subjectType: 'wallet',
            subjectId:   $wallet->id,
            before:      ['user_id' => $wallet->user_id],
        );

        $wallet->delete();

        return redirect()->route('admin.wallets.index')
                         ->with('success', 'Wallet record deleted.');
    }
}
