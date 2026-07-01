<script>
document.addEventListener('DOMContentLoaded', () => {
    fetchOwnReferralId();
    fetchReferrals();

    document.getElementById('referral-id-copy-btn').addEventListener('click', copyReferralId);
});

function authToken() {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        window.location.href = "{{ route('wallet.view.generate') }}";
        return null;
    }
    return token;
}

async function fetchOwnReferralId() {
    const token = authToken();
    if (!token) return;

    try {
        const response = await fetch("{{ url('api/v1/profile') }}", {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token
            }
        });

        const result = await response.json();

        if (response.status === 403 && result.data && result.data.redirect_to) {
            window.location.href = "{{ route('wallet.view.generate') }}";
            return;
        }

        if (!result.success || !result.data) return;

        document.getElementById('referral-id-field').value = result.data.user.id;
    } catch (error) {
        console.error('Referral ID fetch failure:', error);
    }
}

function copyReferralId() {
    const field = document.getElementById('referral-id-field');
    const copiedText = document.getElementById('referral-id-copied-text');

    if (!field.value) return;

    navigator.clipboard.writeText(field.value).then(() => {
        copiedText.classList.remove('d-none');
        setTimeout(() => copiedText.classList.add('d-none'), 2000);
    });
}

async function fetchReferrals() {
    const token = authToken();
    if (!token) return;

    const tableBody = document.getElementById('referrals-table');
    const emptyAlert = document.getElementById('empty-referrals-alert');

    try {
        const response = await fetch("{{ url('api/v1/referrals') }}", {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token
            }
        });

        const result = await response.json();

        if (response.status === 403 && result.data && result.data.redirect_to) {
            window.location.href = "{{ route('wallet.view.generate') }}";
            return;
        }

        tableBody.innerHTML = '';

        if (result.success && result.data && Array.isArray(result.data.referrals) && result.data.referrals.length > 0) {
            emptyAlert.style.display = 'none';

            result.data.referrals.forEach(referral => {
                const fullName = referral.profile
                    ? [referral.profile.first_name, referral.profile.last_name].filter(Boolean).join(' ')
                    : '';

                tableBody.innerHTML += `
                    <tr class="border-bottom-0">
                        <td class="py-3 pl-0 font-weight-bold text-dark">${referral.username || fullName || '—'}</td>
                        <td class="py-3 text-secondary">${referral.email || '—'}</td>
                        <td class="py-3"><span class="badge badge-${referral.status === 'active' ? 'success' : 'secondary'} text-capitalize px-2 py-1">${referral.status || 'unknown'}</span></td>
                        <td class="py-3 pr-0 text-end text-muted">${referral.created_at ? new Date(referral.created_at).toLocaleDateString() : '—'}</td>
                    </tr>
                `;
            });
        } else {
            tableBody.innerHTML = '';
            emptyAlert.style.display = 'block';
        }
    } catch (error) {
        console.error('Referrals fetch failure:', error);
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-danger">Unable to load referrals right now.</td></tr>';
    }
}
</script>