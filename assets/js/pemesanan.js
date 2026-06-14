/**
 * assets/js/pemesanan.js
 *
 * Client-side logic for pages/pemesanan.php:
 *   - Dynamic product rows (add / remove)
 *   - Real-time order summary sidebar
 *   - DP info box visibility
 *   - Client-side form validation
 *
 * No inline event handlers (addEventListener only).
 * Requirements: 5.1–5.10, 17.4, 17.5
 */

/* global document, window */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var form = document.getElementById('form-pemesanan');
    if (!form) return;

    // ─── Add product row ──────────────────────────────────────────────────────
    var btnTambah = document.getElementById('btn-tambah-produk');
    if (btnTambah) {
        btnTambah.addEventListener('click', function () {
            addProductRow();
        });
    }

    // ─── Remove product row (delegated) ──────────────────────────────────────
    form.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-hapus-produk')) {
            var row = e.target.closest('.produk-row');
            if (row && document.querySelectorAll('.produk-row').length > 1) {
                row.remove();
                updateSummary();
            }
        }
    });

    // ─── Live summary updates (product select / qty change) ──────────────────
    form.addEventListener('change', function (e) {
        if (
            e.target.matches('select[name="produk_id[]"]') ||
            e.target.matches('input[name="jumlah[]"]') ||
            e.target.matches('input[name="metode_bayar"]')
        ) {
            updateSummary();
        }
    });

    form.addEventListener('input', function (e) {
        if (e.target.matches('input[name="jumlah[]"]')) {
            updateSummary();
        }
    });

    // ─── Form validation on submit ────────────────────────────────────────────
    form.addEventListener('submit', function (e) {
        clearErrors();
        var isValid = true;

        // Validate nama pemesan
        var nama = form.querySelector('[name="nama_pemesan"]');
        if (!nama || nama.value.trim() === '') {
            showError(nama, 'Nama pemesan tidak boleh kosong.');
            isValid = false;
        }

        // Validate nomor WhatsApp (8–15 digits)
        var wa = form.querySelector('[name="no_whatsapp"]');
        if (!wa || !/^\d{8,15}$/.test(wa.value.trim())) {
            showError(wa, 'Nomor WhatsApp harus berupa 8–15 digit angka.');
            isValid = false;
        }

        // Validate alamat
        var alamat = form.querySelector('[name="alamat"]');
        if (!alamat || alamat.value.trim() === '') {
            showError(alamat, 'Alamat tidak boleh kosong.');
            isValid = false;
        }

        // Validate tanggal kirim (not in the past)
        var tgl = form.querySelector('[name="tanggal_kirim"]');
        if (tgl) {
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            var selected = new Date(tgl.value);
            if (!tgl.value || selected < today) {
                showError(tgl, 'Tanggal pengiriman tidak boleh di masa lalu.');
                isValid = false;
            }
        }

        // Validate at least one product with quantity >= 1
        var produkRows = form.querySelectorAll('.produk-row');
        var hasProduct = false;
        produkRows.forEach(function (row) {
            var select = row.querySelector('select[name="produk_id[]"]');
            var qty    = row.querySelector('input[name="jumlah[]"]');
            if (select && select.value !== '' && qty && parseInt(qty.value, 10) >= 1) {
                hasProduct = true;
            }
        });
        if (!hasProduct) {
            var produkSection = document.getElementById('produk-section');
            if (produkSection) {
                showError(produkSection, 'Minimal satu produk harus dipilih dengan jumlah minimal 1.');
            }
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            var firstError = form.querySelector('.field-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    // ─── Initial summary render ───────────────────────────────────────────────
    updateSummary();

    // ═══════════════════════════════════════════════════════════════════════════
    // HELPER FUNCTIONS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Clone the template and append a new product row to #produk-rows.
     */
    function addProductRow() {
        var template = document.getElementById('produk-row-template');
        if (!template) return;
        var newRow = template.content.cloneNode(true);
        document.getElementById('produk-rows').appendChild(newRow);
        // Focus the new select for accessibility
        var rows    = document.querySelectorAll('.produk-row');
        var lastRow = rows[rows.length - 1];
        if (lastRow) {
            var newSelect = lastRow.querySelector('select');
            if (newSelect) newSelect.focus();
        }
    }

    /**
     * Attach inline error span below a field.
     * Also marks the field with .input-error.
     *
     * @param {Element} field  - The form control to mark.
     * @param {string}  message - Error text.
     */
    function showError(field, message) {
        if (!field) return;
        field.classList.add('input-error');
        var err       = document.createElement('span');
        err.className = 'field-error form-error';
        err.setAttribute('role', 'alert');
        err.textContent = message;
        field.parentNode.insertBefore(err, field.nextSibling);
    }

    /**
     * Remove all previously injected client-side errors.
     */
    function clearErrors() {
        form.querySelectorAll('.field-error').forEach(function (el) { el.remove(); });
        form.querySelectorAll('.input-error').forEach(function (el) { el.classList.remove('input-error'); });
    }

    /**
     * Rebuild the right-column order summary from current row values.
     * Also controls visibility of the DP info box.
     */
    function updateSummary() {
        var summaryList  = document.getElementById('pem-summary-list');
        var summaryTotal = document.getElementById('pem-summary-total');
        var dpInfo       = document.getElementById('pem-dp-info');

        if (!summaryList || !summaryTotal) return;

        var rows  = document.querySelectorAll('.produk-row');
        var total = 0;
        var lines = [];

        rows.forEach(function (row) {
            var select = row.querySelector('select[name="produk_id[]"]');
            var qty    = row.querySelector('input[name="jumlah[]"]');

            if (!select || !qty) return;

            var selectedOption = select.options[select.selectedIndex];
            if (!selectedOption || select.value === '') return;

            var harga   = parseInt(selectedOption.getAttribute('data-harga') || '0', 10);
            var jumlah  = Math.max(1, parseInt(qty.value, 10) || 1);
            var subtotal = harga * jumlah;

            total += subtotal;
            lines.push({
                nama:     selectedOption.text.split(' — ')[0].trim(),
                jumlah:   jumlah,
                subtotal: subtotal,
            });
        });

        // Render summary lines
        if (lines.length === 0) {
            summaryList.innerHTML = '<p class="pem-summary-empty text-muted text-sm">Pilih produk untuk melihat ringkasan.</p>';
        } else {
            var html = '';
            lines.forEach(function (line) {
                html +=
                    '<div class="pem-summary-line">' +
                        '<span class="pem-summary-line-name">' + escapeHtml(line.nama) + ' <span class="pem-summary-line-qty">×' + line.jumlah + '</span></span>' +
                        '<span class="pem-summary-line-price">' + formatRupiah(line.subtotal) + '</span>' +
                    '</div>';
            });
            summaryList.innerHTML = html;
        }

        // Update total
        summaryTotal.textContent = formatRupiah(total);

        // DP info box: show when total > 100000 AND metode = transfer
        if (dpInfo) {
            var metodeInput = form.querySelector('input[name="metode_bayar"]:checked');
            var metode      = metodeInput ? metodeInput.value : 'transfer';
            if (total > 100000 && metode === 'transfer') {
                dpInfo.style.display = '';
            } else {
                dpInfo.style.display = 'none';
            }
        }
    }

    /**
     * Format an integer as Rupiah string: "Rp 350.000"
     * Mirrors the PHP format_rupiah() helper.
     *
     * @param {number} angka
     * @returns {string}
     */
    function formatRupiah(angka) {
        if (angka === 0) return 'Rp 0';
        return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    /**
     * Basic HTML escape to prevent XSS in dynamically inserted content.
     *
     * @param {string} str
     * @returns {string}
     */
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
});
