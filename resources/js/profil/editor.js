// Editor berformat (WYSIWYG) untuk isi berita, sambutan, dan sejarah. Dipasang lewat npm, bukan CDN.
// HTML yang dihasilkan tetap dibersihkan server (App\Services\Profil\IsiKaya) sebelum disimpan.
import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import './editor.css';
import { kecilkanFoto } from './lib/kecilkan-foto.js';

const BILAH_ALAT = [
    [{ header: [2, 3, false] }],
    [{ size: ['small', false, 'large', 'huge'] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
    [{ align: [] }],
    ['blockquote', 'link', 'image'],
    ['clean'],
];

// Tombol bawaan Quill tidak bernama; nama ini dibaca pembaca layar dan muncul saat kursor diarahkan.
const NAMA_TOMBOL = {
    '.ql-bold': 'Tebal',
    '.ql-italic': 'Miring',
    '.ql-underline': 'Garis bawah',
    '.ql-strike': 'Coret',
    '.ql-list[value="ordered"]': 'Daftar bernomor',
    '.ql-list[value="bullet"]': 'Daftar titik',
    '.ql-indent[value="-1"]': 'Kurangi menjorok',
    '.ql-indent[value="+1"]': 'Tambah menjorok (tab)',
    '.ql-blockquote': 'Kutipan',
    '.ql-link': 'Sisipkan tautan',
    '.ql-image': 'Sisipkan foto',
    '.ql-clean': 'Hapus format',
    '.ql-header .ql-picker-label': 'Jenis paragraf',
    '.ql-size .ql-picker-label': 'Ukuran huruf',
    '.ql-align .ql-picker-label': 'Rata teks',
};

document.querySelectorAll('[data-editor]').forEach(pasangEditor);

function pasangEditor(wadah) {
    const isian = wadah.querySelector('textarea');
    const label = wadah.querySelector('label');
    const status = wadah.querySelector('[data-editor-status]');
    const tempat = document.createElement('div');
    isian.after(tempat);

    const quill = new Quill(tempat, {
        theme: 'snow',
        placeholder: isian.getAttribute('placeholder') || 'Mulai menulis di sini…',
        modules: {
            toolbar: { container: BILAH_ALAT, handlers: { image: () => pilihFoto(quill) } },
            // Foto yang ditempel atau diseret ke editor diunggah ke server, bukan disimpan sebagai teks base64.
            uploader: { handler: (rentang, berkas) => unggahBanyak(quill, rentang.index, berkas) },
        },
    });

    quill.setContents(quill.clipboard.convert({ html: isian.value }), 'silent');

    Object.entries(NAMA_TOMBOL).forEach(([pemilih, nama]) => {
        wadah.querySelectorAll(pemilih).forEach((el) => {
            el.setAttribute('aria-label', nama);
            el.setAttribute('title', nama);
        });
    });

    const akar = quill.root;
    akar.setAttribute('role', 'textbox');
    akar.setAttribute('aria-multiline', 'true');
    if (label) {
        label.id ||= `${isian.id}-label`;
        akar.setAttribute('aria-labelledby', label.id);
        // Label tidak bisa menunjuk ke editor, jadi ketukan pada label memindahkan kursor ke editor.
        label.addEventListener('click', () => quill.focus());
    }
    if (isian.getAttribute('aria-invalid') === 'true') akar.setAttribute('aria-invalid', 'true');
    if (isian.getAttribute('aria-describedby')) akar.setAttribute('aria-describedby', isian.getAttribute('aria-describedby'));

    // Kotak teks asli disembunyikan, tetapi tetap yang dikirim ke server.
    isian.hidden = true;
    isian.required = false;
    wadah.classList.add('editor-siap');

    const salin = () => {
        // Spasi keras (&nbsp;) dari ekspor Quill diganti spasi biasa agar baris bisa terlipat di HP.
        isian.value = quill.getLength() > 1 ? quill.getSemanticHTML().replace(/&nbsp;/g, ' ') : '';
    };
    quill.on('text-change', salin);
    isian.form?.addEventListener('submit', (e) => {
        salin();
        if (wadah.dataset.sedangUnggah) {
            e.preventDefault();
            tulisStatus('Tunggu sebentar, foto masih diunggah…');
        }
    });

    function pilihFoto(q) {
        const masukan = document.createElement('input');
        masukan.type = 'file';
        masukan.accept = 'image/jpeg,image/png,image/webp';
        masukan.addEventListener('change', () => {
            const rentang = q.getSelection(true);
            unggahBanyak(q, rentang ? rentang.index : q.getLength(), [...masukan.files]);
        });
        masukan.click();
    }

    async function unggahBanyak(q, posisi, daftar) {
        wadah.dataset.sedangUnggah = '1';
        tulisStatus('Mengunggah foto…');

        try {
            for (const berkas of daftar) {
                const url = await unggah(await kecilkanFoto(berkas));
                q.insertEmbed(posisi, 'image', url, 'user');
                q.insertText(posisi + 1, '\n', 'user');
                posisi += 2;
            }
            q.setSelection(posisi, 0, 'silent');
            tulisStatus('Foto berhasil disisipkan.');
        } catch (galat) {
            tulisStatus(galat.message, true);
        } finally {
            delete wadah.dataset.sedangUnggah;
        }
    }

    async function unggah(berkas) {
        const data = new FormData();
        data.append('gambar', berkas);

        let jawaban;
        try {
            jawaban = await fetch(wadah.dataset.unggah, {
                method: 'POST',
                body: data,
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': wadah.dataset.token },
                credentials: 'same-origin',
            });
        } catch {
            throw new Error('Foto gagal diunggah. Periksa sinyal internet, lalu coba lagi.');
        }

        const isi = await jawaban.json().catch(() => ({}));
        if (!jawaban.ok) {
            const pesan = isi.errors?.gambar?.[0] ?? (jawaban.status === 419 ? 'Sesi habis. Muat ulang halaman, lalu coba lagi.' : 'Foto gagal diunggah. Coba lagi.');
            throw new Error(pesan);
        }

        return isi.url;
    }

    function tulisStatus(pesan, galat = false) {
        if (!status) return;
        status.textContent = pesan;
        status.classList.toggle('text-awas', galat);
        status.classList.toggle('font-bold', galat);
    }
}
