@extends('layouts.user')
@section('title', 'Buy')
@section('page-title', 'Buy Crypto')

@section('content')
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 border-0">
                    <span class="card-title font-weight-bold text-dark">Buy Crypto</span>
                </div>
                <div class="card-body pt-0">

                    <div id="buy-form-alert" class="alert alert-danger py-2 px-3" style="font-size:13px; display:none;"></div>
                    <div id="buy-form-success" class="alert alert-success py-2 px-3" style="font-size:13px; display:none;"></div>

                    <form id="buy-form">
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted mb-1">Asset to Buy</label>
                            <select id="buy-asset" class="form-control form-control-sm" required>
                                <option value="">Select an asset</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted mb-1">You Send (USD)</label>
                            <input type="number" step="any" min="0" id="buy-fiat-amount" class="form-control form-control-sm" placeholder="0.00" required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted mb-1">Payment Method</label>
                            <select id="buy-method" class="form-control form-control-sm">
                                <option value="">Select a method</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted mb-1">Channel</label>
                            <select id="buy-sub-method" class="form-control form-control-sm" disabled>
                                <option value="">Select a payment method first</option>
                            </select>
                        </div>

                        <!-- Live preview -->
                        <div id="buy-preview" class="border rounded p-3 mb-3 bg-light" style="font-size:13px; display:none;">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Price</span>
                                <span id="buy-preview-price">—</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Charge (<span id="buy-preview-charge-pct">0</span>%)</span>
                                <span id="buy-preview-charge" class="text-danger">—</span>
                            </div>
                            <div class="d-flex justify-content-between font-weight-bold">
                                <span>You receive</span>
                                <span id="buy-preview-receive" class="text-success">—</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm font-weight-bold" id="buy-submit-btn">
                            Place Buy Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Upload proof for a pending buy order -->
    <div id="buy-proof-section" class="row mb-4" style="display:none;">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 border-0">
                    <span class="card-title font-weight-bold text-dark">Upload Payment Proof</span>
                </div>
                <div class="card-body pt-0">
                    <p class="small text-muted mb-3">Send the payment for your buy order then upload your receipt or screenshot here.</p>

                    <div id="buy-proof-alert"   class="alert alert-danger py-2 px-3"  style="font-size:13px; display:none;"></div>
                    <div id="buy-proof-success" class="alert alert-success py-2 px-3" style="font-size:13px; display:none;"></div>

                    <form id="buy-proof-form">
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted mb-1">Transaction</label>
                            <select id="buy-proof-tx-select" class="form-control form-control-sm">
                                <option value="">Select a pending buy order</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted mb-1">Receipt / Screenshot</label>
                            <input type="file" id="buy-proof-file" class="form-control form-control-sm" accept="image/jpeg,image/png,image/jpg">
                            <small class="text-muted">JPG or PNG, up to 2MB.</small>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm font-weight-bold" id="buy-proof-submit-btn">
                            Submit Proof
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4" id="buy-pending-wrapper" style="display:none;">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 border-0">
                    <span class="card-title font-weight-bold text-dark">Pending Buy Orders</span>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0" style="font-size:13px;">
                            <thead>
                                <tr class="text-muted border-bottom" style="font-size:12px;">
                                    <th class="py-2 pl-0">Asset</th>
                                    <th class="py-2">Fiat Sent</th>
                                    <th class="py-2">Crypto Due</th>
                                    <th class="py-2 pr-0 text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody id="buy-pending-table"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Buy history -->
    <div class="row" id="buy-history-wrapper" style="display:none;">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 border-0">
                    <span class="card-title font-weight-bold text-dark">Buy History</span>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0" style="font-size:13px;">
                            <thead>
                                <tr class="text-muted border-bottom" style="font-size:12px;">
                                    <th class="py-2 pl-0">Asset</th>
                                    <th class="py-2">Fiat Sent</th>
                                    <th class="py-2">Crypto Received</th>
                                    <th class="py-2 pr-0 text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody id="buy-history-table"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@include('user.buy_modal')
@endsection
