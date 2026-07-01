@extends('layouts.user')
@section('title', 'Wallets')
@section('page-title', 'Wallets')

@section('content')
<div class="container-fluid">

    <!-- Platform Wallet -->
    <div class="row mb-4">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 border-0">
                    <span class="card-title font-weight-bold text-dark">Platform Wallet</span>
                </div>
                <div class="card-body pt-0" style="font-size:13px;">

                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Public Key</span>
                        <span class="font-weight-bold text-dark text-truncate" style="max-width:240px;" id="wallet-public-key">—</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Fingerprint</span>
                        <span class="font-weight-bold text-dark" id="wallet-fingerprint">—</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Recovery Phrase</span>
                        <span>
                            <span class="font-weight-bold text-dark" id="wallet-mnemonic" style="filter: blur(4px); user-select:none;">•••• •••• •••• •••• •••• ••••</span>
                            <button type="button" class="btn btn-link btn-sm p-0 ml-2" id="wallet-mnemonic-toggle-btn">Reveal</button>
                        </span>
                    </div>
                    <small class="text-danger d-block mt-2">Never share your recovery phrase with anyone.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Connect external wallet -->
    <div class="row mb-4">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 border-0">
                    <span class="card-title font-weight-bold text-dark">Connect External Wallet</span>
                </div>
                <div class="card-body pt-0">

                    <div id="connect-wallet-alert" class="alert alert-danger py-2 px-3" style="font-size:13px; display:none;"></div>
                    <div id="connect-wallet-success" class="alert alert-success py-2 px-3" style="font-size:13px; display:none;"></div>

                    <form id="connect-wallet-form">
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted mb-1">Wallet Provider</label>
                            <select id="connect-wallet-type" class="form-control form-control-sm" required>
                                <option value="">Select a provider</option>
                                <option value="metamask">MetaMask</option>
                                <option value="trustwallet">Trust Wallet</option>
                                <option value="walletconnect">WalletConnect</option>
                                <option value="coinbase">Coinbase Wallet</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted mb-1">Wallet Address</label>
                            <input type="text" id="connect-wallet-address" class="form-control form-control-sm" placeholder="0x..." required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted mb-1">Signature <span class="text-muted font-weight-normal">(optional)</span></label>
                            <input type="text" id="connect-wallet-signature" class="form-control form-control-sm" placeholder="Verification signature, if available">
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm font-weight-bold" id="connect-wallet-submit-btn">
                            Connect Wallet
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- Connected wallets -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 border-0">
                    <span class="card-title font-weight-bold text-dark">Connected Wallets</span>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0" style="font-size: 13px;">
                            <thead>
                                <tr class="text-muted border-bottom" style="font-size: 12px;">
                                    <th class="py-2 pl-0">Provider</th>
                                    <th class="py-2">Address</th>
                                    <th class="py-2 pr-0 text-end">Connected</th>
                                </tr>
                            </thead>
                            <tbody id="connections-table">
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">Loading connections...</td>
                                </tr>
                            </tbody>
                        </table>
                        <div id="empty-connections-alert" class="text-center py-5 text-muted" style="display:none;">
                            No external wallets connected yet.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@include('user.wallets_modal')

@endsection