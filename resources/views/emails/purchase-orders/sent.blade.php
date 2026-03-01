# Purchase Order Baru

Hai,

Anda menerima Purchase Order (PO) baru dari Koperasi Harmoni.

## Detail Purchase Order

- **Nomor PO:** {{ $poNumber }}
- **Tanggal PO:** {{ $poDate }}
- **Total Estimasi:** Rp {{ $estimatedTotal }}
- **Tanggal Pengiriman:** {{ $estimatedDeliveryDate }}

Jumlah Item: {{ $itemsCount }}

@if($notes)
**Catatan:** {{ $notes }}
@endif

Silakan login ke sistem untuk melihat detail PO dan melakukan konfirmasi.

Terima kasih!

---
*Koperasi Harmoni - Sistem Manajemen Koperasi*
