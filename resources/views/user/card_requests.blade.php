@extends('layouts.user')
@section('title', 'Card Requests')
@section('page-title', 'Card Requests')

@section('content')
<div class="container-fluid">

    <!-- New card request form -->
    <div class="row mb-4">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 border-0">
                    <span class="card-title font-weight-bold text-dark">Request a Card</span>
                </div>
                <div class="card-body pt-0">

                    <div id="card-request-alert" class="alert alert-danger py-2 px-3" style="font-size:13px; display:none;"></div>
                    <div id="card-request-success" class="alert alert-success py-2 px-3" style="font-size:13px; display:none;"></div>

                    <form id="card-request-form">
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted mb-1">Card Type</label>
                            <select id="card-request-type" class="form-control form-control-sm" required>
                                <option value="">Select a card type</option>
                                <option value="virtual">Virtual Card</option>
                                <option value="physical">Physical Card</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted mb-1">Requested Amount</label>
                            <input type="number" step="any" min="1" id="card-request-amount" class="form-control form-control-sm" placeholder="0.00" required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted mb-1">Credit Score</label>
                            <input type="text" id="card-request-credit-score" class="form-control form-control-sm" placeholder="e.g. 720" maxlength="10" required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted mb-1">Supporting Document <span class="text-muted font-weight-normal">(optional)</span></label>
                            <input type="file" id="card-request-img-one" class="form-control form-control-sm" accept="image/jpeg,image/png,image/jpg">
                        </div>

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted mb-1">Additional Document <span class="text-muted font-weight-normal">(optional)</span></label>
                            <input type="file" id="card-request-img-two" class="form-control form-control-sm" accept="image/jpeg,image/png,image/jpg">
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm font-weight-bold" id="card-request-submit-btn">
                            Submit Request
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- Request history -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 border-0">
                    <span class="card-title font-weight-bold text-dark">Your Card Requests</span>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0" style="font-size: 13px;">
                            <thead>
                                <tr class="text-muted border-bottom" style="font-size: 12px;">
                                    <th class="py-2 pl-0">Type</th>
                                    <th class="py-2">Amount</th>
                                    <th class="py-2">Credit Score</th>
                                    <th class="py-2">Status</th>
                                    <th class="py-2 pr-0 text-end">Submitted</th>
                                </tr>
                            </thead>
                            <tbody id="card-requests-table">
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Loading requests...</td>
                                </tr>
                            </tbody>
                        </table>
                        <div id="empty-card-requests-alert" class="text-center py-5 text-muted" style="display:none;">
                            You haven't submitted any card requests yet.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@include('user.card_requests_modal')

@endsection