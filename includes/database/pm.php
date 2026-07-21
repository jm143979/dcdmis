<?php
// includes/database/pm.php

// ========== CYCLES ==========

function pmCycles()
{
    return query("SELECT * FROM `pm_cycles` ORDER BY `date_start` DESC") ?: [];
}

function pmActiveCycle()
{
    return find("SELECT * FROM `pm_cycles` WHERE `status` = 'Active' ORDER BY `date_start` DESC LIMIT 1");
}

function pmCycle($id)
{
    return find("SELECT * FROM `pm_cycles` WHERE `id` = ?", [$id]);
}

function createPmCycle($title, $schoolYear, $dateStart, $dateEnd, $createdBy)
{
    return insert('pm_cycles', [
        'title' => $title,
        'school_year' => $schoolYear,
        'date_start' => $dateStart,
        'date_end' => $dateEnd,
        'created_by' => $createdBy
    ]);
}

function updatePmCycle($title, $schoolYear, $dateStart, $dateEnd, $status, $id)
{
    return update('pm_cycles', [
        'title' => $title,
        'school_year' => $schoolYear,
        'date_start' => $dateStart,
        'date_end' => $dateEnd,
        'status' => $status
    ], '`id` = ?', [$id]);
}

// ========== IPCRF ==========

function pmIpcrf($id)
{
    return find(
        "SELECT i.*, c.title AS cycle_title, c.school_year
        FROM `pm_ipcrf` i
        JOIN `pm_cycles` c ON c.id = i.cycle_id
        WHERE i.id = ?",
        [$id]
    );
}

function pmIpcrfByEmployee($employeeId, $cycleId)
{
    return find(
        "SELECT * FROM `pm_ipcrf` WHERE `employee_id` = ? AND `cycle_id` = ?",
        [$employeeId, $cycleId]
    );
}

function pmIpcrfList($employeeId)
{
    return query(
        "SELECT i.*, c.title AS cycle_title, c.school_year
        FROM `pm_ipcrf` i
        JOIN `pm_cycles` c ON c.id = i.cycle_id
        WHERE i.employee_id = ?
        ORDER BY c.date_start DESC",
        [$employeeId]
    ) ?: [];
}

function pmIpcrfByValidator($validatorId, $cycleId = null)
{
    $sql = "SELECT i.*, c.title AS cycle_title, c.school_year,
            CONCAT(e.last_name, ', ', e.first_name, ' ', COALESCE(e.middle_name, '')) AS ratee_name
            FROM `pm_ipcrf` i
            JOIN `pm_cycles` c ON c.id = i.cycle_id
            JOIN `employees` e ON e.id = i.employee_id
            WHERE i.validator_id = ?";
    $params = [$validatorId];

    if ($cycleId) {
        $sql .= " AND i.cycle_id = ?";
        $params[] = $cycleId;
    }

    $sql .= " ORDER BY c.date_start DESC, e.last_name ASC";
    return query($sql, $params) ?: [];
}

function createPmIpcrf($cycleId, $employeeId, $validatorId = null)
{
    return insert('pm_ipcrf', [
        'cycle_id' => $cycleId,
        'employee_id' => $employeeId,
        'validator_id' => $validatorId
    ]);
}

function updatePmIpcrfStatus($id, $status, $remarks = null, $field = 'ratee_remarks')
{
    $data = ['status' => $status];

    if ($remarks !== null) {
        $data[$field] = $remarks;
    }

    if ($status === 'Submitted') {
        $data['submitted_at'] = date('Y-m-d H:i:s');
    } elseif ($status === 'Validated' || $status === 'Completed') {
        $data['validated_at'] = date('Y-m-d H:i:s');
    }

    return update('pm_ipcrf', $data, '`id` = ?', [$id]);
}

function updatePmIpcrfPhase($id, $phase)
{
    return update('pm_ipcrf', ['phase' => $phase], '`id` = ?', [$id]);
}

function updatePmIpcrfFinalRating($id, $finalRating, $adjectivalRating)
{
    return update('pm_ipcrf', [
        'final_rating' => $finalRating,
        'adjectival_rating' => $adjectivalRating
    ], '`id` = ?', [$id]);
}

function updatePmIpcrfValidator($id, $validatorId)
{
    return update('pm_ipcrf', ['validator_id' => $validatorId], '`id` = ?', [$id]);
}

// ========== KRA ==========

function pmKras($ipcrfId)
{
    return query("SELECT * FROM `pm_kra` WHERE `ipcrf_id` = ? ORDER BY `sort_order` ASC", [$ipcrfId]) ?: [];
}

function pmKra($id)
{
    return find("SELECT * FROM `pm_kra` WHERE `id` = ?", [$id]);
}

function createPmKra($ipcrfId, $title, $weight, $sortOrder = 0)
{
    return insert('pm_kra', [
        'ipcrf_id' => $ipcrfId,
        'title' => $title,
        'weight' => $weight,
        'sort_order' => $sortOrder
    ]);
}

function updatePmKra($id, $title, $weight)
{
    return update('pm_kra', [
        'title' => $title,
        'weight' => $weight
    ], '`id` = ?', [$id]);
}

function deletePmKra($id)
{
    return delete('pm_kra', '`id` = ?', [$id]);
}

// ========== OBJECTIVES ==========

function pmObjectives($kraId)
{
    return query("SELECT * FROM `pm_objectives` WHERE `kra_id` = ? ORDER BY `sort_order` ASC", [$kraId]) ?: [];
}

function pmObjectivesByIpcrf($ipcrfId)
{
    return query(
        "SELECT o.*, k.title AS kra_title, k.weight AS kra_weight
        FROM `pm_objectives` o
        JOIN `pm_kra` k ON k.id = o.kra_id
        WHERE o.ipcrf_id = ?
        ORDER BY k.sort_order ASC, o.sort_order ASC",
        [$ipcrfId]
    ) ?: [];
}

function pmObjective($id)
{
    return find("SELECT * FROM `pm_objectives` WHERE `id` = ?", [$id]);
}

function createPmObjective($kraId, $ipcrfId, $objective, $indicator, $target, $weight = 0, $timeline = null, $sortOrder = 0)
{
    return insert('pm_objectives', [
        'kra_id' => $kraId,
        'ipcrf_id' => $ipcrfId,
        'weight' => $weight,
        'objective' => $objective,
        'performance_indicator' => $indicator,
        'target' => $target,
        'timeline' => $timeline,
        'sort_order' => $sortOrder
    ]);
}

function updatePmObjective($id, $objective, $indicator, $target, $weight = 0, $timeline = null)
{
    return update('pm_objectives', [
        'objective' => $objective,
        'performance_indicator' => $indicator,
        'target' => $target,
        'weight' => $weight,
        'timeline' => $timeline
    ], '`id` = ?', [$id]);
}

function updatePmObjectiveResult($id, $actualResult)
{
    return update('pm_objectives', [
        'actual_result' => $actualResult
    ], '`id` = ?', [$id]);
}

function updatePmObjectiveRating($id, $ratingQ, $ratingE, $ratingT, $remarks = null)
{
    $avg = ($ratingQ + $ratingE + $ratingT) / 3;
    return update('pm_objectives', [
        'rating_q' => $ratingQ,
        'rating_e' => $ratingE,
        'rating_t' => $ratingT,
        'average_rating' => round($avg, 2),
        'remarks' => $remarks
    ], '`id` = ?', [$id]);
}

function deletePmObjective($id)
{
    return delete('pm_objectives', '`id` = ?', [$id]);
}

// ========== VALIDATORS ==========

function pmValidator($validatorId, $rateeId, $cycleId)
{
    return find(
        "SELECT * FROM `pm_validators` WHERE `validator_id` = ? AND `ratee_id` = ? AND `cycle_id` = ?",
        [$validatorId, $rateeId, $cycleId]
    );
}

function pmValidatorOf($rateeId, $cycleId)
{
    return find(
        "SELECT v.*, CONCAT(e.last_name, ', ', e.first_name, ' ', COALESCE(e.middle_name, '')) AS validator_name
        FROM `pm_validators` v
        JOIN `employees` e ON e.id = v.validator_id
        WHERE v.ratee_id = ? AND v.cycle_id = ?",
        [$rateeId, $cycleId]
    );
}

function pmRatees($validatorId, $cycleId)
{
    return query(
        "SELECT v.*, CONCAT(e.last_name, ', ', e.first_name, ' ', COALESCE(e.middle_name, '')) AS ratee_name
        FROM `pm_validators` v
        JOIN `employees` e ON e.id = v.ratee_id
        WHERE v.validator_id = ? AND v.cycle_id = ?
        ORDER BY e.last_name ASC",
        [$validatorId, $cycleId]
    ) ?: [];
}

function assignPmValidator($validatorId, $rateeId, $cycleId)
{
    return insert('pm_validators', [
        'validator_id' => $validatorId,
        'ratee_id' => $rateeId,
        'cycle_id' => $cycleId
    ]);
}

function removePmValidator($validatorId, $rateeId, $cycleId)
{
    return delete('pm_validators', '`validator_id` = ? AND `ratee_id` = ? AND `cycle_id` = ?', [$validatorId, $rateeId, $cycleId]);
}

// ========== UTILITY ==========

function pmAdjectivalRating($rating)
{
    if ($rating >= 4.500) return 'Outstanding';
    if ($rating >= 3.500) return 'Very Satisfactory';
    if ($rating >= 2.500) return 'Satisfactory';
    if ($rating >= 1.500) return 'Unsatisfactory';
    return 'Poor';
}

function pmComputeFinalRating($ipcrfId)
{
    $kras = pmKras($ipcrfId);
    $totalWeightedRating = 0;
    $totalWeight = 0;

    foreach ($kras as $kra) {
        $objectives = pmObjectives($kra['id']);
        $kraRatingSum = 0;
        $count = 0;

        foreach ($objectives as $obj) {
            if ($obj['average_rating'] !== null) {
                $kraRatingSum += $obj['average_rating'];
                $count++;
            }
        }

        if ($count > 0) {
            $kraAvg = $kraRatingSum / $count;
            $totalWeightedRating += $kraAvg * ($kra['weight'] / 100);
            $totalWeight += $kra['weight'];
        }
    }

    if ($totalWeight > 0) {
        $finalRating = round($totalWeightedRating / ($totalWeight / 100), 2);
        return $finalRating;
    }

    return null;
}

function pmPhaseLabel($phase)
{
    $labels = [
        1 => 'Performance Planning and Commitment',
        2 => 'Performance Monitoring and Coaching',
        3 => 'Performance Review and Evaluation',
        4 => 'Performance Rewarding and Development Planning'
    ];
    return $labels[$phase] ?? 'Unknown';
}

function pmStatusBadge($status)
{
    $colors = [
        'Draft' => 'secondary',
        'Submitted' => 'info',
        'Validated' => 'success',
        'Returned' => 'warning',
        'Completed' => 'primary'
    ];
    $color = $colors[$status] ?? 'secondary';
    return "<span class=\"badge badge-{$color} px-2 py-1\">{$status}</span>";
}
