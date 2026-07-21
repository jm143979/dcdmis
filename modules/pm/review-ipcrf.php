<?php
// modules/pm/review-ipcrf.php
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

$isValidator = ($userId === (int) $ipcrf['validator_id']);

if (!$isValidator) {
    require_once(root() . '/modules/error/403.php');
    return;
}

$employee = employee($ipcrf['employee_id']);
$kras = pmKras($ipcrfId);
$canRate = ($ipcrf['status'] === 'Submitted' || $ipcrf['status'] === 'Validated');

messageAlert($showAlert, $message, $success);
?>

<div class="d-flex align-items-center justify-content-between flex-row mt-2 mb-3">
    <nav class="d-flex align-items-center flex-row m-0">
        <ol class="breadcrumb m-0 p-0 bg-transparent">
            <li class="breadcrumb-item"><a href="<?= uri() . '/' . $activeApp ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= customUri('pis', 'Performance Management', $userId) ?>">Performance Management</a></li>
            <li class="breadcrumb-item active">Review IPCRF</li>
        </ol>
    </nav>
</div>

<!-- Ratee Info -->
<div class="card border-left-success shadow mb-4">
    <div class="card-header py-3">
        <div class="d-sm-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-success text-uppercase">
                <i class="fas fa-clipboard-check mr-1"></i>
                Review IPCRF - <?= e(strtoupper(toName($employee['last_name'], $employee['first_name'], $employee['middle_name'], $employee['name_extension']))) ?>
            </h6>
            <?= pmStatusBadge($ipcrf['status']) ?>
        </div>
    </div>
    <div class="card-body">
        <div class="row small">
            <div class="col-md-3"><strong>Cycle:</strong> <?= e($ipcrf['cycle_title']) ?> (<?= e($ipcrf['school_year']) ?>)</div>
            <div class="col-md-3"><strong>Phase:</strong> <?= e(pmPhaseLabel($ipcrf['phase'])) ?></div>
            <div class="col-md-3"><strong>Submitted:</strong> <?= $ipcrf['submitted_at'] ? date('M d, Y', strtotime($ipcrf['submitted_at'])) : 'Not submitted' ?></div>
            <div class="col-md-3"><strong>Final Rating:</strong> <?= $ipcrf['final_rating'] ? number_format($ipcrf['final_rating'], 2) . ' (' . e($ipcrf['adjectival_rating']) . ')' : 'Pending' ?></div>
        </div>
        <?php if ($ipcrf['ratee_remarks']): ?>
            <div class="alert alert-light small p-2 mt-2 mb-0 border">
                <strong>Ratee Remarks:</strong> <?= e($ipcrf['ratee_remarks']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Rating Form -->
<?php if ($canRate): ?>
    <form action="" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="verifier" value="<?= cipher($ipcrfId) ?>">
<?php endif; ?>

<!-- KRAs and Objectives with Rating -->
<?php foreach ($kras as $index => $kra):
    $objectives = pmObjectives($kra['id']);
?>
    <div class="card shadow mb-3">
        <div class="card-header py-2 bg-light">
            <h6 class="m-0 font-weight-bold text-dark">
                KRA <?= $index + 1 ?>: <?= e($kra['title']) ?>
                <span class="badge badge-primary ml-2"><?= e($kra['weight']) ?>%</span>
            </h6>
        </div>
        <div class="card-body p-0">
            <?php if (empty($objectives)): ?>
                <p class="text-muted text-center py-3 mb-0">No objectives defined.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="bg-light">
                            <tr class="text-center small text-uppercase">
                                <th width="5%">#</th>
                                <th width="20%">Objective</th>
                                <th width="15%">Indicator</th>
                                <th width="10%">Target</th>
                                <th width="10%">Actual Result</th>
                                <th width="10%">Q (1-5)</th>
                                <th width="10%">E (1-5)</th>
                                <th width="10%">T (1-5)</th>
                                <th width="10%">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($objectives as $oi => $obj): ?>
                                <tr class="small">
                                    <td class="text-center align-middle"><?= $oi + 1 ?></td>
                                    <td class="align-middle"><?= e($obj['objective']) ?></td>
                                    <td class="align-middle"><?= e($obj['performance_indicator'] ?? '-') ?></td>
                                    <td class="align-middle"><?= e($obj['target'] ?? '-') ?></td>
                                    <td class="align-middle"><?= e($obj['actual_result'] ?? '-') ?></td>
                                    <?php if ($canRate): ?>
                                        <td class="align-middle">
                                            <input type="hidden" name="obj_id[]" value="<?= cipher($obj['id']) ?>">
                                            <input type="number" name="rating_q[]" class="form-control form-control-sm text-center" min="1" max="5" step="0.5" value="<?= e($obj['rating_q'] ?? '') ?>" placeholder="-">
                                        </td>
                                        <td class="align-middle">
                                            <input type="number" name="rating_e[]" class="form-control form-control-sm text-center" min="1" max="5" step="0.5" value="<?= e($obj['rating_e'] ?? '') ?>" placeholder="-">
                                        </td>
                                        <td class="align-middle">
                                            <input type="number" name="rating_t[]" class="form-control form-control-sm text-center" min="1" max="5" step="0.5" value="<?= e($obj['rating_t'] ?? '') ?>" placeholder="-">
                                        </td>
                                        <td class="align-middle">
                                            <input type="text" name="obj_remarks[]" class="form-control form-control-sm" value="<?= e($obj['remarks'] ?? '') ?>" placeholder="-">
                                        </td>
                                    <?php else: ?>
                                        <td class="text-center align-middle"><?= $obj['rating_q'] ?? '-' ?></td>
                                        <td class="text-center align-middle"><?= $obj['rating_e'] ?? '-' ?></td>
                                        <td class="text-center align-middle"><?= $obj['rating_t'] ?? '-' ?></td>
                                        <td class="align-middle"><?= e($obj['remarks'] ?? '-') ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<!-- Validator Actions -->
<?php if ($canRate): ?>
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="form-group">
                <label class="font-weight-bold small">Validator Remarks</label>
                <textarea name="validator_remarks" class="form-control" rows="2" placeholder="Provide feedback or comments..."><?= e($ipcrf['validator_remarks'] ?? '') ?></textarea>
            </div>
            <div class="d-flex justify-content-between">
                <a href="<?= customUri('pis', 'Performance Management', $userId) ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
                <div>
                    <button type="submit" name="return-ipcrf" class="btn btn-warning btn-sm" onclick="return confirm('Return this IPCRF to the ratee for revision?')">
                        <i class="fas fa-undo mr-1"></i> Return to Ratee
                    </button>
                    <button type="submit" name="save-ratings" class="btn btn-info btn-sm">
                        <i class="fas fa-save mr-1"></i> Save Ratings
                    </button>
                    <button type="submit" name="validate-ipcrf" class="btn btn-success btn-sm" onclick="return confirm('Validate and finalize this IPCRF?')">
                        <i class="fas fa-check-circle mr-1"></i> Validate
                    </button>
                </div>
            </div>
        </div>
    </div>
    </form>
<?php else: ?>
    <div class="text-left mb-4">
        <a href="<?= customUri('pis', 'Performance Management', $userId) ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>
<?php endif; ?>
