<x-mail::message>
# Deposit Initiated

Hi {{ $user->username }},

Your deposit request has been received and is **pending review**.

<x-mail::table>
| Field | Details |
|:------|:--------|
| Asset | {{ $asset?->label ?? 'N/A' }} ({{ strtoupper($asset?->name ?? '') }}) |
| Amount | {{ number_format($transaction->amount, 5) }} |
| Status | {{ ucfirst($transaction->status) }} |
| Reference | `{{ $transaction->id }}` |
</x-mail::table>

@if($subMethod)
**Payment Instructions**

Please send your payment using the following details:

<x-mail::table>
| Field | Details |
|:------|:--------|
| Method | {{ $subMethod->method?->name ?? '' }} — {{ $subMethod->name }} |
@if($subMethod->wallet_address)
| Address | {{ $subMethod->wallet_address }} |
@endif
@if($subMethod->network)
| Network | {{ $subMethod->network }} |
@endif
@if($subMethod->account_name)
| Account Name | {{ $subMethod->account_name }} |
@endif
@if($subMethod->account_number)
| Account Number | {{ $subMethod->account_number }} |
@endif
@if($subMethod->bank_name)
| Bank | {{ $subMethod->bank_name }} |
@endif
@if($subMethod->routing_number)
| Routing | {{ $subMethod->routing_number }} |
@endif
@if($subMethod->swift_code)
| SWIFT / BIC | {{ $subMethod->swift_code }} |
@endif
@if($subMethod->iban)
| IBAN | {{ $subMethod->iban }} |
@endif
</x-mail::table>

@if($subMethod->instructions)
**Additional Instructions:** {{ $subMethod->instructions }}
@endif

After sending payment, log in and upload your proof of payment.
@endif

If you did not initiate this deposit, please contact support immediately.

Thanks,
**{{ $companyName }}**
</x-mail::message>