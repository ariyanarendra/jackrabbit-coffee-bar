# Menjalankan Jackrabbit Coffee Bar

Panduan ini berisi urutan menyalakan, memeriksa, menguji, dan mematikan seluruh layanan setelah laptop dinyalakan kembali.

## Daftar layanan

| Layanan | URL | Database | Fungsi |
|---|---|---:|---|
| Pemesanan | http://localhost:8080 | MySQL `3306` | Mengelola pesanan |
| Inventori | http://localhost:8081 | MySQL `3307` | Mengelola menu dan stok |
| Operasional | http://localhost:8082 | MySQL `3308` | Menerima pesanan terkonfirmasi |
| RabbitMQ Management | http://localhost:15672 | AMQP `5672` | Memantau event dan antrean |

Login RabbitMQ lokal:

```text
Username: jackrabbit
Password: secret
```

Kredensial tersebut hanya untuk pengembangan lokal dan tidak boleh digunakan di production.

## Menyalakan proyek setelah laptop dinyalakan

### 1. Jalankan Docker Desktop

Buka Docker Desktop dan tunggu sampai Docker Engine sudah berjalan.

### 2. Buka proyek di VS Code

Buka Ubuntu/WSL Terminal, lalu jalankan:

```bash
cd ~/jackrabbit-coffee-bar
code .
```

Pastikan VS Code terhubung ke WSL.

### 3. Nyalakan Pemesanan dan RabbitMQ

Buka terminal pertama di VS Code:

```bash
cd ~/jackrabbit-coffee-bar/layanan-pemesanan
vendor/bin/sail up -d
vendor/bin/sail ps
```

Pastikan container berikut berstatus `Up` atau `healthy`:

```text
layanan-pemesanan-laravel.test-1
layanan-pemesanan-mysql-1
layanan-pemesanan-rabbitmq-1
```

### 4. Nyalakan Inventori

Buka terminal kedua di VS Code:

```bash
cd ~/jackrabbit-coffee-bar/layanan-inventori
vendor/bin/sail up -d
vendor/bin/sail ps
```

Pastikan Laravel berstatus `Up` dan MySQL berstatus `healthy`.

### 5. Nyalakan Operasional

Buka terminal ketiga di VS Code:

```bash
cd ~/jackrabbit-coffee-bar/layanan-operasional
vendor/bin/sail up -d
vendor/bin/sail ps
```

Pastikan container berikut aktif:

```text
layanan-operasional-laravel.test-1
layanan-operasional-mysql-1
layanan-operasional-rabbitmq-consumer-1
```

Consumer RabbitMQ berjalan otomatis. Command consumer tidak perlu dijalankan secara manual.

## Pemeriksaan cepat

Buka alamat berikut di browser:

```text
http://localhost:8080
http://localhost:8081
http://localhost:8082
http://localhost:15672
```

Periksa log consumer Operasional dengan:

```bash
cd ~/jackrabbit-coffee-bar/layanan-operasional
vendor/bin/sail logs --tail=30 rabbitmq-consumer
```

## Menjalankan tes

### Pemesanan

```bash
cd ~/jackrabbit-coffee-bar/layanan-pemesanan
vendor/bin/sail artisan test --compact
```

### Inventori

```bash
cd ~/jackrabbit-coffee-bar/layanan-inventori
vendor/bin/sail artisan test --compact
```

### Operasional

```bash
cd ~/jackrabbit-coffee-bar/layanan-operasional
vendor/bin/sail artisan test --compact
```

### Kontrak OpenAPI

```bash
cd ~/jackrabbit-coffee-bar

node_modules/.bin/spectral lint \
  --ruleset .spectral.yaml \
  contracts/pemesanan/openapi.yaml \
  contracts/inventori/openapi.yaml
```

Peringatan `info-contact` masih diperbolehkan selama data kontak resmi belum ditentukan. Hasil pemeriksaan tidak boleh memiliki severity `error`.

## Mematikan proyek

Matikan layanan sesuai urutan berikut agar consumer berhenti sebelum RabbitMQ.

### 1. Matikan Operasional

```bash
cd ~/jackrabbit-coffee-bar/layanan-operasional
vendor/bin/sail down
```

### 2. Matikan Inventori

```bash
cd ~/jackrabbit-coffee-bar/layanan-inventori
vendor/bin/sail down
```

### 3. Matikan Pemesanan dan RabbitMQ

```bash
cd ~/jackrabbit-coffee-bar/layanan-pemesanan
vendor/bin/sail down
```

Setelah semua container berhenti, Docker Desktop boleh ditutup.

Jangan gunakan perintah berikut kecuali memang ingin menghapus database:

```bash
vendor/bin/sail down -v
```

Opsi `-v` menghapus volume MySQL dan data RabbitMQ.

## Port yang digunakan

| Port | Pemilik |
|---:|---|
| `8080` | HTTP Pemesanan |
| `8081` | HTTP Inventori |
| `8082` | HTTP Operasional |
| `5173` | Vite Pemesanan |
| `5174` | Vite Inventori |
| `5175` | Vite Operasional |
| `3306` | MySQL Pemesanan |
| `3307` | MySQL Inventori |
| `3308` | MySQL Operasional |
| `5672` | RabbitMQ AMQP |
| `15672` | RabbitMQ Management |

## Alur integrasi

```text
Client
  |
  v
Layanan Pemesanan
  |
  +---- HTTP ----> Layanan Inventori
  |                 |
  |                 +---- Mengurangi stok secara atomik
  |                       dan idempoten
  |
  +---- RabbitMQ: PesananDikonfirmasi
                    |
                    v
             Layanan Operasional
                    |
                    +---- Menyimpan pesanan secara idempoten
```