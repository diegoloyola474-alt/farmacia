<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class VentasExport implements FromCollection, WithHeadings, WithMapping, WithCustomStartCell, WithEvents, WithStyles, ShouldAutoSize
{
    public function __construct(
        private readonly Collection $sales,
        private readonly string $from,
        private readonly string $to,
    ) {}

    public function collection(): Collection
    {
        return $this->sales;
    }

    public function headings(): array
    {
        return [
            'N.º venta', 'Fecha', 'Cliente', 'Vendedor', 'Subtotal', 'IGV', 'Total',
        ];
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function map($sale): array
    {
        return [
            $sale->id,
            $sale->date->format('d/m/Y H:i'),
            $sale->customer->name,
            $sale->user->name,
            (float) $sale->total - (float) $sale->igv,
            (float) $sale->igv,
            (float) $sale->total,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A4:G4')->getFont()->setBold(true);
        $sheet->getStyle('A4:G4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('D9EAF7');

        return [];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();
            $sheet->mergeCells('A1:G1');
            $sheet->mergeCells('A2:G2');
            $sheet->setCellValue('A1', 'Farmacia San Juan Bautista - Reporte de ventas');
            $sheet->setCellValue('A2', "Periodo: {$this->from} al {$this->to}");
            $sheet->getStyle('A1:G1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A2:G2')->getFont()->setItalic(true);
        }];
    }
}
