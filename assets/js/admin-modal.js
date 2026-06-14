/**
 * assets/js/admin-modal.js
 * Modal ubah status pesanan — vanilla JS, tidak ada inline onclick.
 *
 * Cara kerja:
 *  1. Inject modal HTML ke <body>.
 *  2. Event delegation untuk tombol .btn-ubah-status.
 *  3. fetch() POST ke /admin/ajax/update-status-pesanan.php dengan CSRF token.
 *  4. Pada sukses: update badge & data-* di baris tabel, tutup modal.
 *  5. Tutup modal: tombol Batal, klik overlay, tombol Escape.
 *
 * Requirements: 8.3, 8.7, 16.9, 17.5
 */

(function () {
    'use strict';

    /* ── Badge helpers ──────────────────────────────────────────────────────── */

    /** @type {Record<string, string>} */
    var BADGE_CLASS = {
        menunggu_konfirmasi: 'badge-menunggu',
        diproses:            'badge-diproses',
        selesai:             'badge-selesai',
        dibatalkan:          'badge-dibatalkan',
    };

    /** @type {Record<string, string>} */
    var BADGE_LABEL = {
        menunggu_konfirmasi: 'Menunggu Konfirmasi',
        diproses:            'Diproses',
        selesai:             'Selesai',
        dibatalkan:          'Dibatalkan',
    };

    /* ── State ──────────────────────────────────────────────────────────────── */
    var currentIdPesanan  = null;
    var currentTriggerBtn = null;

    /* ── DOM refs (dipopulasi setelah inject) ───────────────────────────────── */
    var overlay, modal, selectStatus, errorBox, btnConfirm, btnCancel;

    /* ── Inject modal HTML ──────────────────────────────────────────────────── */
    function injectModal() {
        var wrapper = document.createElement('div');
        wrapper.innerHTML = [
            '<div id="wf-modal-overlay"',
            '     role="presentation"',
            '     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);',
            '            z-index:999;align-items:center;justify-content:center;',
            '            padding:1rem;backdrop-filter:blur(2px);">',
            '  <div id="wf-modal"',
            '       role="dialog"',
            '       aria-modal="true"',
            '       aria-labelledby="wf-modal-title"',
            '       style="background:#fff;border-radius:16px;padding:1.5rem;width:100%;',
            '              max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.2);',
            '              animation:wfModalIn .2s ease-out;font-family:\'Inter\',sans-serif;">',
            '    <h3 id="wf-modal-title"',
            '        style="font-family:\'Playfair Display\',serif;font-size:1.25rem;',
            '               font-weight:700;color:#1F2937;margin:0 0 1rem;">',
            '      ✏ Ubah Status Pesanan',
            '    </h3>',
            '    <p style="font-size:.875rem;color:#6B7280;margin:0 0 .75rem;">',
            '      Pilih status baru untuk pesanan ini:',
            '    </p>',
            '    <select id="wf-modal-status"',
            '            style="width:100%;padding:.5rem .875rem;border:1.5px solid #E5E7EB;',
            '                   border-radius:8px;font-family:\'Inter\',sans-serif;font-size:.9rem;',
            '                   color:#1F2937;background:#F9FAFB;outline:none;cursor:pointer;',
            '                   margin-bottom:.75rem;">',
            '      <option value="menunggu_konfirmasi">Menunggu Konfirmasi</option>',
            '      <option value="diproses">Diproses</option>',
            '      <option value="selesai">Selesai</option>',
            '      <option value="dibatalkan">Dibatalkan</option>',
            '    </select>',
            '    <div id="wf-modal-error"',
            '         role="alert"',
            '         style="color:#DC2626;font-size:.8rem;margin-bottom:.75rem;display:none;">',
            '    </div>',
            '    <div style="display:flex;gap:.625rem;justify-content:flex-end;margin-top:1rem;">',
            '      <button id="wf-modal-cancel"',
            '              type="button"',
            '              style="padding:.5rem 1.25rem;border:1.5px solid #E5E7EB;border-radius:9999px;',
            '                     background:#fff;color:#374151;font-family:\'Inter\',sans-serif;',
            '                     font-size:.875rem;font-weight:500;cursor:pointer;transition:background .15s;">',
            '        Batal',
            '      </button>',
            '      <button id="wf-modal-confirm"',
            '              type="button"',
            '              style="padding:.5rem 1.25rem;border:none;border-radius:9999px;',
            '                     background:#6B21A8;color:#fff;font-family:\'Inter\',sans-serif;',
            '                     font-size:.875rem;font-weight:600;cursor:pointer;transition:background .15s;">',
            '        Konfirmasi',
            '      </button>',
            '    </div>',
            '  </div>',
            '</div>',
            '<style>',
            '@keyframes wfModalIn{from{opacity:0;transform:scale(.95) translateY(8px)}to{opacity:1;transform:scale(1) translateY(0)}}',
            '#wf-modal-cancel:hover{background:#F3F4F6;}',
            '#wf-modal-confirm:hover{background:#5B1A90;}',
            '#wf-modal-confirm:disabled{opacity:.6;cursor:not-allowed;}',
            '</style>',
        ].join('\n');

        document.body.appendChild(wrapper.firstElementChild); // overlay div
        // Append the <style> tag separately
        var styleEl = wrapper.querySelector('style');
        if (styleEl) document.head.appendChild(styleEl);
    }

    /* ── Modal open / close ─────────────────────────────────────────────────── */
    function openModal(idPesanan, statusSaatIni, triggerBtn) {
        currentIdPesanan  = idPesanan;
        currentTriggerBtn = triggerBtn;

        // Preset select ke status saat ini
        selectStatus.value = statusSaatIni;
        hideError();

        overlay.style.display = 'flex';
        // Fokus ke select agar aksesibel
        setTimeout(function () { selectStatus.focus(); }, 50);
    }

    function closeModal() {
        overlay.style.display = 'none';
        currentIdPesanan  = null;
        if (currentTriggerBtn) {
            currentTriggerBtn.focus();
            currentTriggerBtn = null;
        }
    }

    /* ── Error helpers ──────────────────────────────────────────────────────── */
    function showError(msg) {
        errorBox.textContent = msg;
        errorBox.style.display = 'block';
    }

    function hideError() {
        errorBox.textContent = '';
        errorBox.style.display = 'none';
    }

    /* ── Update badge di tabel ──────────────────────────────────────────────── */
    function updateRowBadge(triggerBtn, statusBaru) {
        // Naik dari button → <td> → <tr>
        var td  = triggerBtn.closest('td');
        var row = triggerBtn.closest('tr');
        if (!row) return;

        // Cari <span class="badge ..."> di kolom status (td ke-5, index 4)
        var cells = row.querySelectorAll('td');
        // Status badge ada di td indeks 4 (0-based: No, Nama, Total, Metode, Status, Tanggal, Aksi)
        var statusCell = cells[4];
        if (statusCell) {
            var badge = statusCell.querySelector('.badge');
            if (badge) {
                // Hapus semua kelas badge-*
                Object.values(BADGE_CLASS).forEach(function (cls) {
                    badge.classList.remove(cls);
                });
                badge.classList.add(BADGE_CLASS[statusBaru] || 'badge-menunggu');
                badge.textContent = BADGE_LABEL[statusBaru] || statusBaru;
            }
        }

        // Update data-* pada tombol agar pembukaan modal berikutnya akurat
        triggerBtn.dataset.statusSaatIni = statusBaru;
    }

    /* ── Kirim perubahan status ke server ───────────────────────────────────── */
    function submitStatusChange() {
        var csrfInput = document.getElementById('csrf_token_modal');
        var csrfToken = csrfInput ? csrfInput.value : '';
        var statusBaru = selectStatus.value;

        if (!statusBaru) {
            showError('Pilih status terlebih dahulu.');
            return;
        }

        hideError();
        btnConfirm.disabled = true;
        btnConfirm.textContent = 'Menyimpan…';

        fetch('/admin/ajax/update-status-pesanan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token:  csrfToken,
                id_pesanan:  parseInt(currentIdPesanan, 10),
                status_baru: statusBaru,
            }),
        })
        .then(function (res) {
            return res.json();
        })
        .then(function (data) {
            if (data.success) {
                // Update badge & data-* di baris tabel
                if (currentTriggerBtn) {
                    updateRowBadge(currentTriggerBtn, data.status_baru);
                }
                closeModal();
            } else {
                showError(data.message || 'Terjadi kesalahan. Coba lagi.');
            }
        })
        .catch(function () {
            showError('Gagal menghubungi server. Periksa koneksi Anda.');
        })
        .finally(function () {
            btnConfirm.disabled = false;
            btnConfirm.textContent = 'Konfirmasi';
        });
    }

    /* ── Inisialisasi setelah DOM siap ──────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {

        // 1. Inject modal HTML
        injectModal();

        // 2. Simpan referensi elemen modal
        overlay       = document.getElementById('wf-modal-overlay');
        modal         = document.getElementById('wf-modal');
        selectStatus  = document.getElementById('wf-modal-status');
        errorBox      = document.getElementById('wf-modal-error');
        btnConfirm    = document.getElementById('wf-modal-confirm');
        btnCancel     = document.getElementById('wf-modal-cancel');

        // 3. Event delegation — klik tombol .btn-ubah-status
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-ubah-status');
            if (btn) {
                var idPesanan    = btn.dataset.idPesanan    || btn.getAttribute('data-id-pesanan');
                var statusSaatIni = btn.dataset.statusSaatIni || btn.getAttribute('data-status-saat-ini');
                openModal(idPesanan, statusSaatIni, btn);
            }
        });

        // 4. Tombol Konfirmasi
        btnConfirm.addEventListener('click', function () {
            submitStatusChange();
        });

        // 5. Tombol Batal
        btnCancel.addEventListener('click', function () {
            closeModal();
        });

        // 6. Klik overlay (di luar kotak modal) → tutup
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                closeModal();
            }
        });

        // 7. Tombol Escape → tutup
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay && overlay.style.display !== 'none') {
                closeModal();
            }
        });

    });

}());
