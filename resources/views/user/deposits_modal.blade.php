<script>
let depositMethodsData = [];
let selectedSubMethod = null;
let activeDepositTransactionId = null;

document.addEventListener('DOMContentLoaded', () => {
    fetchDepositOptions();

    document.getElementById('deposit-method').addEventListener('change', populateSubMethodSelect);
    document.getElementById('deposit-form').addEventListener('submit', submitDepositForm);
    document.getElementById('deposit-proof-form').addEventListener('submit', submitDepositProof);
    document.getElementById('back-to-deposit-start-btn').addEventListener('click', showDepositStartView);
});

function authToken() {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        window.location.href = "{{ route('wallet.view.generate') }}";
        return null;
    }
    return token;
}

function showDepositStartView() {
    document.getElementById('deposit-instructions-view').style.display = 'none';
    document.getElementById('deposit-start-view').style.display = 'block';
    fetchDepositOptions();
}

function showDepositInstructionsView() {
    document.getElementById('deposit-start-view').style.display = 'none';
    document.getElementById('deposit-instructions-view').style.display = 'block';
}

async function fetchDepositOptions() {
    const token = authToken();
    if (!token) return;

    try {
        const response = await fetch("{{ url('api/v1/deposits/options') }}", {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token
            }
        });

        const result = await response.json();
        if (!result.success || !result.data) return;

        // Assets dropdown
        const assetSelect = document.getElementById('deposit-asset');
        assetSelect.innerHTML = '<option value="">Select an asset</option>';
        (result.data.assets || []).forEach(asset => {
            assetSelect.innerHTML += `<option value="${asset.id}">${asset.label || asset.name}</option>`;
        });

        // Methods dropdown
        depositMethodsData = result.data.methods || [];
        const methodSelect = document.getElementById('deposit-method');
        methodSelect.innerHTML = '<option value="">Select a payment method</option>';
        depositMethodsData.forEach(method => {
            methodSelect.innerHTML += `<option value="${method.id}">${method.name}</option>`;
        });

        // Pending deposits
        renderPendingDeposits(result.data.pending || []);
    } catch (error) {
        console.error('Deposit options fetch failure:', error);
    }
}

function renderPendingDeposits(pending) {
    const wrapper = document.getElementById('pending-deposits-wrapper');
    const tableBody = document.getElementById('pending-deposits-table');

    if (!pending.length) {
        wrapper.style.display = 'none';
        return;
    }

    wrapper.style.display = 'block';
    tableBody.innerHTML = '';

    pending.forEach(tx => {
        tableBody.innerHTML += `
            <tr class="border-bottom-0">
                <td class="py-3 pl-0 font-weight-bold text-dark">${tx.asset?.label || tx.asset?.name || '—'}</td>
                <td class="py-3 text-secondary">${tx.amount}</td>
                <td class="py-3"><span class="badge badge-warning text-capitalize px-2 py-1">${tx.status_label || tx.status}</span></td>
                <td class="py-3 pr-0 text-end">
                    <button type="button" class="btn btn-outline-dark btn-xs py-0 px-2 font-weight-bold" style="font-size:11px; height:22px;" onclick="resumeDeposit('${tx.id}')">
                        Continue
                    </button>
                </td>
            </tr>
        `;
    });
}

function populateSubMethodSelect() {
    const methodId = document.getElementById('deposit-method').value;
    const subMethodSelect = document.getElementById('deposit-sub-method');

    subMethodSelect.innerHTML = '';
    subMethodSelect.disabled = true;

    const method = depositMethodsData.find(m => m.id === methodId);
    const subMethods = (method?.sub_methods || []).filter(sm => sm.is_active);

    if (!subMethods.length) {
        subMethodSelect.innerHTML = '<option value="">No active channels for this method</option>';
        return;
    }

    subMethodSelect.innerHTML = '<option value="">Select a channel</option>';
    subMethods.forEach(sm => {
        subMethodSelect.innerHTML += `<option value="${sm.id}">${sm.name}</option>`;
    });
    subMethodSelect.disabled = false;
}

async function submitDepositForm(event) {
    event.preventDefault();

    const token = authToken();
    if (!token) return;

    const assetId = document.getElementById('deposit-asset').value;
    const methodId = document.getElementById('deposit-method').value;
    const subMethodId = document.getElementById('deposit-sub-method').value;
    const amount = document.getElementById('deposit-amount').value;
    const alertBox = document.getElementById('deposit-form-alert');
    const submitBtn = document.getElementById('deposit-submit-btn');

    alertBox.style.display = 'none';

    if (!assetId || !subMethodId || !amount) {
        alertBox.textContent = 'Please fill in every field before continuing.';
        alertBox.style.display = 'block';
        return;
    }

    const method = depositMethodsData.find(m => m.id === methodId);
    selectedSubMethod = (method?.sub_methods || []).find(sm => sm.id === subMethodId) || null;

    submitBtn.disabled = true;
    submitBtn.innerText = 'Submitting...';

    try {
        const response = await fetch("{{ url('api/v1/deposits') }}", {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({
                asset_id: assetId,
                sub_method_id: subMethodId,
                amount: amount
            })
        });

        const result = await response.json();

        if (!result.success) {
            alertBox.textContent = result.message || 'Unable to start this deposit right now.';
            alertBox.style.display = 'block';
            return;
        }

        activeDepositTransactionId = result.data.transaction.id;
        renderDepositInstructions(amount, selectedSubMethod);
        showDepositInstructionsView();
    } catch (error) {
        console.error('Deposit submission failure:', error);
        alertBox.textContent = 'Something went wrong. Please try again.';
        alertBox.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerText = 'Continue';
    }
}

async function resumeDeposit(transactionId) {
    const token = authToken();
    if (!token) return;

    try {
        const response = await fetch(`{{ url('api/v1/deposits') }}/${transactionId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token
            }
        });

        const result = await response.json();
        if (!result.success) return;

        const tx = result.data.transaction;
        activeDepositTransactionId = tx.id;
        renderDepositInstructions(tx.amount, tx.sub_method);
        showDepositInstructionsView();
    } catch (error) {
        console.error('Resume deposit fetch failure:', error);
    }
}

function renderDepositInstructions(amount, subMethod) {
    document.getElementById('deposit-instructions-amount').innerText = amount;

    const fieldsBox = document.getElementById('deposit-instructions-fields');
    const notesBox = document.getElementById('deposit-instructions-notes');
    fieldsBox.innerHTML = '';
    notesBox.style.display = 'none';

    if (!subMethod) {
        fieldsBox.innerHTML = '<p class="text-muted">Payment details unavailable.</p>';
        return;
    }

    const rows = [
        ['Channel', subMethod.name],
        ['Account Name', subMethod.account_name],
        ['Account Number', subMethod.account_number],
        ['Bank Name', subMethod.bank_name],
        ['Routing Number', subMethod.routing_number],
        ['SWIFT Code', subMethod.swift_code],
        ['IBAN', subMethod.iban],
        ['Wallet Address', subMethod.wallet_address],
        ['Network', subMethod.network],
    ].filter(([, value]) => !!value);

    rows.forEach(([label, value]) => {
        fieldsBox.innerHTML += `
            <div class="d-flex justify-content-between border-bottom py-2">
                <span class="text-muted">${label}</span>
                <span class="font-weight-bold text-dark">${value}</span>
            </div>
        `;
    });

    if (subMethod.instructions) {
        notesBox.innerText = subMethod.instructions;
        notesBox.style.display = 'block';
    }

    // Reset the proof form/messages each time instructions render so stale state from a
    // previous deposit doesn't carry over.
    document.getElementById('deposit-proof-form').reset();
    document.getElementById('deposit-proof-alert').style.display = 'none';
    document.getElementById('deposit-proof-success').style.display = 'none';
}

async function submitDepositProof(event) {
    event.preventDefault();

    const token = authToken();
    if (!token) return;

    if (!activeDepositTransactionId) {
        return;
    }

    const fileInput = document.getElementById('deposit-photo');
    const alertBox = document.getElementById('deposit-proof-alert');
    const successBox = document.getElementById('deposit-proof-success');
    const submitBtn = document.getElementById('deposit-proof-submit-btn');

    alertBox.style.display = 'none';
    successBox.style.display = 'none';

    if (!fileInput.files.length) {
        alertBox.textContent = 'Please choose a payment screenshot to upload.';
        alertBox.style.display = 'block';
        return;
    }

    const formData = new FormData();
    formData.append('deposit_photo', fileInput.files[0]);

    submitBtn.disabled = true;
    submitBtn.innerText = 'Uploading...';

    try {
        const response = await fetch(`{{ url('api/v1/deposits') }}/${activeDepositTransactionId}/proof`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: formData
        });

        const result = await response.json();

        if (!result.success) {
            alertBox.textContent = result.message || 'Unable to upload proof right now.';
            alertBox.style.display = 'block';
            return;
        }

        successBox.textContent = result.message || 'Proof uploaded. Your deposit is under review.';
        successBox.style.display = 'block';
        fileInput.value = '';
    } catch (error) {
        console.error('Deposit proof upload failure:', error);
        alertBox.textContent = 'Something went wrong. Please try again.';
        alertBox.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerText = 'Submit Proof';
    }
}
</script>