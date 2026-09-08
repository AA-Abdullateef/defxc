<script>
let buyAssetsData = [];
let buyMethodsData = [];

document.addEventListener('DOMContentLoaded', () => {
    fetchBuyOptions();
    document.getElementById('buy-asset').addEventListener('change', updateBuyPreview);
    document.getElementById('buy-fiat-amount').addEventListener('input', updateBuyPreview);
    document.getElementById('buy-method').addEventListener('change', populateBuySubMethod);
    document.getElementById('buy-form').addEventListener('submit', submitBuy);
    document.getElementById('buy-proof-form').addEventListener('submit', submitBuyProof);
});

function authToken() {
    const token = localStorage.getItem('auth_token');
    if (!token) { window.location.href = "{{ route('wallet.view.generate') }}"; return null; }
    return token;
}

async function fetchBuyOptions() {
    const token = authToken(); if (!token) return;

    try {
        const res = await fetch("{{ url('api/v1/buy/options') }}", {
            headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + token }
        });
        const result = await res.json();
        if (!result.success || !result.data) return;

        buyAssetsData  = result.data.assets  || [];
        buyMethodsData = result.data.methods  || [];

        const assetSel = document.getElementById('buy-asset');
        assetSel.innerHTML = '<option value="">Select an asset</option>';
        buyAssetsData.forEach(asset => {
            assetSel.innerHTML += `<option value="${asset.id}">${asset.label} (${asset.name.toUpperCase()}) — $${Number(asset.price_usd).toLocaleString()}</option>`;
        });

        const methodSel = document.getElementById('buy-method');
        methodSel.innerHTML = '<option value="">Select a method</option>';
        buyMethodsData.forEach(m => {
            methodSel.innerHTML += `<option value="${m.id}">${m.name}</option>`;
        });

        renderBuyTable('buy-pending-wrapper', 'buy-pending-table', result.data.pending || []);
        renderBuyTable('buy-history-wrapper', 'buy-history-table', result.data.history || []);
        populateBuyProofSelect(result.data.pending || []);
    } catch (e) { console.error('Buy options error:', e); }
}

function updateBuyPreview() {
    const assetId   = document.getElementById('buy-asset').value;
    const fiatAmount = parseFloat(document.getElementById('buy-fiat-amount').value) || 0;
    const preview   = document.getElementById('buy-preview');

    if (!assetId || fiatAmount <= 0) { preview.style.display = 'none'; return; }

    const asset     = buyAssetsData.find(a => a.id === assetId);
    if (!asset || !asset.price_usd) { preview.style.display = 'none'; return; }

    const priceUsd  = parseFloat(asset.price_usd);
    const chargeRate = parseFloat(asset.buy_charge_rate) || 0;
    const chargeFiat = fiatAmount * (chargeRate / 100);
    const netFiat    = fiatAmount - chargeFiat;
    const receive    = netFiat / priceUsd;

    document.getElementById('buy-preview-price').textContent          = `$${priceUsd.toLocaleString()} / ${asset.name.toUpperCase()}`;
    document.getElementById('buy-preview-charge-pct').textContent     = chargeRate;
    document.getElementById('buy-preview-charge').textContent         = `$${chargeFiat.toFixed(2)}`;
    document.getElementById('buy-preview-receive').textContent        = `${receive.toFixed(8)} ${asset.name.toUpperCase()}`;
    preview.style.display = 'block';
}

function populateBuySubMethod() {
    const methodId = document.getElementById('buy-method').value;
    const subSel   = document.getElementById('buy-sub-method');
    subSel.innerHTML = ''; subSel.disabled = true;

    const method    = buyMethodsData.find(m => m.id === methodId);
    const subMethods = (method?.sub_methods || []).filter(sm => sm.is_active);

    if (!subMethods.length) {
        subSel.innerHTML = '<option value="">No channels available</option>'; return;
    }
    subSel.innerHTML = '<option value="">Select a channel</option>';
    subMethods.forEach(sm => { subSel.innerHTML += `<option value="${sm.id}">${sm.name}</option>`; });
    subSel.disabled = false;
}

async function submitBuy(e) {
    e.preventDefault();
    const token = authToken(); if (!token) return;

    const alertBox   = document.getElementById('buy-form-alert');
    const successBox = document.getElementById('buy-form-success');
    const submitBtn  = document.getElementById('buy-submit-btn');

    alertBox.style.display = 'none'; successBox.style.display = 'none';

    const payload = {
        asset_id:      document.getElementById('buy-asset').value,
        fiat_amount:   document.getElementById('buy-fiat-amount').value,
        sub_method_id: document.getElementById('buy-sub-method').value || null,
    };

    if (!payload.asset_id || !payload.fiat_amount) {
        alertBox.textContent = 'Please select an asset and enter the amount.';
        alertBox.style.display = 'block'; return;
    }

    submitBtn.disabled = true; submitBtn.textContent = 'Placing order...';

    try {
        const res = await fetch("{{ url('api/v1/buys') }}", {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (!result.success) {
            alertBox.textContent = result.message || 'Buy order failed.';
            alertBox.style.display = 'block'; return;
        }

        successBox.textContent = result.message || 'Buy order placed. Pending admin approval.';
        successBox.style.display = 'block';
        document.getElementById('buy-form').reset();
        document.getElementById('buy-preview').style.display = 'none';
        fetchBuyOptions();
    } catch (err) {
        alertBox.textContent = 'Something went wrong. Please try again.';
        alertBox.style.display = 'block';
    } finally {
        submitBtn.disabled = false; submitBtn.textContent = 'Place Buy Order';
    }
}

function renderBuyTable(wrapperId, tableId, rows) {
    const wrapper = document.getElementById(wrapperId);
    const tbody   = document.getElementById(tableId);

    if (!rows.length) { wrapper.style.display = 'none'; return; }

    wrapper.style.display = 'block'; tbody.innerHTML = '';

    rows.forEach(tx => {
        const meta = tx.meta || {};
        let badgeClass = 'badge-warning';
        if (tx.status === 'completed') badgeClass = 'badge-success';
        if (tx.status === 'cancelled') badgeClass = 'badge-danger';
        tbody.innerHTML += `
            <tr class="border-bottom-0">
                <td class="py-3 pl-0 font-weight-bold">${tx.asset?.label || '—'}</td>
                <td class="py-3 text-secondary">$${meta.fiat_amount ? parseFloat(meta.fiat_amount).toFixed(2) : '—'}</td>
                <td class="py-3">${parseFloat(tx.amount).toFixed(8)} ${tx.asset?.name?.toUpperCase() || ''}</td>
                <td class="py-3 pr-0 text-end"><span class="badge ${badgeClass} px-2 py-1">${tx.status_label || tx.status}</span></td>
            </tr>`;
    });
}

function populateBuyProofSelect(pendingRows) {
    const proofSection = document.getElementById('buy-proof-section');
    const txSelect     = document.getElementById('buy-proof-tx-select');

    txSelect.innerHTML = '<option value="">Select a pending buy order</option>';

    if (!pendingRows.length) {
        proofSection.style.display = 'none'; return;
    }

    proofSection.style.display = 'block';
    pendingRows.forEach(tx => {
        const meta  = tx.meta || {};
        const label = `${tx.asset?.label || 'Asset'} — $${meta.fiat_amount ? parseFloat(meta.fiat_amount).toFixed(2) : '?'}`;
        txSelect.innerHTML += `<option value="${tx.id}">${label}</option>`;
    });
}

async function submitBuyProof(e) {
    e.preventDefault();
    const token = authToken(); if (!token) return;

    const txId      = document.getElementById('buy-proof-tx-select').value;
    const fileInput = document.getElementById('buy-proof-file');
    const alertBox  = document.getElementById('buy-proof-alert');
    const successBox = document.getElementById('buy-proof-success');
    const submitBtn = document.getElementById('buy-proof-submit-btn');

    alertBox.style.display = 'none'; successBox.style.display = 'none';

    if (!txId) {
        alertBox.textContent = 'Please select a pending buy order.';
        alertBox.style.display = 'block'; return;
    }

    if (!fileInput.files.length) {
        alertBox.textContent = 'Please choose a screenshot to upload.';
        alertBox.style.display = 'block'; return;
    }

    const formData = new FormData();
    formData.append('buy_proof', fileInput.files[0]);

    submitBtn.disabled = true; submitBtn.textContent = 'Uploading...';

    try {
        const res = await fetch(`{{ url('api/v1/buys') }}/${txId}/proof`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
            body: formData
        });
        const result = await res.json();

        if (!result.success) {
            alertBox.textContent = result.message || 'Upload failed.';
            alertBox.style.display = 'block'; return;
        }

        successBox.textContent = result.message || 'Proof uploaded. Your buy order is under review.';
        successBox.style.display = 'block';
        fileInput.value = '';
    } catch (err) {
        alertBox.textContent = 'Something went wrong. Please try again.';
        alertBox.style.display = 'block';
    } finally {
        submitBtn.disabled = false; submitBtn.textContent = 'Submit Proof';
    }
}
</script>
