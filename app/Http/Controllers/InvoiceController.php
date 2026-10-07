<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Pembayaran;
use App\Models\Penghuni;
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
        return redirect()->route('invoices.edit', $invoice);
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
