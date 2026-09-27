<?php

namespace App\Exports;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TicketsExport implements FromQuery, WithMapping, WithCustomStartCell, WithTitle, WithEvents
{
    public function __construct(private array $filters = []) {}

    public function query()
    {
        return Ticket::with('user')
            ->filterStatus($this->filters['status'] ?? null)
            ->filterKategori($this->filters['kategori'] ?? null)
            ->filterPrioritas($this->filters['prioritas'] ?? null)
            ->filterTanggal($this->filters['date_from'] ?? null, $this->filters['date_to'] ?? null)
            ->filterSearch($this->filters['search'] ?? null)
            ->oldest('tanggal_kejadian');
    }

    public function title(): string
    {
        return now()->format('m-Y');
    }

    public function startCell(): string
    {
        return 'B4';
    }

    public function map($ticket): array
    {
        return [
            $ticket->nomor_tiket,
            $ticket->tanggal_kejadian ? $ticket->tanggal_kejadian->format('d/m/y') : '',
            $ticket->nama_pelapor ?? '',
            $ticket->divisi_toko ?? '',
            $ticket->kategori ?? '',
            $ticket->prioritas ?? '',
            $ticket->deskripsi_kendala ?? '',
            $ticket->dampak_operasional ?? '',
            '', // Bukti
            $ticket->pic ?? '',
            $ticket->status ?? '',
            $ticket->tindakan_dilakukan ?? '',
            $ticket->tanggal_penyelesaian ? $ticket->tanggal_penyelesaian->format('d/m/y') : '',
            $ticket->solusi_diberikan ?? '',
            $ticket->root_cause ?? '',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = max(4, $sheet->getHighestRow());

                // Set Group Headers (Row 2)
                $sheet->setCellValue('B2', 'Informasi Ticket');
                $sheet->setCellValue('F2', 'Detail Kendala');
                $sheet->setCellValue('K2', 'Penanganan');
                $sheet->setCellValue('N2', 'Penyelesaian');

                // Merge Cells for Group Headers
                $sheet->mergeCells('B2:E2');
                $sheet->mergeCells('F2:J2');
                $sheet->mergeCells('K2:M2');
                $sheet->mergeCells('N2:P2');

                // Set Column Headers (Row 3)
                $headers = [
                    'B3' => 'Nomor Ticket',
                    'C3' => 'Tanggal',
                    'D3' => 'Nama Pelapor',
                    'E3' => 'Divisi/Toko',
                    'F3' => 'Kategori',
                    'G3' => 'Tingkat Prioritas',
                    'H3' => 'Deskripsi Kendala',
                    'I3' => 'Dampak Operasional',
                    'J3' => 'Bukti',
                    'K3' => 'PIC',
                    'L3' => 'Status',
                    'M3' => 'Tindakan yg Dilakukan',
                    'N3' => 'Tanggal ',
                    'O3' => 'Solusi yg Diberikan',
                    'P3' => 'Root Cause (Penyebab)',
                ];

                foreach ($headers as $cell => $value) {
                    $sheet->setCellValue($cell, $value);
                }

                // Global Font for all content B2:P{highestRow}
                $sheet->getStyle("B2:P{$highestRow}")->getFont()->setName('Cambria')->setSize(12);

                // Style Row 2 (Group Headers)
                $sheet->getStyle('B2:P2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'name' => 'Cambria',
                        'size' => 12,
                        'color' => ['argb' => 'FF000000'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFD9EAD3'],
                    ],
                ]);

                // Style Row 3 (Column Headers)
                $sheet->getStyle('B3:P3')->applyFromArray([
                    'font' => [
                        'bold' => false,
                        'name' => 'Cambria',
                        'size' => 12,
                        'color' => ['argb' => 'FF000000'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Style Data Rows (B4:P{highestRow})
                $sheet->getStyle("B4:P{$highestRow}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Enable wrap text for long text columns
                $sheet->getStyle("H4:I{$highestRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("M4:M{$highestRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("O4:P{$highestRow}")->getAlignment()->setWrapText(true);

                // Thin black borders for B2:P{highestRow}
                $sheet->getStyle("B2:P{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                // Set Exact Column Widths as in "Form Ticket IT as is.xlsx"
                $columnWidths = [
                    'A' => 4,
                    'B' => 16.25,
                    'C' => 12,
                    'D' => 20.38,
                    'E' => 17.25,
                    'F' => 14.5,
                    'G' => 15.38,
                    'H' => 46,
                    'I' => 44.63,
                    'J' => 15.75,
                    'K' => 12,
                    'L' => 14.25,
                    'M' => 31.13,
                    'N' => 12,
                    'O' => 37.38,
                    'P' => 36.88,
                ];

                foreach ($columnWidths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }
            },
        ];
    }
}
