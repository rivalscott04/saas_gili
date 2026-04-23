# Backend Rules & Requirement Mapping (Laravel 12)

Dokumen ini merangkum kebutuhan backend berdasarkan frontend yang ada saat ini, plus aturan dasar implementasi.

## 1) Scope dari Frontend Saat Ini

Frontend punya 4 area utama:
- Dashboard (`/`)
- Bookings (`/bookings`, `/bookings/:id`)
- Chat (`/chat`, `/chat/:bookingId`)
- Chat Templates (`/templates`)

## 2) Domain Entity yang Dibutuhkan

Minimal entity backend:
- `bookings`
- `chat_messages`
- `chat_templates`
- `users` (operator/admin)
- `guides` (opsional dipisah; jika belum bisa string dulu lalu migrate)
- `tours` (opsional dipisah; jika belum bisa string dulu lalu migrate)

Relasi minimal:
- `Booking` hasMany `ChatMessage`
- `ChatMessage` belongsTo `Booking`
- `ChatTemplate` belongsTo `User` (creator) [opsional tapi direkomendasikan]
- `Booking` belongsTo `User` (operator PIC) [opsional]

## 3) Kebutuhan API Berdasarkan Frontend

### Dashboard
- `GET /api/v1/dashboard/summary`
  - return:
    - total bookings
    - upcoming tours
    - guests expected
    - needs attention (start < 24 jam)
- `GET /api/v1/dashboard/urgent-bookings?limit=3`
- `GET /api/v1/dashboard/recent-bookings?limit=6`

### Bookings
- `GET /api/v1/bookings`
  - query params:
    - `search` (tour/customer)
    - `status` (`confirmed|pending|cancelled`)
    - `page`, `per_page`
    - `sort_by`, `sort_dir`
- `GET /api/v1/bookings/{id}`
- `PATCH /api/v1/bookings/{id}/status`
  - payload: `{ "status": "confirmed|pending|cancelled" }`
- (opsional) `POST /api/v1/bookings`
- (opsional) `PUT /api/v1/bookings/{id}`

### Chat
- `GET /api/v1/chats`
  - list chat threads (unique by booking) untuk panel kiri
  - support `search` by customer name
  - include last message per thread
- `GET /api/v1/chats/{bookingId}/messages`
  - params: `page`, `per_page`
- `POST /api/v1/chats/{bookingId}/messages`
  - payload: `{ "message": "..." }`
  - source default `whatsapp` (sesuai UI)

### Chat Templates
- `GET /api/v1/chat-templates`
- `POST /api/v1/chat-templates`
- `PUT /api/v1/chat-templates/{id}`
- `DELETE /api/v1/chat-templates/{id}`

## 4) Kontrak Data Minimum (Agar Frontend Tidak Pecah)

### Booking
- `id`
- `tour_name`
- `customer_name`
- `customer_email`
- `customer_phone`
- `tour_start_at` (ISO datetime, UTC)
- `location`
- `guide_name`
- `status` (`confirmed|pending|cancelled`)
- `participants`
- `notes`

Catatan:
- Frontend saat ini punya `date` + `time`; backend lebih aman simpan sebagai satu kolom `tour_start_at`.
- Di layer response API, bisa expose format kompatibel:
  - `date` (ISO string)
  - `time` (formatted lokal, jika masih dibutuhkan UI lama)

### ChatMessage
- `id`
- `booking_id`
- `sender` (`customer|operator`)
- `message`
- `source` (`whatsapp|web`)
- `timestamp` (ISO datetime)

### ChatTemplate
- `id`
- `name`
- `content`
- `created_at`
- `updated_at`

## 5) Rule Dasar Arsitektur Backend (WAJIB)

1. Controller hanya request-response  
   - Controller hanya:
     - validasi via FormRequest
     - panggil service
     - return API Resource / JSON response
   - Tidak boleh ada business logic kompleks di controller.

2. Business logic di Service Layer  
   - Semua logic domain ditaruh di `app/Services/*`.
   - Service boleh koordinasi antar repository/query object.
   - Logic seperti: confirm booking, ambil dashboard summary, build chat thread list, render template vars.

3. Query optimization dan anti N+1 (WAJIB)  
   - Selalu eager load relasi yang dipakai (`with`, `loadMissing`).
   - Gunakan `withCount` untuk aggregate count.
   - Untuk list + relasi kompleks, pakai query object/repository khusus.
   - Hindari loop yang query di dalam loop.
   - Endpoint list wajib pagination.
   - Tambahkan index DB untuk kolom filter/sort:
     - `bookings.status`
     - `bookings.tour_start_at`
     - `bookings.customer_name`
     - `chat_messages.booking_id`
     - `chat_messages.created_at`

## 6) Struktur Folder Laravel yang Disarankan

- `app/Http/Controllers/Api/V1/*Controller.php`
- `app/Http/Requests/*`
- `app/Http/Resources/*`
- `app/Services/*Service.php`
- `app/Repositories/*Repository.php` (opsional tapi disarankan)
- `routes/api.php` (group prefix `/api/v1`)

## 7) Standar Response API

Gunakan format konsisten:
- sukses:
  - `data`
  - `meta` (pagination/info tambahan jika perlu)
- error:
  - `message`
  - `errors` (validasi)

Contoh:
- list endpoint return pagination Laravel standard + resource collection.
- detail endpoint return single resource.

## 8) Kebutuhan Non-Fungsional Awal

- Auth: gunakan Sanctum untuk operator/admin.
- Logging: catat action penting (confirm booking, kirim pesan).
- Validation: semua payload mutasi wajib FormRequest.
- Timezone: simpan UTC di DB, convert di response bila perlu.
- Test minimal:
  - Feature test untuk endpoint utama.
  - Unit test untuk service logic.

## 9) Prioritas Implementasi (MVP)

1. Booking list + search + status filter + pagination
2. Booking detail + update status (confirm)
3. Chat thread list + message list + send message
4. Chat templates CRUD
5. Dashboard summary/urgent/recent

---

Jika nanti frontend berubah ke React Query full-API, endpoint di dokumen ini sudah siap jadi baseline kontrak backend Laravel 12.
