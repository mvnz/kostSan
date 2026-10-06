<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Berhasil</title>
    <style>
        body { margin: 0; font-family: "Segoe UI", Tahoma, sans-serif; background: #f5f7fb; color: #1f2a37; }
        .wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .card { background: #fff; border: 1px solid #e5eaf2; border-radius: 14px; padding: 28px; width: min(560px, 100%); box-shadow: 0 14px 36px rgba(20, 33, 61, 0.08); }
        h1 { margin: 0 0 8px; font-size: 1.4rem; }
        p { margin: 0; color: #526079; line-height: 1.6; }
        .badge { display: inline-block; margin-bottom: 12px; background: #e7f9ee; color: #067647; border: 1px solid #c9f2d9; border-radius: 999px; padding: 4px 10px; font-size: .8rem; font-weight: 600; }
        .payment-link {
            margin-top: 16px;
            padding: 12px;
            border: 1px dashed #b9ccff;
            border-radius: 10px;
            background: #f4f7ff;
        }
        .payment-link strong { display: block; margin-bottom: 6px; color: #1d3e9a; }
        .payment-link a { color: #224bc2; word-break: break-all; }
        .wa-status {
            margin-top: 10px;
            font-size: .9rem;
            color: #4f5f79;
        }
        .wa-status.ok { color: #067647; }
        .wa-status.fail { color: #9a3412; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <span class="badge">Berhasil</span>
            <h1>Pendaftaran penghuni berhasil dikirim</h1>
            <p>Data Anda sudah diterima pengelola kost. Link ini otomatis ditutup setelah pengiriman dan tidak bisa dipakai lagi.</p>

            @if(!empty($paymentLinkUrl))
                <div class="payment-link">
                    <strong>Link Pembayaran Sewa</strong>
                    <a href="{{ $paymentLinkUrl }}" target="_blank">{{ $paymentLinkUrl }}</a>
                    <div class="wa-status {{ !empty($waSent) ? 'ok' : 'fail' }}">
                        {{ !empty($waSent) ? 'Link pembayaran sudah dikirim ke WhatsApp.' : 'Link pembayaran belum terkirim ke WhatsApp. Silakan kirim manual menggunakan link di atas.' }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
