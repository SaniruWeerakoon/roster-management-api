<?php

namespace App\Export;

use App\Models\Roster;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RosterExport implements FromArray, WithTitle, WithEvents, WithStyles
{
    use Exportable;

    public array $roster;

    public function array(): array
    {
        // Enabled shift types for this roster (same as UI columns)
        $shiftTypes = collect($this->roster['shiftTypes']);

        // People map
        $peopleById = collect($this->roster['people'])->mapWithKeys(function ($item) {
            return [(int)$item['id'] => $item['code']];
        });
        // Build cell map: date|shiftTypeId => [personCode...]
        $assignments = $this->roster['assignments'];

        $cellMap = []; // key => array of person codes
        foreach ($assignments as $a) {
            $date = Carbon::parse($a['date'])->toDateString(); // YYYY-MM-DD
            $key = $date . '|' . (int)$a['shift_type_id'];
            $code = $peopleById[(int)$a['person_id']] ?? null;
            if (!$code) continue;
            $cellMap[$key] ??= [];
            $cellMap[$key][] = $code;
        }

        // Sort codes inside each cell for stable output
        foreach ($cellMap as $k => $codes) {
            sort($codes, SORT_NATURAL);
            $cellMap[$k] = $codes;
        }

        // Header row: Day + shift codes
        $header = array_merge(['', ''], $shiftTypes->pluck('code')->all());

        // Build day rows (entire roster month)
        $monthStart = Carbon::parse($this->roster['roster']['month'])->startOfMonth();
        $monthEnd = (clone $monthStart)->endOfMonth();

        $rows[] = ['DUTY ROSTER OF MEDICAL OFFICERS FOR ' . $monthStart->format('F Y')];
        $rows[] = $header;

        $d = $monthStart->copy();
        while ($d->lte($monthEnd)) {
            $dateStr = $d->toDateString();      // YYYY-MM-DD
            $row = [$d->format('j'), $d->format('D')];

            foreach ($shiftTypes as $st) {
                $k = $dateStr . '|' . (int)$st['id'];
                $codes = $cellMap[$k] ?? [];
                $row[] = implode(' ', $codes); // people in cell
            }

            $rows[] = $row;
            $d->addDay();
        }

        $this->roster['rows'] = $rows;
        return $rows;
    }

    public function title(): string
    {
        return $this->roster['roster']['name'] ?? 'Roster';
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $rows = $this->roster['rows'];
                // merge first cell
                $colCount = isset($rows[1]) ? count($rows[1]) : 1;
                $lastColIndex = max(1, $colCount); // at least 1
                $lastColLetter = Coordinate::stringFromColumnIndex($lastColIndex);
                $sheet->mergeCells("A1:{$lastColLetter}1");
                $sheet->getRowDimension(1)->setRowHeight(22);

                $sheet->getStyle("A1:{$lastColLetter}2")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);


                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle("A3:B$highestRow")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                for ($r = 3; $r <= $highestRow; $r++) {
                    $dayVal = (string)$sheet->getCell("B$r")->getValue();
                    if (str_contains($dayVal, 'Sat')) {
                        $sheet->getStyle($r)
                            ->getFont()
                            ->getColor()
                            ->setARGB('FF006400'); // dark green
                    } elseif (str_contains($dayVal, 'Sun')) {
                        $sheet->getStyle($r)
                            ->getFont()
                            ->getColor()
                            ->setARGB('FFFF0000'); // red
                    }
                }

                $sheet->getStyle("B3:{$lastColLetter}{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_DISTRIBUTED);

                $sheet->getStyle("A1:{$lastColLetter}{$highestRow}")
                    ->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                    ]);


                $pxToWidth = function (int $px): float {
                    return max(0.0, round(($px - 5) / 7, 2));
                };

                $pxWidths = [36, 41, 65, 204, 167, 121, 90];

                foreach ($pxWidths as $i => $px) {
                    $colIndex = $i + 1; // A = 1
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->getColumnDimension($colLetter)->setWidth($pxToWidth($px));
                }
            },
        ];
    }


    public function styles(Worksheet $sheet)
    {
        return [];
    }
}
