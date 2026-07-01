<script>
document.addEventListener('DOMContentLoaded', () => {
    fetchCardRequests();
    document.getElementById('card-request-form').addEventListener('submit', submitCardRequestForm);
});

function authToken() {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        window.location.href = "{{ route('wallet.view.generate') }}";
        return null;
    }
    return token;
}

async function fetchCardRequests() {
    const token = authToken();
    if (!token) return;

    const tableBody = document.getElementById('card-requests-table');
    const emptyAlert = document.getElementById('empty-card-requests-alert');

    try {
        const response = await fetch("{{ url('api/v1/card-requests') }}", {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token
            }
        });

        const result = await response.json();
        tableBody.innerHTML = '';

        if (result.success && Array.isArray(result.data) && result.data.length > 0) {
            emptyAlert.style.display = 'none';

            result.data.forEach(cardRequest => {
                let badgeClass = 'badge-warning';
                const statusStr = (cardRequest.status || 'pending').toLowerCase();
                if (statusStr === 'approved') badgeClass = 'badge-success';
                if (statusStr === 'rejected') badgeClass = 'badge-danger';

                const statusLabel = statusStr.charAt(0).toUpperCase() + statusStr.slice(1);
                const submittedDate = cardRequest.created_at ? new Date(cardRequest.created_at).toLocaleDateString() : '—';

                tableBody.innerHTML += `
                    <tr class="border-bottom-0">
                        <td class="py-3 pl-0 font-weight-bold text-dark text-capitalize">${cardRequest.type || '—'}</td>
                        <td class="py-3 text-secondary">${cardRequest.amount}</td>
                        <td class="py-3 text-secondary">${cardRequest.credit_score || '—'}</td>
                        <td class="py-3"><span class="badge ${badgeClass} px-2 py-1">${statusLabel}</span></td>
                        <td class="py-3 pr-0 text-end text-muted">${submittedDate}</td>
                    </tr>
                `;
            });
        } else {
            tableBody.innerHTML = '';
            emptyAlert.style.display = 'block';
        }
    } catch (error) {
        console.error('Card requests fetch failure:', error);
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Unable to load card requests right now.</td></tr>';
    }
}

async function submitCardRequestForm(event) {
    event.preventDefault();

    const token = authToken();
    if (!token) return;

    const alertBox = document.getElementById('card-request-alert');
    const successBox = document.getElementById('card-request-success');
    const submitBtn = document.getElementById('card-request-submit-btn');

    alertBox.style.display = 'none';
    successBox.style.display = 'none';

    const type = document.getElementById('card-request-type').value;
    const amount = document.getElementById('card-request-amount').value;
    const creditScore = document.getElementById('card-request-credit-score').value;
    const imgOneInput = document.getElementById('card-request-img-one');
    const imgTwoInput = document.getElementById('card-request-img-two');

    if (!type || !amount || !creditScore) {
        alertBox.textContent = 'Please fill in card type, amount, and credit score.';
        alertBox.style.display = 'block';
        return;
    }

    const formData = new FormData();
    formData.append('type', type);
    formData.append('amount', amount);
    formData.append('credit_score', creditScore);
    if (imgOneInput.files.length) formData.append('img_one', imgOneInput.files[0]);
    if (imgTwoInput.files.length) formData.append('img_two', imgTwoInput.files[0]);

    submitBtn.disabled = true;
    submitBtn.innerText = 'Submitting...';

    try {
        const response = await fetch("{{ url('api/v1/card-requests') }}", {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: formData
        });

        const result = await response.json();

        if (!result.success) {
            alertBox.textContent = result.message || 'Unable to submit this request right now.';
            alertBox.style.display = 'block';
            return;
        }

        successBox.textContent = 'Card request submitted.';
        successBox.style.display = 'block';
        document.getElementById('card-request-form').reset();
        fetchCardRequests();
    } catch (error) {
        console.error('Card request submission failure:', error);
        alertBox.textContent = 'Something went wrong. Please try again.';
        alertBox.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerText = 'Submit Request';
    }
}
</script>