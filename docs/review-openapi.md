# Review Kontrak API dan Event

## Identitas Review

- Tanggal: 1 November 2026
- Nama reviewer: Tia Anggraeni Sutisna
- Peran reviewer: Anggota kelompok dan reviewer kontrak
- Kontrak utama yang ditinjau: Layanan Pemesanan
- Kontrak pendukung yang divalidasi: Layanan Inventori, Layanan Operasional, dan event `PesananDikonfirmasi`
- Media review: Swagger Editor, Spectral CLI, dan AsyncAPI CLI

## Endpoint Layanan Pemesanan yang Ditinjau

- [x] `POST /api/v1/pesanan`
- [x] `GET /api/v1/pesanan/{kode}`
- [x] `POST /api/v1/pesanan/{kode}/konfirmasi`
- [x] `POST /api/v1/pesanan/{kode}/batal`

## Checklist Review

- [x] Nama dan tujuan setiap endpoint mudah dipahami.
- [x] Request body pembuatan pesanan mudah dipahami.
- [x] Parameter `kode` memiliki fungsi yang jelas sebagai kode pesanan.
- [x] Respons sukses setiap endpoint mudah dibedakan.
- [x] Respons `404`, `409`, `422`, dan `500` memiliki tujuan yang berbeda dan mudah dipahami.
- [x] Response body tidak menampilkan ID database atau field internal yang tidak diperlukan.
- [x] Format Problem Details konsisten.
- [x] Kontrak menampilkan contoh nilai untuk membantu pengguna API.
- [x] Kontrak event `PesananDikonfirmasi` berhasil divalidasi.

## Hasil Review Endpoint

### `POST /api/v1/pesanan`

Endpoint digunakan untuk membuat pesanan baru dengan status awal `menunggu`.

Request body menjelaskan informasi kanal, nama pelanggan, dan daftar item pesanan. Setiap item memiliki kode menu, nama menu, jumlah, dan harga satuan.

Respons sukses menggunakan status `201 Created`. Respons validasi menggunakan status `422`, sedangkan kesalahan internal menggunakan status `500`.

Hasil review: request dan response mudah dipahami.

### `GET /api/v1/pesanan/{kode}`

Endpoint digunakan untuk melihat detail pesanan berdasarkan `kode_pesanan`.

Respons sukses menampilkan data utama pesanan beserta daftar item. Jika kode pesanan tidak ditemukan, API mengembalikan status `404` dalam format Problem Details.

Hasil review: parameter dan response mudah dipahami.

### `POST /api/v1/pesanan/{kode}/konfirmasi`

Endpoint digunakan untuk mengonfirmasi pesanan yang masih berstatus `menunggu`.

Kontrak membedakan kondisi sukses dengan konflik status pesanan menggunakan status `409`. Pesanan yang tidak ditemukan menggunakan status `404`.

Hasil review: tujuan endpoint dan kemungkinan kegagalannya mudah dibedakan.

### `POST /api/v1/pesanan/{kode}/batal`

Endpoint digunakan untuk membatalkan pesanan yang masih dapat dibatalkan.

Respons sukses menampilkan kode pesanan dan status `dibatalkan`. Konflik perubahan status menggunakan `409`, sedangkan pesanan yang tidak ditemukan menggunakan `404`.

Hasil review: tujuan endpoint dan response mudah dipahami.

## Review Format Problem Details

Respons kesalahan API menggunakan struktur berikut:

- `type`
- `title`
- `status`
- `detail`
- `instance`

Struktur tersebut digunakan secara konsisten untuk membedakan kesalahan validasi, data yang tidak ditemukan, konflik status, dan kesalahan internal.

## Hasil Validasi Otomatis

### OpenAPI

Validasi dilakukan terhadap:

- `contracts/pemesanan/openapi.yaml`
- `contracts/inventori/openapi.yaml`
- `contracts/operasional/openapi.yaml`

Hasil Spectral:

- 0 error
- 2 warning
- 0 info
- 0 hint

Kedua warning berasal dari aturan `info-contact` pada kontrak Inventori dan Operasional. Warning tersebut tidak memengaruhi struktur maupun validitas endpoint.

### AsyncAPI

Validasi dilakukan terhadap:

- `contracts/pemesanan/asyncapi.yaml`

Hasil AsyncAPI CLI menyatakan bahwa kontrak valid dan tidak memiliki governance issue.

## Bagian yang Membingungkan

Tidak ditemukan bagian utama yang menghalangi pemahaman penggunaan API. Nama endpoint, request, response, dan status code sudah menggambarkan tujuan operasinya.

Catatan kecil terdapat pada informasi kontak kontrak Inventori dan Operasional yang belum diisi.

## Saran Perbaikan

1. Menambahkan objek `contact` pada bagian `info` kontrak Inventori dan Operasional.
2. Menjaga contoh request dan response agar selalu sama dengan implementasi serta feature test.
3. Menjelaskan urutan perubahan status pesanan dan status operasional pada dokumentasi utama.
4. Menjalankan Spectral dan AsyncAPI CLI kembali setiap kali kontrak berubah.

## Keputusan Review

Kontrak API Layanan Pemesanan dan kontrak event `PesananDikonfirmasi` dapat digunakan sebagai dasar implementasi dan demonstrasi Checkpoint 1.

Status review: **Disetujui dengan catatan perbaikan dokumentasi minor**.

## Tindak Lanjut

- Kontrak dapat digunakan untuk pengujian dan demonstrasi.
- Warning `info-contact` dicatat sebagai perbaikan dokumentasi.
- Perubahan kontrak berikutnya harus ditinjau kembali oleh anggota selain penulis utama.