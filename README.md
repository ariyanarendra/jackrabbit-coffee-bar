# Jackrabbit Coffee Bar

## Integrasi Sistem dan Komputasi Awan

### Checkpoint 1 — Analisis Integrasi, Kontrak API, Tiga Layanan Berjalan, dan RabbitMQ

Proyek **Jackrabbit Coffee Bar** dikembangkan sebagai bagian dari mata kuliah **Integrasi Sistem dan Komputasi Awan**. Proyek ini membangun fondasi integrasi untuk proses pemesanan, pengelolaan inventori, dan operasional kedai kopi.

Kondisi awal melibatkan beberapa kanal pemesanan, seperti kasir Luna iPad, Gojek, GrabFood, dan ShopeeFood. Setiap kanal mempunyai aplikasi dan alur kerja tersendiri. Sistem internal pada proyek ini memisahkan proses bisnis menjadi tiga layanan agar kepemilikan data, tanggung jawab, dan komunikasi antarsistem menjadi lebih jelas.

Fokus implementasi Checkpoint 1 meliputi:

- tiga layanan Laravel yang berjalan menggunakan Laravel Sail;
- database MySQL terpisah untuk setiap layanan;
- komunikasi sinkron melalui REST API;
- komunikasi asinkron melalui RabbitMQ;
- kontrak REST API menggunakan OpenAPI;
- kontrak event menggunakan AsyncAPI;
- format kegagalan menggunakan Problem Details;
- transaksi dan idempotensi pada pengurangan stok;
- consumer RabbitMQ yang berjalan otomatis;
- feature test pada setiap layanan;
- pengujian integrasi antarlayanan.

---

# 1. Anggota Kelompok

| NIM | Nama | Akun GitHub |
| --- | --- | --- |
| 3224012 | Ariya Narendra Ardi Alfirdaus | [ariyanarendra](https://github.com/ariyanarendra) |
| 3224015 | Melanie Priatna | [MelaniePriatna24](https://github.com/MelaniePriatna24) |
| 3224010 | Minarianty Ismail | [minarianty](https://github.com/minarianty) |
| 3224004 | Tia Anggraeni Sutisna | [Tiaa07](https://github.com/Tiaa07) |

---

# 2. Studi Kasus

Jackrabbit Coffee Bar menerima pesanan dari beberapa kanal. Pesanan dapat berasal dari kasir yang menggunakan Luna iPad maupun aplikasi pemesanan daring, seperti Gojek, GrabFood, dan ShopeeFood.

Penggunaan beberapa kanal membantu menjangkau lebih banyak pelanggan, tetapi menimbulkan beberapa persoalan:

- pegawai perlu memantau beberapa aplikasi;
- informasi pesanan perlu diteruskan kepada bagian operasional;
- jumlah stok perlu disesuaikan dengan pesanan yang dikonfirmasi;
- pesanan dapat terlambat diproses atau terlewat;
- stok dapat menjadi tidak sesuai dengan kondisi sebenarnya;
- data pesanan dapat tercatat lebih dari satu kali;
- format kesalahan antarsistem dapat berbeda-beda.

Proyek ini belum terhubung langsung dengan API Gojek, GrabFood, ShopeeFood, dan Luna iPad. Sistem eksternal tersebut digunakan sebagai konteks permasalahan. Implementasi Checkpoint 1 difokuskan pada integrasi tiga layanan internal.

---

# 3. Arsitektur Sistem

Arsitektur terdiri dari tiga layanan utama:

1. **Layanan Pemesanan**
2. **Layanan Inventori**
3. **Layanan Operasional**

Diagram arsitektur sederhana:

```text
                          +-----------------------+
                          |    Kasir / Client     |
                          +-----------+-----------+
                                      |
                                      | REST / JSON
                                      v
                  +-------------------------------------------+
                  |          Layanan Pemesanan                |
                  |-------------------------------------------|
                  | Laravel Sail — http://localhost:8080      |
                  | MySQL Pemesanan — host port 3306          |
                  | Membuat, melihat, mengonfirmasi,           |
                  | dan membatalkan pesanan                   |
                  +----------------+--------------------------+
                                   |
                  +----------------+----------------+
                  |                                 |
                  | REST / HTTP                     | Publish event
                  | POST /api/v1/stok/kurangi       | PesananDikonfirmasi
                  v                                 v
       +----------------------------+     +-----------------------------+
       |    Layanan Inventori       |     |          RabbitMQ           |
       |----------------------------|     |-----------------------------|
       | Laravel Sail — :8081       |     | AMQP — :5672                |
       | MySQL Inventori — :3307    |     | Management UI — :15672      |
       | Mengelola menu dan stok    |     | Exchange: pesanan.events    |
       | Transaksi + idempotensi    |     | Type: topic                 |
       +----------------------------+     | Routing key:                |
                                          | pesanan.dikonfirmasi        |
                                          +--------------+--------------+
                                                         |
                                                         | Queue:
                                                         | operasional.
                                                         | pesanan-dikonfirmasi
                                                         v
                                          +-----------------------------+
                                          |    Layanan Operasional      |
                                          |-----------------------------|
                                          | Laravel Sail — :8082        |
                                          | MySQL Operasional — :3308   |
                                          | RabbitMQ consumer otomatis  |
                                          | Mengelola status pengerjaan |
                                          +-----------------------------+
```

Setiap layanan mempunyai database sendiri. Layanan tidak melakukan join maupun akses langsung ke database layanan lain. Pertukaran data dilakukan melalui REST API atau event RabbitMQ.

---

# 4. Batas Tanggung Jawab Layanan

## 4.1 Layanan Pemesanan

Layanan Pemesanan menjadi pintu masuk utama proses pesanan.

Tanggung jawabnya meliputi:

- menerima payload pembuatan pesanan;
- memvalidasi kanal, nama pelanggan, dan daftar item;
- menghitung subtotal setiap item;
- menghitung total pesanan;
- membuat kode pesanan;
- menyimpan pesanan dan item pesanan;
- menampilkan detail pesanan;
- memproses konfirmasi pesanan;
- meminta Layanan Inventori mengurangi stok;
- mengubah status pesanan menjadi `dikonfirmasi`;
- menerbitkan event `PesananDikonfirmasi`;
- membatalkan pesanan yang masih berstatus `menunggu`.

Status pesanan:

```text
menunggu
   |
   +---- konfirmasi berhasil ----> dikonfirmasi
   |
   +---- pembatalan -------------> dibatalkan
```

Pesanan yang sudah `dikonfirmasi` atau `dibatalkan` tidak dapat dibatalkan kembali.

## 4.2 Layanan Inventori

Layanan Inventori memiliki kepemilikan atas data menu dan stok.

Tanggung jawabnya meliputi:

- menyimpan kode dan nama menu;
- menyimpan harga satuan;
- menyimpan jumlah stok;
- menampilkan detail menu;
- memeriksa ketersediaan stok tanpa mengubah data;
- mengurangi stok secara atomik;
- mengunci data menu selama transaksi;
- menyimpan riwayat pengurangan stok;
- menerapkan idempotensi berdasarkan kode pesanan;
- menolak pengurangan apabila stok tidak mencukupi.

Jika request dengan `kode_pesanan` dan payload yang sama dikirim ulang, stok tidak dikurangi untuk kedua kalinya. Jika kode pesanan sama tetapi isi payload berbeda, layanan mengembalikan `409 Conflict` dengan judul `Konflik idempotensi`.

## 4.3 Layanan Operasional

Layanan Operasional menerima pesanan yang sudah dikonfirmasi melalui RabbitMQ.

Tanggung jawabnya meliputi:

- menjalankan consumer RabbitMQ;
- mendeklarasikan exchange, queue, dan binding;
- menerima event `PesananDikonfirmasi`;
- memvalidasi struktur event;
- menyimpan payload event;
- mencegah event yang sama diproses dua kali berdasarkan `event_id`;
- menampilkan daftar pesanan operasional;
- memfilter daftar berdasarkan status operasional;
- menampilkan detail pesanan operasional;
- memperbarui status pengerjaan.

Urutan status operasional:

```text
menunggu_diproses
        |
        v
sedang_diproses
        |
        v
siap_diambil
        |
        v
selesai
```

Status tidak dapat melompati tahapan. Perubahan yang tidak valid menghasilkan `409 Conflict`.

---

# 5. Pola Integrasi

## 5.1 Integrasi Sinkron melalui REST API

Jalur sinkron digunakan antara Layanan Pemesanan dan Layanan Inventori.

```text
Layanan Pemesanan
        |
        | POST /api/v1/stok/kurangi
        v
Layanan Inventori
        |
        | Respons langsung
        v
Layanan Pemesanan
```

REST dipilih karena hasil pengurangan stok harus diketahui sebelum status pesanan diubah menjadi `dikonfirmasi`.

Kelebihan:

- hasil berhasil atau gagal diterima langsung;
- mudah diuji melalui HTTP;
- sesuai untuk operasi yang membutuhkan keputusan segera;
- kontrak request dan response dapat didokumentasikan dengan OpenAPI.

Trade-off:

- konfirmasi pesanan bergantung pada ketersediaan Inventori;
- gangguan jaringan dapat menggagalkan konfirmasi;
- Pemesanan harus memahami respons kegagalan Inventori.

## 5.2 Integrasi Asinkron melalui RabbitMQ

Jalur asinkron digunakan antara Layanan Pemesanan dan Layanan Operasional.

```text
Layanan Pemesanan
        |
        | Publish PesananDikonfirmasi
        v
RabbitMQ
        |
        | Consume event
        v
Layanan Operasional
```

RabbitMQ dipilih agar Pemesanan tidak harus menunggu proses penyimpanan pada Operasional.

Kelebihan:

- publisher dan consumer tidak terikat secara langsung;
- pesan dapat ditahan di queue sampai consumer siap;
- Operasional dapat diproses secara terpisah;
- pengiriman ulang dapat ditangani secara idempoten.

Trade-off:

- membutuhkan message broker dan consumer;
- perubahan data antarlayanan tidak terjadi pada waktu yang sama;
- berlaku eventual consistency;
- membutuhkan penanganan pesan gagal dan duplikat.

## 5.3 Database per Layanan

Setiap layanan menggunakan database MySQL sendiri:

| Layanan | Data yang dimiliki |
| --- | --- |
| Pemesanan | Pesanan dan item pesanan |
| Inventori | Menu dan riwayat pengurangan stok |
| Operasional | Pesanan operasional dan payload event |

Pendekatan ini memperjelas kepemilikan data dan mengurangi ketergantungan terhadap database bersama.

---

# 6. Struktur Repository

```text
jackrabbit-coffee-bar/
|
├── contracts/
│   ├── inventori/
│   │   └── openapi.yaml
│   ├── operasional/
│   │   └── openapi.yaml
│   └── pemesanan/
│       ├── asyncapi.yaml
│       └── openapi.yaml
|
├── docs/
│   ├── menjalankan-proyek.md
│   └── review-openapi.md
|
├── layanan-inventori/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── tests/
│   ├── compose.yaml
│   └── ...
|
├── layanan-operasional/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── tests/
│   ├── compose.yaml
│   └── ...
|
├── layanan-pemesanan/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── tests/
│   ├── compose.yaml
│   └── ...
|
├── .gitignore
├── .spectral.yaml
├── package-lock.json
├── package.json
└── README.md
```

---

# 7. Teknologi

| Komponen | Teknologi | Fungsi |
| --- | --- | --- |
| Framework | Laravel | REST API dan logika aplikasi |
| Runtime | PHP 8.5 | Menjalankan Laravel di dalam container |
| Container | Docker Desktop dan Laravel Sail | Menjalankan layanan secara terisolasi |
| Database | MySQL 8.4 | Menyimpan data setiap layanan |
| Message broker | RabbitMQ | Mengirim event secara asinkron |
| Library AMQP | php-amqplib | Publisher dan consumer RabbitMQ |
| Kontrak REST | OpenAPI | Dokumentasi request, response, dan error |
| Kontrak event | AsyncAPI | Dokumentasi event RabbitMQ |
| Linter | Spectral | Validasi kontrak OpenAPI |
| AsyncAPI CLI | AsyncAPI CLI | Validasi kontrak AsyncAPI |
| Testing | PHPUnit | Feature test |
| Version control | Git dan GitHub | Riwayat perubahan dan kontribusi anggota |

---

# 8. Port Layanan

| Komponen | Port host | Port container / fungsi |
| --- | ---: | --- |
| HTTP Pemesanan | `8080` | HTTP `80` |
| HTTP Inventori | `8081` | HTTP `80` |
| HTTP Operasional | `8082` | HTTP `80` |
| Vite Pemesanan | `5173` | Vite |
| Vite Inventori | `5174` | Vite |
| Vite Operasional | `5175` | Vite |
| MySQL Pemesanan | `3306` | MySQL `3306` |
| MySQL Inventori | `3307` | MySQL `3306` |
| MySQL Operasional | `3308` | MySQL `3306` |
| RabbitMQ AMQP | `5672` | AMQP `5672` |
| RabbitMQ Management | `15672` | Management UI `15672` |

Port database di dalam container tetap `3306`. Port `3307` dan `3308` merupakan port host untuk menghindari konflik saat tiga MySQL berjalan bersamaan.

---

# 9. Kontrak API dan Event

## 9.1 Kontrak OpenAPI

Kontrak REST dibuat secara design-first:

- [OpenAPI Layanan Pemesanan](contracts/pemesanan/openapi.yaml)
- [OpenAPI Layanan Inventori](contracts/inventori/openapi.yaml)
- [OpenAPI Layanan Operasional](contracts/operasional/openapi.yaml)

Kontrak memuat:

- endpoint dan metode HTTP;
- parameter path dan query;
- request body;
- response sukses;
- response kegagalan;
- skema data;
- contoh payload;
- Problem Details.

## 9.2 Kontrak AsyncAPI

Kontrak event tersedia pada:

- [AsyncAPI PesananDikonfirmasi](contracts/pemesanan/asyncapi.yaml)

Kontrak memuat:

- nama event;
- channel;
- exchange;
- routing key;
- publisher;
- consumer;
- struktur payload;
- tipe setiap field.

## 9.3 Validasi Kontrak

Install dependency root repository:

```bash
cd ~/jackrabbit-coffee-bar
npm ci
```

Validasi seluruh kontrak:

```bash
npm run lint:contracts
```

Validasi OpenAPI saja:

```bash
npm run lint:openapi
```

Validasi AsyncAPI saja:

```bash
npm run validate:asyncapi
```

Target validasi adalah tidak ada error. Docker Desktop harus aktif ketika menjalankan validasi AsyncAPI karena perintah menggunakan container Node.js.

---

# 10. Endpoint Layanan Pemesanan

Base URL lokal:

```text
http://localhost:8080/api/v1
```

| Metode | Endpoint | Fungsi | Respons sukses |
| --- | --- | --- | --- |
| `POST` | `/pesanan` | Membuat pesanan | `201 Created` |
| `GET` | `/pesanan/{kode}` | Melihat detail pesanan | `200 OK` |
| `POST` | `/pesanan/{kode}/konfirmasi` | Mengonfirmasi pesanan | `200 OK` |
| `POST` | `/pesanan/{kode}/batal` | Membatalkan pesanan | `200 OK` |

## 10.1 Membuat Pesanan

```http
POST /api/v1/pesanan
Content-Type: application/json
Accept: application/json
```

Contoh request:

```json
{
  "kanal": "grabfood",
  "nama_pelanggan": "Andi",
  "items": [
    {
      "kode_menu": "MNU-001",
      "nama_menu": "Cafe Latte",
      "jumlah": 2,
      "harga_satuan": 25000
    },
    {
      "kode_menu": "MNU-002",
      "nama_menu": "Americano",
      "jumlah": 1,
      "harga_satuan": 20000
    }
  ]
}
```

Contoh curl:

```bash
curl -i --request POST \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{
    "kanal": "grabfood",
    "nama_pelanggan": "Andi",
    "items": [
      {
        "kode_menu": "MNU-001",
        "nama_menu": "Cafe Latte",
        "jumlah": 2,
        "harga_satuan": 25000
      },
      {
        "kode_menu": "MNU-002",
        "nama_menu": "Americano",
        "jumlah": 1,
        "harga_satuan": 20000
      }
    ]
  }' \
  http://localhost:8080/api/v1/pesanan
```

Contoh response:

```json
{
  "data": {
    "kode_pesanan": "PSN-XXXXXXXX",
    "kanal": "grabfood",
    "nama_pelanggan": "Andi",
    "status": "menunggu",
    "total": 70000,
    "items": [
      {
        "kode_menu": "MNU-001",
        "nama_menu": "Cafe Latte",
        "jumlah": 2,
        "harga_satuan": 25000,
        "subtotal": 50000
      },
      {
        "kode_menu": "MNU-002",
        "nama_menu": "Americano",
        "jumlah": 1,
        "harga_satuan": 20000,
        "subtotal": 20000
      }
    ]
  }
}
```

Response menggunakan `201 Created` dan header `Location` yang menunjuk endpoint detail pesanan.

## 10.2 Melihat Pesanan

Ganti `KODE_PESANAN` dengan kode dari response pembuatan pesanan.

```bash
curl -i \
  --header "Accept: application/json" \
  http://localhost:8080/api/v1/pesanan/KODE_PESANAN
```

Jika pesanan tidak ditemukan, API mengembalikan `404 Not Found` dalam format Problem Details.

## 10.3 Mengonfirmasi Pesanan

Inventori harus aktif dan mempunyai stok untuk seluruh item.

```bash
curl -i --request POST \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  http://localhost:8080/api/v1/pesanan/KODE_PESANAN/konfirmasi
```

Contoh response:

```json
{
  "data": {
    "kode_pesanan": "KODE_PESANAN",
    "status": "dikonfirmasi"
  }
}
```

Pada proses ini Pemesanan:

1. mengambil data pesanan dan item;
2. memastikan status masih `menunggu`;
3. memanggil endpoint pengurangan stok Inventori;
4. mengubah status menjadi `dikonfirmasi`;
5. menerbitkan event `PesananDikonfirmasi`.

## 10.4 Membatalkan Pesanan

Pesanan hanya dapat dibatalkan ketika masih berstatus `menunggu`.

```bash
curl -i --request POST \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  http://localhost:8080/api/v1/pesanan/KODE_PESANAN/batal
```

Contoh response:

```json
{
  "data": {
    "kode_pesanan": "KODE_PESANAN",
    "status": "dibatalkan"
  }
}
```

---

# 11. Endpoint Layanan Inventori

Base URL lokal:

```text
http://localhost:8081/api/v1
```

| Metode | Endpoint | Fungsi | Respons sukses |
| --- | --- | --- | --- |
| `GET` | `/menu/{kode}` | Melihat detail menu | `200 OK` |
| `POST` | `/stok/cek` | Memeriksa stok | `200 OK` |
| `POST` | `/stok/kurangi` | Mengurangi stok | `200 OK` |

## 11.1 Membuat Data Menu Uji

Checkpoint 1 belum menyediakan endpoint administrasi untuk membuat menu. Data uji dapat dibuat menggunakan Tinker:

```bash
cd ~/jackrabbit-coffee-bar/layanan-inventori

vendor/bin/sail artisan tinker --execute="App\Models\Menu::query()->updateOrCreate(['kode_menu' => 'MNU-001'], ['nama_menu' => 'Cafe Latte', 'harga_satuan' => 25000, 'stok' => 20]); App\Models\Menu::query()->updateOrCreate(['kode_menu' => 'MNU-002'], ['nama_menu' => 'Americano', 'harga_satuan' => 20000, 'stok' => 10]);"
```

## 11.2 Melihat Detail Menu

```bash
curl -i \
  --header "Accept: application/json" \
  http://localhost:8081/api/v1/menu/MNU-001
```

Contoh response:

```json
{
  "data": {
    "kode_menu": "MNU-001",
    "nama_menu": "Cafe Latte",
    "harga_satuan": 25000,
    "stok": 20
  }
}
```

## 11.3 Memeriksa Ketersediaan Stok

Pemeriksaan ini tidak mengurangi stok.

```bash
curl -i --request POST \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{
    "items": [
      {
        "kode_menu": "MNU-001",
        "jumlah": 2
      },
      {
        "kode_menu": "MNU-002",
        "jumlah": 1
      }
    ]
  }' \
  http://localhost:8081/api/v1/stok/cek
```

Contoh response:

```json
{
  "data": {
    "tersedia": true,
    "items": [
      {
        "kode_menu": "MNU-001",
        "jumlah_diminta": 2,
        "stok_tersedia": 20,
        "tersedia": true
      },
      {
        "kode_menu": "MNU-002",
        "jumlah_diminta": 1,
        "stok_tersedia": 10,
        "tersedia": true
      }
    ]
  }
}
```

## 11.4 Mengurangi Stok

```bash
curl -i --request POST \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{
    "kode_pesanan": "PSN-DEMO-STOK-001",
    "items": [
      {
        "kode_menu": "MNU-001",
        "jumlah": 2
      },
      {
        "kode_menu": "MNU-002",
        "jumlah": 1
      }
    ]
  }' \
  http://localhost:8081/api/v1/stok/kurangi
```

Contoh response:

```json
{
  "data": {
    "kode_pesanan": "PSN-DEMO-STOK-001",
    "status": "stok_dikurangi"
  }
}
```

Mengirim ulang request yang sama tidak mengurangi stok untuk kedua kalinya.

---

# 12. Endpoint Layanan Operasional

Base URL lokal:

```text
http://localhost:8082/api/v1
```

| Metode | Endpoint | Fungsi | Respons sukses |
| --- | --- | --- | --- |
| `GET` | `/pesanan` | Daftar pesanan operasional | `200 OK` |
| `GET` | `/pesanan/{kode}` | Detail pesanan operasional | `200 OK` |
| `PATCH` | `/pesanan/{kode}/status` | Memperbarui status | `200 OK` |

## 12.1 Menampilkan Daftar Pesanan

```bash
curl -i \
  --header "Accept: application/json" \
  http://localhost:8082/api/v1/pesanan
```

Memfilter berdasarkan status:

```bash
curl -i \
  --header "Accept: application/json" \
  "http://localhost:8082/api/v1/pesanan?status_operasional=menunggu_diproses"
```

## 12.2 Menampilkan Detail Pesanan

```bash
curl -i \
  --header "Accept: application/json" \
  http://localhost:8082/api/v1/pesanan/KODE_PESANAN
```

Detail memuat informasi pesanan dan daftar item yang berasal dari event.

## 12.3 Memperbarui Status Operasional

Perubahan pertama:

```bash
curl -i --request PATCH \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{
    "status_operasional": "sedang_diproses"
  }' \
  http://localhost:8082/api/v1/pesanan/KODE_PESANAN/status
```

Perubahan berikutnya menuju `siap_diambil`:

```bash
curl -i --request PATCH \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{
    "status_operasional": "siap_diambil"
  }' \
  http://localhost:8082/api/v1/pesanan/KODE_PESANAN/status
```

Perubahan terakhir menuju `selesai`:

```bash
curl -i --request PATCH \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{
    "status_operasional": "selesai"
  }' \
  http://localhost:8082/api/v1/pesanan/KODE_PESANAN/status
```

---

# 13. Problem Details

Semua API menggunakan format Problem Details untuk respons kegagalan.

Content-Type:

```text
application/problem+json
```

Struktur umum:

```json
{
  "type": "about:blank",
  "title": "Request tidak valid",
  "status": 422,
  "detail": "The items field is required.",
  "instance": "/api/v1/pesanan"
}
```

Kode status yang digunakan:

| Status | Penggunaan |
| ---: | --- |
| `404` | Pesanan atau menu tidak ditemukan |
| `409` | Stok tidak mencukupi, konflik idempotensi, atau perubahan status tidak diperbolehkan |
| `422` | Payload request tidak valid |
| `500` | Kesalahan internal yang tidak diprediksi |

Contoh menguji validasi:

```bash
curl -i --request POST \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{}' \
  http://localhost:8080/api/v1/pesanan
```

Hasil yang diharapkan:

- status `422 Unprocessable Content`;
- Content-Type `application/problem+json`;
- body memuat `type`, `title`, `status`, `detail`, dan `instance`.

---

# 14. Kontrak Event PesananDikonfirmasi

| Komponen | Nilai |
| --- | --- |
| Event | `PesananDikonfirmasi` |
| Publisher | Layanan Pemesanan |
| Consumer | Layanan Operasional |
| Broker | RabbitMQ |
| Exchange | `pesanan.events` |
| Exchange type | `topic` |
| Routing key | `pesanan.dikonfirmasi` |
| Queue | `operasional.pesanan-dikonfirmasi` |
| Content-Type | `application/json` |
| Delivery mode | Persistent (`2`) |

Contoh payload event:

```json
{
  "event_id": "9bdb7771-c4c5-4eca-ab7d-4420be544229",
  "event_name": "PesananDikonfirmasi",
  "occurred_at": "2026-10-31T03:18:12+00:00",
  "data": {
    "kode_pesanan": "PSN-000001",
    "kanal": "grabfood",
    "nama_pelanggan": "Andi",
    "status": "dikonfirmasi",
    "total": 50000,
    "items": [
      {
        "kode_menu": "MNU-001",
        "nama_menu": "Cafe Latte",
        "jumlah": 2,
        "harga_satuan": 25000,
        "subtotal": 50000
      }
    ]
  }
}
```

Properti AMQP yang diterbitkan:

- `content_type`: `application/json`;
- `delivery_mode`: `2`;
- `message_id`: sama dengan `event_id`;
- `type`: `PesananDikonfirmasi`;
- `timestamp`: waktu publikasi;
- `app_id`: `layanan-pemesanan`.

---

# 15. Persyaratan Lingkungan

Untuk Windows, gunakan WSL 2 dengan integrasi Docker Desktop yang aktif.

| Perangkat lunak | Kegunaan |
| --- | --- |
| Git | Clone repository dan version control |
| Docker Desktop | Menjalankan container |
| Docker Compose v2 | Orkestrasi container setiap layanan |
| WSL 2 Ubuntu | Terminal pengembangan pada Windows |
| PHP CLI | Menjalankan Composer pada host |
| Composer 2 | Memasang dependency Laravel |
| Node.js | Menjalankan tooling kontrak |
| npm | Memasang Spectral |
| Visual Studio Code | Editor proyek |

Periksa perangkat lunak:

```bash
git --version
docker --version
docker compose version
php --version
composer --version
node --version
npm --version
```

Pastikan Docker Desktop berjalan sebelum menyalakan layanan.

---

# 16. Clone Repository

```bash
cd ~
git clone https://github.com/ariyanarendra/jackrabbit-coffee-bar.git
cd ~/jackrabbit-coffee-bar
```

Panduan berikut mengasumsikan repository berada di:

```text
~/jackrabbit-coffee-bar
```

Install dependency tooling kontrak:

```bash
cd ~/jackrabbit-coffee-bar
npm ci
```

---

# 17. Menjalankan Layanan Pemesanan dari Nol

Masuk ke folder:

```bash
cd ~/jackrabbit-coffee-bar/layanan-pemesanan
```

Install dependency:

```bash
composer install
```

Buat environment:

```bash
cp .env.example .env
```

Konfigurasi port default pada `.env.example`:

```env
APP_URL=http://localhost:8080
APP_PORT=8080
VITE_PORT=5173
FORWARD_DB_PORT=3306
```

Konfigurasi integrasi:

```env
INVENTORI_BASE_URL=http://host.docker.internal:8081

RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=jackrabbit
RABBITMQ_PASSWORD=secret
RABBITMQ_VHOST=/
RABBITMQ_EXCHANGE=pesanan.events
FORWARD_RABBITMQ_PORT=5672
FORWARD_RABBITMQ_MANAGEMENT_PORT=15672
```

Jalankan container:

```bash
vendor/bin/sail up -d
```

Periksa status:

```bash
vendor/bin/sail ps
```

Tunggu sampai MySQL dan RabbitMQ berstatus `healthy`.

Generate application key:

```bash
vendor/bin/sail artisan key:generate
```

Jalankan migration:

```bash
vendor/bin/sail artisan migrate --no-interaction
```

Bersihkan cache konfigurasi:

```bash
vendor/bin/sail artisan config:clear
```

Layanan dapat diakses melalui:

```text
http://localhost:8080
```

RabbitMQ Management dapat diakses melalui:

```text
http://localhost:15672
```

Kredensial lokal:

```text
Username: jackrabbit
Password: secret
```

---

# 18. Menjalankan Layanan Inventori dari Nol

```bash
cd ~/jackrabbit-coffee-bar/layanan-inventori
composer install
cp .env.example .env
```

Konfigurasi port:

```env
APP_URL=http://localhost:8081
APP_PORT=8081
VITE_PORT=5174
FORWARD_DB_PORT=3307
```

Jalankan service:

```bash
vendor/bin/sail up -d
vendor/bin/sail ps
```

Tunggu sampai MySQL berstatus `healthy`.

```bash
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate --no-interaction
vendor/bin/sail artisan config:clear
```

Layanan dapat diakses melalui:

```text
http://localhost:8081
```

---

# 19. Menjalankan Layanan Operasional dari Nol

```bash
cd ~/jackrabbit-coffee-bar/layanan-operasional
composer install
cp .env.example .env
```

Konfigurasi port:

```env
APP_URL=http://localhost:8082
APP_PORT=8082
VITE_PORT=5175
FORWARD_DB_PORT=3308
```

Konfigurasi RabbitMQ:

```env
RABBITMQ_HOST=host.docker.internal
RABBITMQ_PORT=5672
RABBITMQ_USER=jackrabbit
RABBITMQ_PASSWORD=secret
RABBITMQ_VHOST=/
RABBITMQ_EXCHANGE=pesanan.events
RABBITMQ_QUEUE=operasional.pesanan-dikonfirmasi
RABBITMQ_ROUTING_KEY=pesanan.dikonfirmasi
```

Pastikan RabbitMQ milik Layanan Pemesanan telah aktif.

Jalankan service:

```bash
vendor/bin/sail up -d
vendor/bin/sail ps
```

Tunggu sampai MySQL berstatus `healthy`.

```bash
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate --no-interaction
vendor/bin/sail artisan config:clear
```

Container `rabbitmq-consumer` menjalankan command:

```text
php artisan rabbitmq:consume-pesanan-dikonfirmasi
```

Consumer berjalan otomatis dengan kebijakan restart `unless-stopped`.

Layanan dapat diakses melalui:

```text
http://localhost:8082
```

---

# 20. Menyalakan Proyek Setelah Laptop Dinyalakan

Urutan startup penting agar RabbitMQ tersedia sebelum consumer Operasional mencoba terhubung.

## 20.1 Nyalakan Docker Desktop

Tunggu sampai Docker Engine berjalan.

## 20.2 Nyalakan Pemesanan dan RabbitMQ

```bash
cd ~/jackrabbit-coffee-bar/layanan-pemesanan
vendor/bin/sail up -d
vendor/bin/sail ps
```

Container yang diharapkan:

```text
layanan-pemesanan-laravel.test-1
layanan-pemesanan-mysql-1
layanan-pemesanan-rabbitmq-1
```

## 20.3 Nyalakan Inventori

```bash
cd ~/jackrabbit-coffee-bar/layanan-inventori
vendor/bin/sail up -d
vendor/bin/sail ps
```

## 20.4 Nyalakan Operasional

```bash
cd ~/jackrabbit-coffee-bar/layanan-operasional
vendor/bin/sail up -d
vendor/bin/sail ps
```

Container yang diharapkan:

```text
layanan-operasional-laravel.test-1
layanan-operasional-mysql-1
layanan-operasional-rabbitmq-consumer-1
```

## 20.5 Periksa Consumer

```bash
cd ~/jackrabbit-coffee-bar/layanan-operasional
docker compose logs --tail=30 rabbitmq-consumer
```

Log normal:

```text
Consumer aktif. Tekan Ctrl+C untuk berhenti.
```

---

# 21. Pemeriksaan RabbitMQ

## 21.1 Management UI

Buka:

```text
http://localhost:15672
```

Login:

```text
Username: jackrabbit
Password: secret
```

## 21.2 Komponen Broker

| Komponen | Nilai |
| --- | --- |
| Exchange | `pesanan.events` |
| Exchange type | `topic` |
| Queue | `operasional.pesanan-dikonfirmasi` |
| Routing key | `pesanan.dikonfirmasi` |

Publisher Pemesanan mendeklarasikan exchange. Consumer Operasional mendeklarasikan exchange, queue, dan binding ketika mulai berjalan.

## 21.3 Pemeriksaan di Management UI

1. Buka menu **Exchanges**.
2. Pilih `pesanan.events`.
3. Pastikan exchange bertipe `topic` dan durable.
4. Buka menu **Queues and Streams**.
5. Pilih `operasional.pesanan-dikonfirmasi`.
6. Periksa consumer yang terhubung.
7. Konfirmasikan satu pesanan.
8. Periksa log consumer dan data pada Operasional.

Nilai Ready dapat kembali menjadi nol karena pesan langsung diproses dan diakui oleh consumer.

---

# 22. Menjalankan Feature Test

## 22.1 Layanan Pemesanan

```bash
cd ~/jackrabbit-coffee-bar/layanan-pemesanan
vendor/bin/sail artisan test --compact
```

Pengujian mencakup:

- membuat pesanan valid;
- validasi payload kosong;
- kesalahan internal;
- melihat pesanan;
- pesanan tidak ditemukan;
- konfirmasi pesanan;
- konflik status konfirmasi;
- kegagalan integrasi Inventori;
- pembatalan pesanan;
- konflik pembatalan.

## 22.2 Layanan Inventori

```bash
cd ~/jackrabbit-coffee-bar/layanan-inventori
vendor/bin/sail artisan test --compact
```

Pengujian mencakup:

- melihat menu;
- menu tidak ditemukan;
- pemeriksaan stok cukup dan tidak cukup;
- pengurangan stok atomik;
- request idempoten;
- konflik idempotensi;
- stok tidak mencukupi;
- payload tidak valid;
- kesalahan internal.

## 22.3 Layanan Operasional

```bash
cd ~/jackrabbit-coffee-bar/layanan-operasional
vendor/bin/sail artisan test --compact
```

Pengujian mencakup:

- menyimpan event;
- mencegah event duplikat;
- daftar pesanan;
- filter status;
- detail pesanan;
- pesanan tidak ditemukan;
- perubahan status valid;
- perubahan status tidak valid;
- validasi payload.

## 22.4 Hasil Pengujian Terakhir

| Layanan | Test lulus | Assertions | Hasil |
| --- | ---: | ---: | --- |
| Pemesanan | 15 | 160 | PASS |
| Inventori | 15 | 116 | PASS |
| Operasional | 12 | 72 | PASS |
| **Total** | **42** | **348** | **PASS** |

Jumlah test dapat berubah ketika test baru ditambahkan. Target utama adalah seluruh test lulus tanpa kegagalan.

---

# 23. Alur Demo Integrasi End-to-End

Gunakan terminal terpisah untuk setiap layanan. Pastikan semua container aktif dan migration telah dijalankan.

## 23.1 Terminal 1 — Pemesanan dan RabbitMQ

```bash
cd ~/jackrabbit-coffee-bar/layanan-pemesanan
vendor/bin/sail up -d
vendor/bin/sail ps
```

## 23.2 Terminal 2 — Inventori

```bash
cd ~/jackrabbit-coffee-bar/layanan-inventori
vendor/bin/sail up -d
vendor/bin/sail ps
```

Buat menu demo:

```bash
vendor/bin/sail artisan tinker --execute="App\Models\Menu::query()->updateOrCreate(['kode_menu' => 'MNU-DEMO-001'], ['nama_menu' => 'Cafe Latte Demo', 'harga_satuan' => 25000, 'stok' => 10]);"
```

Periksa:

```bash
curl --silent --show-error \
  --header "Accept: application/json" \
  http://localhost:8081/api/v1/menu/MNU-DEMO-001
```

## 23.3 Terminal 3 — Operasional dan Consumer

```bash
cd ~/jackrabbit-coffee-bar/layanan-operasional
vendor/bin/sail up -d
vendor/bin/sail ps
docker compose logs --tail=20 rabbitmq-consumer
```

## 23.4 Buat Pesanan Demo melalui REST API

```bash
curl -i --request POST \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{
    "kanal": "uji-integrasi",
    "nama_pelanggan": "Pelanggan Demo",
    "items": [
      {
        "kode_menu": "MNU-DEMO-001",
        "nama_menu": "Cafe Latte Demo",
        "jumlah": 2,
        "harga_satuan": 25000
      }
    ]
  }' \
  http://localhost:8080/api/v1/pesanan
```

Catat `kode_pesanan` dari response.

## 23.5 Konfirmasi Pesanan

Ganti `KODE_PESANAN`:

```bash
curl -i --request POST \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  http://localhost:8080/api/v1/pesanan/KODE_PESANAN/konfirmasi
```

Hasil yang diharapkan:

1. status pesanan berubah menjadi `dikonfirmasi`;
2. stok `MNU-DEMO-001` berkurang dari 10 menjadi 8;
3. event `PesananDikonfirmasi` diterbitkan;
4. consumer menerima event;
5. data muncul pada Layanan Operasional.

## 23.6 Periksa Stok Inventori

```bash
curl --silent --show-error \
  --header "Accept: application/json" \
  http://localhost:8081/api/v1/menu/MNU-DEMO-001
```

## 23.7 Periksa Log Consumer

```bash
cd ~/jackrabbit-coffee-bar/layanan-operasional
docker compose logs --tail=30 rabbitmq-consumer
```

Log akan memuat:

```text
Pesanan KODE_PESANAN diterima.
```

## 23.8 Periksa Data Operasional

```bash
curl --silent --show-error \
  --header "Accept: application/json" \
  http://localhost:8082/api/v1/pesanan/KODE_PESANAN
```

## 23.9 Perbarui Status Operasional

```bash
curl --silent --show-error --request PATCH \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{"status_operasional":"sedang_diproses"}' \
  http://localhost:8082/api/v1/pesanan/KODE_PESANAN/status
```

## 23.10 Demonstrasikan Problem Details

```bash
curl -i --request POST \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{}' \
  http://localhost:8080/api/v1/pesanan
```

Hasil yang diharapkan adalah `422` dengan Content-Type `application/problem+json`.

---

# 24. Idempotensi

## 24.1 Idempotensi Pengurangan Stok

Inventori menyimpan riwayat berdasarkan `kode_pesanan` dan fingerprint payload.

- request identik dikirim ulang: respons sukses tanpa mengurangi stok kembali;
- kode pesanan sama dengan payload berbeda: `409 Konflik idempotensi`.

## 24.2 Idempotensi Consumer

Operasional menggunakan `event_id` sebagai identitas unik. Event yang sama tidak menghasilkan data operasional duplikat.

Idempotensi diperlukan karena komunikasi jaringan dan message broker dapat mengirim ulang request atau event.

---

# 25. Mematikan Proyek

Matikan Operasional terlebih dahulu agar consumer berhenti sebelum RabbitMQ.

## 25.1 Operasional

```bash
cd ~/jackrabbit-coffee-bar/layanan-operasional
vendor/bin/sail down
```

## 25.2 Inventori

```bash
cd ~/jackrabbit-coffee-bar/layanan-inventori
vendor/bin/sail down
```

## 25.3 Pemesanan dan RabbitMQ

```bash
cd ~/jackrabbit-coffee-bar/layanan-pemesanan
vendor/bin/sail down
```

Jangan menggunakan:

```bash
vendor/bin/sail down -v
```

kecuali memang ingin menghapus volume MySQL dan data RabbitMQ.

---

# 26. Troubleshooting

## 26.1 Docker Tidak Ditemukan di WSL

Jika muncul:

```text
The command 'docker' could not be found in this WSL 2 distro.
```

Aktifkan:

```text
Docker Desktop
→ Settings
→ Resources
→ WSL Integration
→ Ubuntu
```

Tutup dan buka kembali Ubuntu Terminal, lalu jalankan:

```bash
docker version
```

## 26.2 MySQL Masih health: starting

Tunggu 30–60 detik:

```bash
vendor/bin/sail ps
```

Jalankan migration setelah MySQL `healthy`.

## 26.3 RabbitMQ Masih health: starting

```bash
cd ~/jackrabbit-coffee-bar/layanan-pemesanan
vendor/bin/sail ps
```

Tunggu sampai RabbitMQ `healthy` sebelum menyalakan atau me-restart consumer.

## 26.4 Consumer Broken Pipe

Jika log memuat `Broken pipe or closed connection`:

```bash
cd ~/jackrabbit-coffee-bar/layanan-operasional
docker compose restart rabbitmq-consumer
docker compose logs --tail=30 rabbitmq-consumer
```

## 26.5 Configuration Cache Lama

```bash
vendor/bin/sail artisan config:clear
```

## 26.6 Port Sudah Digunakan

Periksa container aktif:

```bash
docker ps
```

Pastikan tidak ada proyek lain menggunakan port `8080`, `8081`, `8082`, `3306`, `3307`, `3308`, `5173`, `5174`, `5175`, `5672`, atau `15672`.

## 26.7 Migration Belum Dijalankan

```bash
vendor/bin/sail artisan migrate --no-interaction
```

## 26.8 OpenAPI Tidak Menemukan Ruleset

Jalankan validasi melalui script root:

```bash
cd ~/jackrabbit-coffee-bar
npm run lint:openapi
```

## 26.9 AsyncAPI Tidak Dapat Menjalankan Docker

Pastikan Docker Desktop dan integrasi WSL aktif:

```bash
docker version
npm run validate:asyncapi
```

---

# 27. Pembagian Tugas

| Anggota | Tanggung jawab dan kontribusi |
| --- | --- |
| Ariya Narendra Ardi Alfirdaus | Koordinasi proyek, implementasi awal layanan, integrasi REST Pemesanan–Inventori, integrasi RabbitMQ, pengujian antarlayanan, dan pengelolaan repository |
| Melanie Priatna | Dokumentasi Docker dan Laravel Sail, panduan instalasi tiga layanan dari nol, urutan startup dan shutdown, alur demo, pemeriksaan layanan, dan troubleshooting |
| Minarianty Ismail | Penyempurnaan metadata kontrak OpenAPI Inventori dan Operasional, validasi Spectral, serta pemeriksaan konsistensi dokumentasi kontrak |
| Tia Anggraeni Sutisna | Review kontrak OpenAPI dan AsyncAPI, pemeriksaan endpoint dan Problem Details, validasi kontrak, serta dokumentasi hasil review |
| Seluruh anggota | Menjalankan Docker, migration, feature test, validasi kontrak, dan pengujian integrasi secara bergantian |

---

# 28. Status Implementasi Checkpoint 1

| Komponen | Status |
| --- | --- |
| Analisis persoalan integrasi | Selesai |
| Diagram tiga layanan | Selesai |
| Layanan Pemesanan | Berjalan |
| Layanan Inventori | Berjalan |
| Layanan Operasional | Berjalan |
| Database terpisah per layanan | Berjalan |
| Integrasi REST Pemesanan–Inventori | Berjalan |
| RabbitMQ publisher | Berjalan |
| RabbitMQ consumer otomatis | Berjalan |
| Event `PesananDikonfirmasi` | Berjalan |
| Idempotensi pengurangan stok | Berjalan |
| Idempotensi consumer | Berjalan |
| Problem Details | Berjalan |
| OpenAPI Pemesanan | Selesai |
| OpenAPI Inventori | Selesai |
| OpenAPI Operasional | Selesai |
| AsyncAPI | Selesai |
| Validasi Spectral | Lulus |
| Validasi AsyncAPI | Lulus |
| Feature test Pemesanan | PASS |
| Feature test Inventori | PASS |
| Feature test Operasional | PASS |
| Pengujian end-to-end | Berhasil |

---

# 29. Keterbatasan dan Pengembangan Berikutnya

Keterbatasan Checkpoint 1:

- belum terhubung langsung ke Gojek, GrabFood, ShopeeFood, dan Luna iPad;
- belum mempunyai antarmuka pengguna khusus;
- belum menerapkan autentikasi dan otorisasi;
- belum menggunakan transactional outbox;
- belum mempunyai dead-letter queue;
- belum mempunyai strategi retry terukur;
- belum mempunyai monitoring dan logging terpusat;
- masih dijalankan pada lingkungan lokal.

Pengembangan berikutnya:

- transactional outbox dan publisher terpisah;
- retry dan dead-letter queue;
- autentikasi dan otorisasi berbasis peran;
- antarmuka kasir, inventori, dan operasional;
- continuous integration;
- centralized logging dan monitoring;
- deployment pada cluster atau cloud;
- pengujian konkurensi dan pemulihan kegagalan.

---

# 30. Alur Kontribusi Git

Setiap anggota bekerja pada branch terpisah.

```bash
git switch main
git pull --ff-only origin main
git switch -c nama-branch
```

Periksa perubahan:

```bash
git status --short
git diff --check
git diff --stat
```

Commit dan push:

```bash
git add .
git commit -m "jenis: deskripsi perubahan"
git push -u origin nama-branch
```

Branch kemudian diajukan melalui pull request dan diperiksa sebelum digabungkan ke `main`.

---

# 31. Dokumentasi Tambahan

- [Panduan Menjalankan Proyek](docs/menjalankan-proyek.md)
- [Hasil Review Kontrak API](docs/review-openapi.md)
- [Kontrak OpenAPI Pemesanan](contracts/pemesanan/openapi.yaml)
- [Kontrak OpenAPI Inventori](contracts/inventori/openapi.yaml)
- [Kontrak OpenAPI Operasional](contracts/operasional/openapi.yaml)
- [Kontrak AsyncAPI](contracts/pemesanan/asyncapi.yaml)

---

# 32. Kesimpulan

Jackrabbit Coffee Bar menerapkan komunikasi sinkron dan asinkron pada tiga layanan dengan tanggung jawab serta kepemilikan data yang terpisah.

REST API digunakan antara Layanan Pemesanan dan Layanan Inventori karena proses konfirmasi membutuhkan hasil pengurangan stok secara langsung. RabbitMQ digunakan antara Layanan Pemesanan dan Layanan Operasional agar proses operasional tidak harus ditunggu oleh request konfirmasi.

Layanan Inventori menerapkan transaksi dan idempotensi untuk menjaga konsistensi stok. Layanan Operasional menerapkan idempotensi berdasarkan `event_id` agar pesan yang dikirim ulang tidak menghasilkan data duplikat.

Kontrak REST didokumentasikan menggunakan OpenAPI, sedangkan kontrak event didokumentasikan menggunakan AsyncAPI. Seluruh layanan mempunyai feature test dan telah diuji dalam satu alur integrasi end-to-end.

---

# Repository

https://github.com/ariyanarendra/jackrabbit-coffee-bar
