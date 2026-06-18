document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('ownerToggle');
    if (!toggle) return;

    toggle.addEventListener('change', async function () {
        var statusText = document.getElementById('ownerStatusText');
        var csrfInput  = document.getElementById('csrf_token_ajax');

        if (!csrfInput) return;

        try {
            var response = await fetch('/admin/ajax/toggle-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: csrfInput.value })
            });

            var data = await response.json();

            if (data.success) {
                if (statusText) {
                    statusText.textContent = data.status_baru === 'aktif' ? 'Aktif' : 'Nonaktif';
                }
            } else {
                this.checked = !this.checked;
                alert('Gagal mengubah status: ' + (data.message || 'Terjadi kesalahan.'));
            }
        } catch (err) {\
            this.checked = !this.checked;
        }
    });
});
