@extends('layouts.user')
@section('title', 'Deposit')
@section('page-title', 'Deposit Funds')

@section('content')
<div class="container-fluid">

    <!-- Step 1: Pending deposits + new deposit form -->
    <div id="deposit-start-view">

        <!-- Pending deposits awaiting proof / review -->
        <div class="row mb-4" id="pending-deposits-wrapper" style="display:none;">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent py-3 border-0">
                        <span class="card-title font-weight-bold text-dark">Pending Deposits</span>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle mb-0" style="font-size: 13px;">
                                <thead>
                                    <tr class="text-muted border-bottom" style="font-size: 12px;">
                                        <th class="py-2 pl-0">Asset</th>
                                        <th class="py-2">Amount</th>
                                        <th class="py-2">Status</th>
                                        <th class="py-2 pr-0 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="pending-deposits-table"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- New deposit form -->
        <div class="row">
            <div class="col-12 col-lg-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent py-3 border-0">
                        <span class="card-title font-weight-bold text-dark">New Deposit</span>
                    </div>
                    <div class="card-body pt-0">

                        <div id="deposit-form-alert" class="alert alert-danger py-2 px-3" style="font-size:13px; display:none;"></div>

                        <form id="deposit-form">
                            <div class="form-group mb-3">
                                <label class="small font-weight-bold text-muted mb-1">Asset</label>
                                <select id="deposit-asset" class="form-control form-control-sm" required>
                                    <option value="">Select an asset</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label class="small font-weight-bold text-muted mb-1">Payment Method</label>
                                <select id="deposit-method" class="form-control form-control-sm" required>
                                    <option value="">Select a payment method</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label class="small font-weight-bold text-muted mb-1">Channel</label>
                                <select id="deposit-sub-method" class="form-control form-control-sm" required disabled>
                                    <option value="">Select a payment method first</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label class="small font-weight-bold text-muted mb-1">Amount</label>
                                <input type="number" step="any" min="0" id="deposit-amount" class="form-control form-control-sm" placeholder="0.00" required>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm font-weight-bold" id="deposit-submit-btn">
                                Continue
                            </button>
                        </form>

                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Step 2: Payment instructions + proof upload -->
    <div id="deposit-instructions-view" style="display:none;">
        <button type="button" class="btn btn-link p-0 mb-3 font-weight-bold text-secondary" id="back-to-deposit-start-btn" style="font-size:13px; text-decoration:none;">
            &larr; Back
        </button>

        <div class="row">
            <div class="col-12 col-lg-7">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-transparent py-3 border-0">
                        <span class="card-title font-weight-bold text-dark">Payment Instructions</span>
                    </div>
                    <div class="card-body pt-0">
                        <p class="small text-muted mb-3">
                            Send <strong id="deposit-instructions-amount">0.00</strong> using the details below, then upload your proof of payment.
                        </p>
                        <div id="deposit-instructions-fields" class="mb-3" style="font-size:13px;"></div>

                        <!-- QR code rendered here when a wallet address is present -->
                        <div id="deposit-qr-wrapper" class="text-center my-3" style="display:none;">
                            <p class="small text-muted mb-2">Scan to send payment</p>
                            <div id="deposit-qr-code" class="d-inline-block p-2 border rounded bg-white"></div>
                            <p class="small text-muted mt-2" id="deposit-qr-address" style="word-break:break-all; font-size:11px;"></p>
                        </div>
                        <div id="deposit-instructions-notes" class="small text-muted" style="display:none;"></div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent py-3 border-0">
                        <span class="card-title font-weight-bold text-dark">Upload Proof of Payment</span>
                    </div>
                    <div class="card-body pt-0">

                        <div id="deposit-proof-alert" class="alert alert-danger py-2 px-3" style="font-size:13px; display:none;"></div>
                        <div id="deposit-proof-success" class="alert alert-success py-2 px-3" style="font-size:13px; display:none;"></div>

                        <form id="deposit-proof-form">
                            <div class="form-group mb-3">
                                <input type="file" id="deposit-photo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/jpg" required>
                                <small class="text-muted">JPG or PNG, up to 1MB.</small>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm font-weight-bold" id="deposit-proof-submit-btn">
                                Submit Proof
                            </button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@include('user.deposits_modal')

@endsection