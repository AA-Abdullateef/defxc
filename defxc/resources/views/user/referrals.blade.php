@extends('layouts.user')
@section('title', 'Referrals')
@section('page-title', 'Referrals')

@section('content')
<div class="container-fluid">

    <!-- Your referral ID -->
    <div class="row mb-4">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 border-0">
                    <span class="card-title font-weight-bold text-dark">Your Referral ID</span>
                </div>
                <div class="card-body pt-0">
                    <p class="text-muted small mb-2">Share this ID — new sign-ups can enter it during registration to be linked to your account.</p>
                    <div class="input-group input-group-sm">
                        <input type="text" id="referral-id-field" class="form-control form-control-sm" readonly>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-dark btn-sm font-weight-bold" id="referral-id-copy-btn">
                                Copy
                            </button>
                        </div>
                    </div>
                    <small class="text-success d-none" id="referral-id-copied-text">Copied!</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Referred users -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 border-0">
                    <span class="card-title font-weight-bold text-dark">Your Referrals</span>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0" style="font-size: 13px;">
                            <thead>
                                <tr class="text-muted border-bottom" style="font-size: 12px;">
                                    <th class="py-2 pl-0">User</th>
                                    <th class="py-2">Email</th>
                                    <th class="py-2">Status</th>
                                    <th class="py-2 pr-0 text-end">Joined</th>
                                </tr>
                            </thead>
                            <tbody id="referrals-table">
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Loading referrals...</td>
                                </tr>
                            </tbody>
                        </table>
                        <div id="empty-referrals-alert" class="text-center py-5 text-muted" style="display:none;">
                            You haven't referred anyone yet.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@include('user.referrals_modal')

@endsection