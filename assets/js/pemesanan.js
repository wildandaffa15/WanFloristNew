document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var form = document.getElementById('form-pemesanan');
    if (!form) return;

    var btnTambah = document.getElementById('btn-tambah-produk');
    if (btnTambah) {
        btnTambah.addEventListener('click', function () {
            addProductRow();
        });
    }

    form.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-hapus-produk')) {
            var row = e.target.closest('.produk-row');
            if (row && document.querySelectorAll('.produk-row').length > 1) {
                row.remove();
                updateSummary();
            }
        }
    });

    form.addEventListener('change', function (e) {
        if (
            e.target.matches('select[name="produk_id[]"]') ||
            e.target.matches('input[name="jumlah[]"]') ||
            e.target.matches('input[name="metode_pengambilan"]')
        ) {
            updateSummary();
        }
    });

    form.addEventListener('input', function (e) {
        if (e.target.matches('input[name="jumlah[]"]')) {
            updateSummary();
        }
    });

    form.addEventListener('submit', function (e) {
        clearErrors();
        var isValid = true;

        var nama = form.querySelector('[name="nama_pembeli"]');
        if (!nama || nama.value.trim() === '') {
            showError(nama, 'Nama pembeli tidak boleh kosong.');
            isValid = false;
        }

        var hp = form.querySelector('[name="no_hp"]');
        if (!hp || !/^\d{8,15}$/.test(hp.value.trim())) {
            showError(hp, 'Nomor HP harus berupa 8–15 digit angka.');
            isValid = false;
        }

        var tgl = form.querySelector('[name="tanggal_ambil"]');
        if (tgl) {
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            var selected = new Date(tgl.value);
            if (!tgl.value || selected < today) {
                showError(tgl, 'Tanggal pengambilan tidak boleh di masa lalu.');
                isValid = false;
            }
        }

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
            var firstError = form.querySelector('.field-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    updateSummary();


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

    function showError(field, message) {
        if (!field) return;
        field.classList.add('input-error');
        var err       = document.createElement('span');
        err.className = 'field-error form-error';
        err.setAttribute('role', 'alert');
        err.textContent = message;
        field.parentNode.insertBefore(err, field.nextSibling);
    }

    function clearErrors() {
        form.querySelectorAll('.field-error').forEach(function (el) { el.remove(); });
        form.querySelectorAll('.input-error').forEach(function (el) { el.classList.remove('input-error'); });
    }

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

        summaryTotal.textContent = formatRupiah(total);

        if (dpInfo) {
            var metodeInput = form.querySelector('input[name="metode_pengambilan"]:checked');
            var metode      = metodeInput ? metodeInput.value : 'ambil_sendiri';
            dpInfo.classList.toggle('pem-dp-info--visible', metode === 'cod');
        }
    }

    function formatRupiah(angka) {
        if (angka === 0) return 'Rp 0';
        return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
});
