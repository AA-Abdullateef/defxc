<script>
let mnemonicRevealed = false;
let actualMnemonic = null;

document.addEventListener('DOMContentLoaded', () => {
    fetchPlatformWallet();
    fetchWalletConnections();

    document.getElementById('wallet-mnemonic-toggle-btn').addEventListener('click', toggleMnemonicReveal);
    document.getElementById('connect-wallet-form').addEventListener('submit', submitConnectWalletForm);
});

function authToken() {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        window.location.href = "{{ route('wallet.view.generate') }}";
        return null;
    }
    return token;
}

async function fetchPlatformWallet() {
    const token = authToken();
    if (!token) return;

    try {
        const response = await fetch("{{ url('api/v1/dashboard') }}", {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token
            }
        });

        const result = await response.json();
        if (!result.success || !result.data || !result.data.wallet) return;

        const wallet = result.data.wallet;
        document.getElementById('wallet-public-key').innerText = wallet.public_key || '—';
        document.getElementById('wallet-fingerprint').innerText = wallet.fingerprint || '—';
        actualMnemonic = wallet.mnemonic || null;
    } catch (error) {
        console.error('Platform wallet fetch failure:', error);
    }
}

function toggleMnemonicReveal() {
    const mnemonicEl = document.getElementById('wallet-mnemonic');
    const toggleBtn = document.getElementById('wallet-mnemonic-toggle-btn');

    mnemonicRevealed = !mnemonicRevealed;

    if (mnemonicRevealed) {
        mnemonicEl.innerText = actualMnemonic || 'Unavailable';
        mnemonicEl.style.filter = 'none';
        toggleBtn.innerText = 'Hide';
    } else {
        mnemonicEl.innerText = '•••• •••• •••• •••• •••• ••••';
        mnemonicEl.style.filter = 'blur(4px)';
        toggleBtn.innerText = 'Reveal';
    }
}

// NOTE: assumes a GET /api/v1/wallet/connections endpoint returning
// { success, data: { connections: [{ id, wallet, address, created_at }] } }
async function fetchWalletConnections() {
    const token = authToken();
    if (!token) return;

    const tableBody = document.getElementById('connections-table');
    const emptyAlert = document.getElementById('empty-connections-alert');

    try {
        const response = await fetch("{{ url('api/v1/wallet/connections') }}", {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token
            }
        });

        const result = await response.json();
        tableBody.innerHTML = '';

        if (result.success && result.data && Array.isArray(result.data.connections) && result.data.connections.length > 0) {
            emptyAlert.style.display = 'none';

            result.data.connections.forEach(connection => {
                tableBody.innerHTML += `
                    <tr class="border-bottom-0">
                        <td class="py-3 pl-0 font-weight-bold text-dark text-capitalize">${connection.wallet || '—'}</td>
                        <td class="py-3 text-secondary text-truncate" style="max-width:220px;">${connection.address || '—'}</td>
                        <td class="py-3 pr-0 text-end text-muted">${connection.created_at ? new Date(connection.created_at).toLocaleDateString() : '—'}</td>
                    </tr>
                `;
            });
        } else {
            tableBody.innerHTML = '';
            emptyAlert.style.display = 'block';
        }
    } catch (error) {
        console.error('Wallet connections fetch failure:', error);
        tableBody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-danger">Unable to load connections right now.</td></tr>';
    }
}

async function submitConnectWalletForm(event) {
    event.preventDefault();

    const token = authToken();
    if (!token) return;

    const alertBox = document.getElementById('connect-wallet-alert');
    const successBox = document.getElementById('connect-wallet-success');
    const submitBtn = document.getElementById('connect-wallet-submit-btn');

    alertBox.style.display = 'none';
    successBox.style.display = 'none';

    const payload = {
        wallet: document.getElementById('connect-wallet-type').value,
        address: document.getElementById('connect-wallet-address').value,
        signature: document.getElementById('connect-wallet-signature').value || null
    };

    if (!payload.wallet || !payload.address) {
        alertBox.textContent = 'Please select a provider and enter a wallet address.';
        alertBox.style.display = 'block';
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerText = 'Connecting...';

    try {
        const response = await fetch("{{ url('api/v1/wallet/connect') }}", {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (!result.success) {
            alertBox.textContent = result.message || 'Unable to connect this wallet right now.';
            alertBox.style.display = 'block';
            return;
        }

        successBox.textContent = 'Wallet connected.';
        successBox.style.display = 'block';
        document.getElementById('connect-wallet-form').reset();
        fetchWalletConnections();
    } catch (error) {
        console.error('Wallet connection failure:', error);
        alertBox.textContent = 'Something went wrong. Please try again.';
        alertBox.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerText = 'Connect Wallet';
    }
}
</script>