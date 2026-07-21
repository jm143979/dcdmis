<?php
// modules/pm/ipcrf-details.php
if (!$isPis) {
    require_once(root() . '/modules/error/403.php');
    return;
}

$ipcrfId = (int) sanitize(decode($_GET['id'] ?? null));
$ipcrf = pmIpcrf($ipcrfId);

if (!$ipcrf) {
    require_once(root() . '/modules/error/no-results-found.php');
    return;
}

$isOwner = ($userId === (int) $ipcrf['employee_id']);
$isValidator = ($userId === (int) $ipcrf['validator_id']);

if (!$isOwner && !$isValidator) {
    require_once(root() . '/modules/error/403.php');
    return;
}

$employee = employee($ipcrf['employee_id']);
$kras = pmKras($ipcrfId);
$isDraft = ($ipcrf['status'] === 'Draft' || $ipcrf['status'] === 'Returned');

messageAlert($showAlert, $message, $success);
?>

<div class="d-flex align-items-center justify-content-between flex-row mt-2 mb-3">
    <nav class="d-flex align-items-center flex-row m-0">
        <ol class="breadcrumb m-0 p-0 bg-transparent">
            <li class="breadcrumb-item"><a href="<?= uri() . '/' . $activeApp ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= customUri('pis', 'Performance Management', $userId) ?>">Performance Management</a></li>
            <li class="breadcrumb-item active">IPCRF Details</li>
        </ol>
    </nav>
</div>

<!-- IPCRF Header -->
<div class="card border-left-primary shadow mb-4">
    <div class="card-header py-3">
        <div class="d-sm-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary text-uppercase">
                IPCRF - <?= e($ipcrf['cycle_title']) ?> (<?= e($ipcrf['school_year']) ?>)
            </h6>
            <div>
                <?= pmStatusBadge($ipcrf['status']) ?>
                <span class="badge badge-light px-2 py-1 ml-1">Phase <?= e($ipcrf['phase']) ?>: <?= e(pmPhaseLabel($ipcrf['phase'])) ?></span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <p class="mb-1"><strong>Ratee:</strong> <?= e(strtoupper(toName($employee['last_name'], $employee['first_name'], $employee['middle_name'], $employee['name_extension']))) ?></p>
            </div>
            <div class="col-md-4">
                <p class="mb-1"><strong>Validator:</strong>
                    <?php
                    if ($ipcrf['validator_id']) {
                        $validator = employee($ipcrf['validator_id']);
                        echo $validator ? e(strtoupper(toName($validator['last_name'], $validator['first_name'], $validator['middle_name'], $validator['name_extension']))) : 'Not assigned';
                    } else {
                        echo 'Not assigned';
                    }
                    ?>
                </p>
            </div>
            <div class="col-md-4">
                <p class="mb-1"><strong>Final Rating:</strong>
                    <?= $ipcrf['final_rating'] ? number_format($ipcrf['final_rating'], 2) . ' (' . e($ipcrf['adjectival_rating']) . ')' : 'Pending' ?>
                </p>
            </div>
        </div>

        <?php if ($ipcrf['validator_remarks']): ?>
            <div class="alert alert-info small p-2 mt-2 mb-0">
                <strong>Validator Remarks:</strong> <?= e($ipcrf['validator_remarks']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- KRAs and Objectives -->
<?php if (empty($kras)): ?>
    <div class="alert alert-warning small p-2">
        <i class="fas fa-exclamation-circle mr-1"></i> No Key Result Areas defined yet.
    </div>
<?php endif; ?>

<?php foreach ($kras as $index => $kra):
    $objectives = pmObjectives($kra['id']);
?>
    <div class="card shadow mb-3">
        <div class="card-header py-2 bg-light">
            <div class="d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-dark">
                    KRA <?= $index + 1 ?>: <?= e($kra['title']) ?>
                    <span class="badge badge-primary ml-2"><?= e($kra['weight']) ?>%</span>
                </h6>
                <?php if ($isOwner && $isDraft): ?>
                    <a href="<?= customUri('pis', 'Add Objective', $kra['id']) ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus fa-sm mr-1"></i> Add Objective
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($objectives)): ?>
                <p class="text-muted text-center py-3 mb-0">No objectives defined for this KRA.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="bg-light">
                            <tr class="text-center small text-uppercase">
                                <th width="4%">#</th>
                                <th width="20%">Objective</th>
                                <th width="5%">Weight</th>
                                <th width="15%">Performance Indicator</th>
                                <th width="10%">Target</th>
                                <th width="10%">Timeline</th>
                                <th width="12%">Actual Result</th>
                                <th width="5%">Q</th>
                                <th width="5%">E</th>
                                <th width="5%">T</th>
                                <th width="5%">Ave</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($objectives as $oi => $obj): ?>
                                <tr class="small">
                                    <td class="text-center align-middle"><?= $oi + 1 ?></td>
                                    <td class="align-middle"><?= e($obj['objective']) ?></td>
                                    <td class="text-center align-middle"><?= e($obj['weight']) ?>%</td>
                                    <td class="align-middle"><?= e($obj['performance_indicator'] ?? '-') ?></td>
                                    <td class="align-middle"><?= e($obj['target'] ?? '-') ?></td>
                                    <td class="align-middle"><?= e($obj['timeline'] ?? '-') ?></td>
                                    <td class="align-middle"><?= e($obj['actual_result'] ?? '-') ?></td>
                                    <td class="text-center align-middle"><?= $obj['rating_q'] ?? '-' ?></td>
                                    <td class="text-center align-middle"><?= $obj['rating_e'] ?? '-' ?></td>
                                    <td class="text-center align-middle"><?= $obj['rating_t'] ?? '-' ?></td>
                                    <td class="text-center align-middle font-weight-bold"><?= $obj['average_rating'] ? number_format($obj['average_rating'], 2) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<!-- Actions -->
<?php if ($isOwner && $isDraft && !empty($kras)): ?>
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="verifier" value="<?= cipher($ipcrfId) ?>">
                <div class="form-group">
                    <label class="font-weight-bold small">Remarks (Optional)</label>
                    <textarea name="ratee_remarks" class="form-control" rows="2" placeholder="Any remarks for your validator..."><?= e($ipcrf['ratee_remarks'] ?? '') ?></textarea>
                </div>
                <div class="d-flex justify-content-between">
                    <a href="<?= customUri('pis', 'Performance Management', $userId) ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                    <button type="submit" name="submit-ipcrf" class="btn btn-success btn-sm" onclick="return confirm('Submit this IPCRF for validation? You cannot edit it after submission.')">
                        <i class="fas fa-paper-plane mr-1"></i> Submit for Validation
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php elseif ($isOwner): ?>
    <div class="text-left mb-4">
        <a href="<?= customUri('pis', 'Performance Management', $userId) ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>
<?php endif; ?>
