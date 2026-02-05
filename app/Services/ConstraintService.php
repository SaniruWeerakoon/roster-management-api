<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AvailabilityBlock;
use App\Models\Roster;
use Illuminate\Support\Collection;

class ConstraintService
{
    public function validateRoster(Roster $roster): array
    {
        $enabledShiftTypes = $roster->shiftTypes()
            ->get(['shift_types.id', 'shift_types.code'])
            ->keyBy('code');

        $codeToId = $enabledShiftTypes->map(fn($st) => (int)$st->id)->all();
        $enabledCodes = array_fill_keys(array_keys($codeToId), true);

        $constraints = $roster->constraints()
            ->get(['key', 'value'])
            ->keyBy('key')
            ->map(fn($c) => is_array($c->value) ? $c->value : [])
            ->all();

        $enabledIds = array_values($codeToId);
        $assignments = Assignment::query()
            ->join('shift_types', 'shift_types.id', '=', 'assignments.shift_type_id')
            ->where('assignments.roster_id', $roster->id)
            ->when(count($enabledIds) > 0, fn($q) => $q->whereIn('assignments.shift_type_id', $enabledIds))
            ->get([
                'assignments.date as date',
                'assignments.person_id as person_id',
                'assignments.shift_type_id as shift_type_id',
                'shift_types.code as shift_code',
                'shift_types.weight as weight',
            ]);

        $violations = [];

        // Rule 1) Availability conflicts
        $violations = array_merge(
            $violations,
            $this->ruleAvailabilityConflicts($roster, $assignments, $constraints['availability_conflicts'] ?? [])
        );

        // Rule 2) Max monthly total shifts per person
        $violations = array_merge(
            $violations,
            $this->ruleMaxTotalShifts($assignments, $constraints['max_total_shifts'] ?? [])
        );

        // Rule 3) Incompatible shift pairs on same day
        $violations = array_merge(
            $violations,
            $this->ruleIncompatibleSameDay($assignments, $constraints['incompatible_same_day'] ?? [], $enabledCodes)
        );

        // Optional: return basic stats useful for UI/debug
        $stats = [
            'assignments_count' => $assignments->count(),
        ];

        return [
            'violations' => $violations,
            'stats' => $stats,
        ];
    }

    private function ruleAvailabilityConflicts(Roster $roster, Collection $assignments, array $cfg): array
    {
        // Default severity
        $severity = $cfg['severity'] ?? 'error';

        // Load all availability blocks for people referenced in this roster assignments
        $personIds = $assignments->pluck('person_id')->unique()->values()->all();
        if (count($personIds) === 0) return [];

        $blocks = AvailabilityBlock::query()
            ->whereIn('person_id', $personIds)
            ->get(['person_id', 'date_from', 'date_to', 'reason']);

        if ($blocks->isEmpty()) return [];

        // Build a quick lookup: person_id => list of blocks
        $byPerson = $blocks->groupBy('person_id');

        $out = [];
        foreach ($assignments as $a) {
            $pid = (int)$a->person_id;
            if (!isset($byPerson[$pid])) continue;

            // $a->date may be Carbon (Eloquent hydration) - normalize to string
            $date = is_object($a->date) ? $a->date->toDateString() : (string)$a->date;

            foreach ($byPerson[$pid] as $b) {
                $from = $b->date_from;
                $to = $b->date_to;

                $fromStr = is_object($from) ? $from->toDateString() : (string)$from;
                $toStr = is_object($to) ? $to->toDateString() : (string)$to;

                if ($date >= $fromStr && $date <= $toStr) {
                    $reason = $b->reason ? " ($b->reason)" : "";
                    $out[] = [
                        'rule' => 'availability_conflicts',
                        'severity' => $severity,
                        'message' => "Assigned during unavailable period$reason.",
                        'cell' => [
                            'date' => $date,
                            'person_id' => $pid,
                            'shift_code' => (string)$a->shift_code,
                            'shift_type_id' => (int)$a->shift_type_id,
                        ],
                    ];
                    break; // one block match is enough
                }
            }
        }
        return $out;
    }

    private function ruleMaxTotalShifts(Collection $assignments, array $cfg): array
    {
        $max = isset($cfg['max']) ? (int)$cfg['max'] : null;
        if (!$max || $max <= 0) return [];

        $severity = $cfg['severity'] ?? 'warn';

        // Count per person
        $counts = [];
        foreach ($assignments as $a) {
            $pid = (int)$a->person_id;
            $counts[$pid] = ($counts[$pid] ?? 0) + 1;
        }

        $out = [];
        foreach ($counts as $pid => $count) {
            if ($count > $max) {
                $out[] = [
                    'rule' => 'max_total_shifts',
                    'severity' => $severity,
                    'message' => "Person has $count shifts (max $max).",
                    // No single cell is “the” culprit; UI can jump to person summary
                    'target' => [
                        'person_id' => (int)$pid,
                    ],
                    'meta' => [
                        'count' => $count,
                        'max' => $max,
                    ],
                ];
            }
        }

        return $out;
    }

    private function ruleIncompatibleSameDay(Collection $assignments, array $cfg, array $enabledCodes): array
    {
        $pairs = $cfg['pairs'] ?? [];
        if (!is_array($pairs) || count($pairs) === 0) return [];

        $severity = $cfg['severity'] ?? 'error';

        // Normalize allowed pairs: set["A|B"]=true and only keep those relevant to this roster
        $pairSet = [];
        foreach ($pairs as $p) {
            if (!is_array($p) || count($p) !== 2) continue;
            [$a, $b] = $p;
            $a = strtoupper(trim((string)$a));
            $b = strtoupper(trim((string)$b));
            if ($a === '' || $b === '') continue;

            // Skip if either shift code isn't enabled for this roster
            if (!isset($enabledCodes[$a]) || !isset($enabledCodes[$b])) continue;

            $key = $a < $b ? "$a|$b" : "$b|$a";
            $pairSet[$key] = true;
        }

        if (empty($pairSet)) return [];

        // Group assignments by (person, date)
        $byPersonDate = [];
        foreach ($assignments as $a) {
            $pid = (int)$a->person_id;
            $date = is_object($a->date) ? $a->date->toDateString() : (string)$a->date;
            $byPersonDate[$pid][$date][] = [
                'shift_code' => (string)$a->shift_code,
                'shift_type_id' => (int)$a->shift_type_id,
            ];
        }

        $out = [];
        foreach ($byPersonDate as $pid => $dates) {
            foreach ($dates as $date => $items) {
                if (count($items) < 2) continue;

                // Check all pairs within that day
                $codes = array_map(fn($x) => strtoupper($x['shift_code']), $items);
                $n = count($codes);

                for ($i = 0; $i < $n; $i++) {
                    for ($j = $i + 1; $j < $n; $j++) {
                        $a = $codes[$i];
                        $b = $codes[$j];
                        $key = $a < $b ? "$a|$b" : "$b|$a";
                        if (!isset($pairSet[$key])) continue;

                        // Find the original entries for targeted highlighting
                        $left = $items[$i];
                        $right = $items[$j];

                        $out[] = [
                            'rule' => 'incompatible_same_day',
                            'severity' => $severity,
                            'message' => "Incompatible shifts on same day: $a + $b.",
                            'cells' => [
                                [
                                    'date' => $date,
                                    'person_id' => (int)$pid,
                                    'shift_code' => $left['shift_code'],
                                    'shift_type_id' => $left['shift_type_id'],
                                ],
                                [
                                    'date' => $date,
                                    'person_id' => (int)$pid,
                                    'shift_code' => $right['shift_code'],
                                    'shift_type_id' => $right['shift_type_id'],
                                ],
                            ],
                        ];
                    }
                }
            }
        }

        return $out;
    }
}
