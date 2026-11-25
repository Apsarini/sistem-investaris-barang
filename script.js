document.addEventListener('DOMContentLoaded', () => {
    // 1. Toggle Tema (Dark / Light Mode) + LocalStorage
    const themeToggle = document.querySelector('#theme-toggle');
    const savedTheme = localStorage.getItem('theme');

    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        themeToggle.textContent = 'Mode Terang';
    }

    themeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        themeToggle.textContent = isDark ? 'Mode Terang' : 'Mode Gelap';
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });

    // 2. Validasi Form Real-Time & Sebelum Submit
    const form = document.querySelector('#inventory-form');
    const fields = {
        kode: {
            el: document.querySelector('#kode'),
            err: document.querySelector('#err-kode'),
            validate: (val) => val.trim().length >= 3 ? '' : 'Kode barang minimal 3 karakter.'
        },
        nama: {
            el: document.querySelector('#nama'),
            err: document.querySelector('#err-nama'),
            validate: (val) => val.trim().length >= 2 ? '' : 'Nama barang wajib diisi minimal 2 karakter.'
        },
        kategori: {
            el: document.querySelector('#kategori'),
            err: document.querySelector('#err-kategori'),
            validate: (val) => val !== '' ? '' : 'Silakan pilih kategori barang.'
        },
        stok: {
            el: document.querySelector('#stok'),
            err: document.querySelector('#err-stok'),
            validate: (val) => val !== '' && Number(val) >= 0 ? '' : 'Stok tidak boleh negatif.'
        },
        harga: {
            el: document.querySelector('#harga'),
            err: document.querySelector('#err-harga'),
            validate: (val) => Number(val) >= 100 ? '' : 'Harga minimal Rp 100.'
        }
    };

    Object.values(fields).forEach(({ el, err, validate }) => {
        el.addEventListener('input', () => {
            err.textContent = validate(el.value);
        });
    });

    form.addEventListener('submit', (e) => {
        let isValid = true;
        Object.values(fields).forEach(({ el, err, validate }) => {
            const msg = validate(el.value);
            err.textContent = msg;
            if (msg !== '') isValid = false;
        });

        if (!isValid) {
            e.preventDefault();
        }
    });
});