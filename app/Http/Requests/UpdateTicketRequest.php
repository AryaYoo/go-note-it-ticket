<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nomor_tiket'         => 'required|string|max:30|regex:/^IT-\d{8}-\d{3}$/|unique:tickets,nomor_tiket,' . $this->ticket->id,
            'tanggal_kejadian'    => 'required|date',
            'nama_pelapor'        => 'required|string|max:150',
            'divisi_toko'         => 'required|string|max:150',
            'kategori'            => 'nullable|in:POS,Akun,Device,Lainnya',
            'prioritas'           => 'nullable|in:Low,Medium,High',
            'deskripsi_kendala'   => 'nullable|string',
            'dampak_operasional'  => 'nullable|string',
            'lampiran_gambar'     => 'nullable|array|max:3',
            'lampiran_gambar.*'   => 'image|mimes:jpg,jpeg,png,webp|max:10240',
            'pic'                 => 'nullable|string|max:150',
            'status'              => 'required|in:Open,Closed,Eskalasi',
            'tindakan_dilakukan'  => 'nullable|string',
            'tanggal_penyelesaian' => 'nullable|date',
            'solusi_diberikan'    => 'nullable|string',
            'root_cause'          => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'nomor_tiket.regex'  => 'Format nomor tiket harus IT-DDMMYYYY-XXX (contoh: IT-27092026-001)',
            'nomor_tiket.unique' => 'Nomor tiket sudah digunakan.',
        ];
    }
}
