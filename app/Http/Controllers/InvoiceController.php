<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('penghuni', 'payment.sewa.kamar')->latest()->get();

        return view('invoices.index', compact('invoices'));
    }

    public function reconciliation(Request $request)
    {
        $filters = $request->validate([
            'kategori' => ['nullable', 'in:legacy,tanpa_invoice,tidak_sesuai'],
            'bulan' => ['nullable', 'date_format:Y-m'],
        ]);
        $kategori = $filters['kategori'] ?? 'legacy';
        $legacy = Invoice::query()->whereNull('payment_id')->where('keterangan', 'like', 'AUTO:%');
        $missing = Pembayaran::query()->whereDoesntHave('invoice');
        $mismatch = Invoice::query()->whereHas('payment', function ($payments): void {
            $payments->where(function ($differences): void {
                $differences->whereColumn('pembayarans.jumlah', '<>', 'invoices.jumlah_tagihan')
                    ->orWhereColumn('pembayarans.periode', '<>', 'invoices.periode')
                    ->orWhereHas('sewa', fn ($leases) => $leases->whereColumn('sewas.penghuni_id', '<>', 'invoices.penghuni_id'))
                    ->orWhere(fn ($status) => $status->where('pembayarans.status', 'lunas')->where('invoices.status', '<>', 'lunas'))
                    ->orWhere(fn ($status) => $status->where('pembayarans.status', '<>', 'lunas')->where('invoices.status', 'lunas'));
            });
        });
        $bulan = $filters['bulan'] ?? null;
        if ($bulan) {
            $start = CarbonImmutable::createFromFormat('!Y-m', $bulan)->startOfMonth();
            $end = $start->addMonth()->toDateString();
            $start = $start->toDateString();
            $legacy->where('periode', '>=', $start)->where('periode', '<', $end);
            $missing->where('periode', '>=', $start)->where('periode', '<', $end);
            // A mismatch belongs to either affected month, including a shifted invoice date.
            $mismatch->where(function ($affected) use ($start, $end): void {
                $affected->where(fn ($invoice) => $invoice->where('periode', '>=', $start)->where('periode', '<', $end))
                    ->orWhereHas('payment', fn ($payment) => $payment->where('periode', '>=', $start)->where('periode', '<', $end));
            });
        }
        $counts = ['legacy' => (clone $legacy)->count(), 'tanpa_invoice' => (clone $missing)->count(), 'tidak_sesuai' => (clone $mismatch)->count()];
        $records = match ($kategori) {
            'tanpa_invoice' => $missing->with('sewa.penghuni', 'sewa.kamar')->orderBy('id')->paginate(25),
            'tidak_sesuai' => $mismatch->with('penghuni', 'payment.sewa.kamar')->orderBy('id')->paginate(25),
            default => $legacy->with('penghuni')->orderBy('id')->paginate(25),
        };
        $records->withQueryString();

        return response()->view('invoices.reconciliation', compact('kategori', 'bulan', 'counts', 'records'))
            ->header('Cache-Control', 'no-store, private');
    }

    public function create()
    {
        return view('invoices.form', [
            'invoice' => new Invoice,
            'penghunis' => Penghuni::orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'penghuni_id' => ['required', 'exists:penghunis,id'],
            'periode' => ['required', 'date'],
            'jatuh_tempo' => ['required', 'date', 'after_or_equal:periode'],
            'jumlah_tagihan' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,terkirim,lunas'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $validated['nomor_invoice'] = 'INV-KOS-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        if ($validated['status'] === 'terkirim') {
            $validated['tanggal_kirim'] = now();
        }

        Invoice::create($validated);

        return redirect()->route('invoices.index')->with('success', 'Invoice berhasil dibuat.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('penghuni', 'payment.sewa.kamar');

        return response()->view('invoices.show', compact('invoice'))
            ->header('Cache-Control', 'no-store, private');
    }

    public function edit(Invoice $invoice)
    {
        return view('invoices.form', [
            'invoice' => $invoice,
            'penghunis' => Penghuni::orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->assertManuallyManaged($invoice);
        $validated = $request->validate([
            'penghuni_id' => ['required', 'exists:penghunis,id'],
            'periode' => ['required', 'date'],
            'jatuh_tempo' => ['required', 'date', 'after_or_equal:periode'],
            'jumlah_tagihan' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,terkirim,lunas'],
            'keterangan' => ['nullable', 'string'],
        ]);

        if ($validated['status'] === 'terkirim' && $invoice->tanggal_kirim === null) {
            $validated['tanggal_kirim'] = now();
        }

        $invoice->update($validated);

        return redirect()->route('invoices.index')->with('success', 'Invoice berhasil diperbarui.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->assertManuallyManaged($invoice);
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice berhasil dihapus.');
    }

    public function send(Invoice $invoice)
    {
        $invoice->update([
            'status' => $invoice->status === 'lunas' ? 'lunas' : 'terkirim',
            'tanggal_kirim' => now(),
        ]);

        return redirect()->route('invoices.index')->with('success', 'Invoice ditandai sudah dikirim.');
    }

    public function refreshFromPayments()
    {
        $created = 0;

        Pembayaran::with('sewa', 'invoice')
            ->where('status', 'lunas')
            ->orderBy('id')
            ->chunkById(200, function ($pembayarans) use (&$created) {
                foreach ($pembayarans as $pembayaran) {
                    $hadInvoice = $pembayaran->invoice !== null;
                    $pembayaran->syncInvoiceFromPayment();
                    $created += $hadInvoice ? 0 : 1;
                }
            });

        $message = $created > 0
            ? "Refresh berhasil. {$created} invoice yang belum tercetak sudah dibuat otomatis."
            : 'Refresh selesai. Tidak ada invoice yang perlu dibuat.';

        return redirect()->route('invoices.index')->with('success', $message);
    }

    private function assertManuallyManaged(Invoice $invoice): void
    {
        if ($invoice->payment_id !== null) {
            throw ValidationException::withMessages([
                'invoice' => 'Invoice otomatis mengikuti pembayaran asal dan tidak dapat diubah atau dihapus secara terpisah.',
            ]);
        }
    }
}
