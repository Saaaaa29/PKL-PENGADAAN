/**
 * assets/js/app.js
 * JavaScript utama aplikasi SIPPA
 */

// =====================================================
// SIDEBAR TOGGLE
// =====================================================
document.addEventListener('DOMContentLoaded', function() {

    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar   = document.getElementById('sidebar');

    // Buat overlay untuk mobile
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                // Mobile: show/hide dengan class
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            } else {
                // Desktop: collapse/expand
                sidebar.classList.toggle('collapsed');
            }
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
});

// =====================================================
// FORMAT RUPIAH
// Fungsi JS untuk format angka ke Rupiah
// =====================================================
function formatRupiah(angka) {
    if (!angka) return 'Rp 0';
    let num = parseFloat(String(angka).replace(/[^0-9.-]/g, ''));
    return 'Rp ' + num.toLocaleString('id-ID', { minimumFractionDigits: 0 });
}

/**
 * Hapus format rupiah, kembalikan angka murni
 */
function unformatRupiah(str) {
    return parseFloat(String(str).replace(/[^0-9.]/g, '')) || 0;
}

// =====================================================
// OTOMATIS HITUNG NILAI ANGGARAN
// Ketika volume atau nilai_satuan berubah
// =====================================================
document.addEventListener('input', function(e) {
    const target = e.target;

    // Jangan proses input search DataTable custom
    if (target.id === 'dtSearchCustom') return;

    // Cari parent form group terdekat
    const container = target.closest('[data-calc]') || target.closest('.detail-item-card') || target.closest('form');
    if (!container) return;

    const vol   = container.querySelector('[name*="volume"]');
    const harga = container.querySelector('[name*="nilai_satuan"]');
    const total = container.querySelector('[name*="nilai_anggaran"]');

    if (vol && harga && total && (target === vol || target === harga)) {
        const nilaiTotal = (parseFloat(vol.value) || 0) * (parseFloat(harga.value) || 0);
        total.value = nilaiTotal;

        // Update display jika ada elemen display
        const display = container.querySelector('.display-nilai-anggaran');
        if (display) display.textContent = formatRupiah(nilaiTotal);
    }
});

// =====================================================
// TENTUKAN METODE PENGADAAN OTOMATIS
// Dipanggil saat nilai anggaran berubah
// =====================================================
function tentukanMetode(nilai) {
    nilai = parseFloat(nilai) || 0;
    if (nilai <= 15000000)  return 'pembelian_langsung';
    if (nilai <= 50000000)  return 'tender_terbatas_spk';   // 15jt - 50jt
    if (nilai <= 600000000) return 'tender_terbatas_pkp';   // 50jt - 600jt
    return 'tender_umum';
}

/**
 * Update select metode pengadaan berdasarkan nilai
 * @param {number} nilai - nilai anggaran dalam rupiah
 * @param {HTMLElement} selectEl - elemen <select> yang akan diupdate
 */
function updateMetodePengadaan(nilai, selectEl) {
    if (!selectEl) return;
    const metode = tentukanMetode(nilai);
    const currentVal = selectEl.value;

    // Jangan override jika user pilih e_purchasing atau swakelola
    if (currentVal === 'e_purchasing' || currentVal === 'swakelola') return;
    selectEl.value = metode;
}

// =====================================================
// KONFIRMASI HAPUS
// =====================================================
function konfirmasiHapus(url, nama) {
    if (confirm('Yakin ingin menghapus "' + nama + '"?\nData yang sudah dihapus tidak dapat dikembalikan.')) {
        window.location.href = url;
    }
}

// =====================================================
// AUTO-DISMISS ALERT setelah 3 detik
// Berlaku di semua halaman karena app.js di-load global
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        document.querySelectorAll('.alert.alert-dismissible').forEach(function(el) {
            // Pakai Bootstrap Alert API
            var bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert.close();
        });
    }, 3000);
});

// =====================================================
// INISIALISASI DATATABLES DEFAULT
// =====================================================
function initDataTable(selector, options = {}) {
    const defaultOptions = {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        },
        responsive: true,
        pageLength: 25,
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        ...options
    };
    return $(selector).DataTable(defaultOptions);
}