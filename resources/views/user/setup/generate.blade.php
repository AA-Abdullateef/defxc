@extends('layouts.setup')

@section('title', 'Wallet Setup')

@section('content')
<div class="setup-card">
    <h2>Wallet Setup</h2>
    <p>Create a new wallet or import an existing one</p>

    <!-- Initial State -->
    <div id="step-init">
        <button id="generate-btn" class="btn-login">Create New Wallet</button>

        <div style="text-align: center; margin: 15px 0; font-size: 13px; font-weight: bold; color: var(--text-muted, #6b7280);">
            OR
        </div>

        <a href="{{ route('wallet.view.import') }}" class="btn-login" style="background-color: #6b7280; text-decoration: none; display: block;">
            I already have a wallet
        </a>
    </div>

    <!-- Mnemonic Display State -->
    <div id="step-display" style="display: none;">
        <div class="mnemonic-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 20px 0;">
            <!-- Appended via JavaScript -->
        </div>
        
        <div class="alert alert-warning" style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
            ⚠️ <strong>Critical Security Check:</strong> Write down these 12 words in order. They will never be shown again.
        </div>

        <!-- Action Row Container Layout Matrix -->
        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <!-- 💡 NEW: Back Action Navigation Button -->
            <button id="back-btn" class="btn-login" style="background-color: #ef4444; flex: 1;">✕ Go Back</button>
            <button id="next-btn" class="btn-login" style="flex: 2;">I Have Safely Written Them Down</button>
        </div>
    </div>
</div>

<script>
document.getElementById('generate-btn').addEventListener('click', async () => {
    try {
        const response = await fetch("{{ url('api/v1/wallet/generate') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const result = await response.json();

        if (result.success && result.data) {
            sessionStorage.setItem('wallet_setup_token', result.data.setup_token);

            const grid = document.querySelector('.mnemonic-grid');
            if (!grid) {
                throw new Error("HTML Element with class '.mnemonic-grid' was not found.");
            }
            grid.innerHTML = '';
            
            const wordsArray = result.data.mnemonic.trim().split(' ');

            wordsArray.forEach((word, index) => {
                grid.innerHTML += `<div style="padding:10px; background:#f4f4f4; border-radius:4px;"><strong>${index + 1}.</strong> ${word}</div>`;
            });

            document.getElementById('step-init').style.display = 'none';
            document.getElementById('step-display').style.display = 'block';
        } else {
            alert('Failed to parse wallet context: ' + (result.message || 'Unknown response structure.'));
        }
    } catch (error) {
        console.error('System exception caught:', error);
        alert('JavaScript Error details printed to developer console: ' + error.message);
    }
});

// 💡 NEW: Click handler to revert panels back into the initial choice selection menu state
document.getElementById('back-btn').addEventListener('click', () => {
    // Prevent old tokens from lingering across accidental back-and-forth loops
    sessionStorage.removeItem('wallet_setup_token'); 
    
    // Toggle element states cleanly
    document.getElementById('step-display').style.display = 'none';
    document.getElementById('step-init').style.display = 'block';
});

document.getElementById('next-btn').addEventListener('click', () => {
    window.location.href = "{{ route('wallet.view.challenge') }}";
});
</script>
@endsection
