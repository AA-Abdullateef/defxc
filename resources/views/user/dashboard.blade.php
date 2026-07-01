@extends('layouts.user')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Top Stats / Overview Row -->
    <div class="row mb-4">
        <!-- Asset Liquidity Balance Card -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <h6 class="text-muted text-uppercase mb-2 font-weight-bold" style="font-size: 12px; letter-spacing: 0.5px;">Asset Liquidity</h6>
                    <div class="d-flex align-items-baseline mb-2">
                        <span class="h1 mb-0 font-weight-bold text-success" id="wallet-balance">$0.00</span>
                        <span class="text-muted ml-2" style="font-size: 14px;">USD</span>
                    </div>
                    
                    <!-- Dynamic Asset Badges Container Row -->
                    <div class="d-flex flex-wrap gap-2 mb-3" id="asset-badges-container">
                        <!-- Appended dynamically via javascript -->
                    </div>

                    <p class="text-muted mb-0 small"><span class="text-success">●</span> Live Sync Network Status Stable (127.0.0.1)</p>
                </div>
            </div>
        </div>

        <!-- System Controls / Actions Card -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase mb-2 font-weight-bold" style="font-size: 12px; letter-spacing: 0.5px;">Session Controls</h6>
                        <p class="text-muted small mb-3">You are securely authenticated via cryptographic token signatures.</p>
                    </div>
                    <div>
                        <button id="logout-btn" class="btn btn-danger btn-sm px-4 font-weight-bold">✕ Disconnect Wallet</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <!-- Workspace Renders natively within the DOM flow instantly with zero display adjustments -->
            <div id="dashboard-content">
                
                
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-transparent py-3 border-0"><span class="card-title font-weight-bold text-dark">Wallet Identity Cluster</span></div>
                    <div class="card-body pt-0">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted mb-1 small d-block font-weight-bold">Wallet ID</label>
                                <div id="wallet-id" class="text-muted font-family-monospace text-break small bg-light p-2 rounded" style="font-size:11px;">Loading...</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted mb-1 small d-block font-weight-bold">Public Reference Key</label>
                                <div id="wallet-public-key" class="text-muted font-family-monospace text-break small bg-light p-2 rounded" style="font-size:11px;">Loading...</div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="text-muted mb-1 small d-block font-weight-bold">Fingerprint Signature Identification Tag</label>
                                <div id="wallet-fingerprint" class="text-muted font-family-monospace text-break small bg-light p-2 rounded" style="font-size:11px;">Loading...</div>
                            </div>

                            <!-- Secret Recovery Mnemonic Field Grid Module -->
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="text-muted mb-0 small font-weight-bold">Secret Recovery Mnemonic (Database Decrypted Payload)</label>
                                    <button type="button" onclick="toggleMnemonicVisibility()" id="toggle-btn" class="btn btn-link text-primary p-0 small font-weight-bold" style="font-size: 11px; text-decoration: none;">👁️ Show Words</button>
                                </div>
                                <div id="mnemonic-text-box" class="text-dark font-family-monospace p-3 rounded bg-light border" style="font-size: 12px; letter-spacing: 0.3px; line-height: 1.6; filter: blur(5px); user-select: none; transition: filter 0.2s ease;">
                                    Loading encrypted cluster seed values...
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">
                        <p class="text-muted small mb-0">
                            ⚠️ <strong>Critical Security Notice:</strong> Keep this workstation layout strictly confidential. Access to these backup signatures allows extraction of all linked assets.
                        </p>
                    </div>
                </div>

                <!-- Card 2: Recent Transactions Logs Table -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-transparent py-3 border-0"><span class="card-title font-weight-bold text-dark">Recent Transaction Activities</span></div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle mb-0" style="font-size: 13px;">
                                <thead>
                                    <tr class="text-muted border-bottom" style="font-size: 12px;">
                                        <th class="py-2 pl-0">Transaction ID</th>
                                        <th class="py-2">Asset Type</th>
                                        <th class="py-2">Execution Action</th>
                                        <th class="py-2">Amount Metric</th>
                                        <th class="py-2 pr-0">Status Reference</th>
                                    </tr>
                                </thead>
                                <tbody id="transactions-log-table">
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Synchronizing active journal entries...</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div id="empty-transactions-alert" class="text-center py-4 text-muted" style="opacity: 0; height: 0; overflow: hidden; padding: 0 !important; margin: 0 !important; transition: opacity 0.2s ease;">
                                No financial transaction history events recorded for this security reference core.
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@include('user.dashboard_modal') 

@endsection
