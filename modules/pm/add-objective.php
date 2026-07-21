<?php
// modules/pm/add-objective.php
if (!$isPis) {
    require_once(root() . '/modules/error/403.php');
    return;
}

$kraId = (int) sanitize(decode($_GET['id'] ?? null));
$kra = pmKra($kraId);

if (!$kra) {
    require_once(root() . '/modules/error/no-results-found.php');
    return;
}

$ipcrf = pmIpcrf($kra['ipcrf_id']);

if (!$ipcrf || (int) $ipcrf['employee_id'] !== $userId) {
    require_once(root() . '/modules/error/403.php');
    return;
}

if ($ipcrf['status'] !== 'Draft' && $ipcrf['status'] !== 'Returned') {
    redirect(customUri('pis', 'IPCRF Details', $ipcrf['id']));
}

$objectives = pmObjectives($kraId);

messageAlert($showAlert, $message, $success);
?>

<div class="d-flex align-items-center justify-content-between flex-row mt-2 mb-3">
    <nav class="d-flex align-items-center flex-row m-0">
        <ol class="breadcrumb m-0 p-0 bg-transparent">
            <li class="breadcrumb-item"><a href="<?= uri() . '/' . $activeApp ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= customUri('pis', 'Performance Management', $userId) ?>">Performance Management</a></li>
            <li class="breadcrumb-item"><a href="<?= customUri('pis', 'IPCRF Details', $ipcrf['id']) ?>">IPCRF</a></li>
            <li class="breadcrumb-item active">Add Objective</li>
        </ol>
    </nav>
</div>

<div class="card border-left-primary shadow mb-4">
    <div class="card-header py-3">
        <?php contentTitleWithLink('Phase 1: Performance Planning and Commitment', customUri('pis', 'IPCRF Details', $ipcrf['id'])) ?>
    </div>
    <div class="card-body">
        <form action="" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="verifier" value="<?= cipher($kraId) ?>">
            <input type="hidden" name="ipcrf-verifier" value="<?= cipher($ipcrf['id']) ?>">

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">Key Result Area <?php showAsterisk() ?></label>
                        <input type="text" class="form-control bg-light" value="<?= e($kra['title']) ?> (<?= e($kra['weight']) ?>%)" readonly>
                        <small class="form-text text-muted">The category under which this objective belongs.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">IPCRF (Rating Period) <?php showAsterisk() ?></label>
                        <input type="text" class="form-control bg-light" value="<?= e($ipcrf['cycle_title']) ?> (<?= e($ipcrf['school_year']) ?>)" readonly>
                        <small class="form-text text-muted">The RPMS cycle this objective is linked to.</small>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="font-weight-bold">Objective <?php showAsterisk() ?></label>
                <textarea name="objective" class="form-control" rows="2" placeholder="Enter the performance objective statement..." required></textarea>
                <small class="form-text text-muted">The specific performance objective to be accomplished.</small>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Weight (%) <?php showAsterisk() ?></label>
                        <input type="number" name="weight" class="form-control" min="1" max="100" step="1" placeholder="e.g., 20" required>
                        <small class="form-text text-muted">Percentage weight of this objective.</small>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label class="font-weight-bold">Performance Indicator <?php showAsterisk() ?></label>
                        <textarea name="performance_indicator" class="form-control" rows="1" placeholder="How will performance be measured?" required></textarea>
                        <small class="form-text text-muted">Criteria to measure if the objective is met.</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">Target</label>
                        <textarea name="target" class="form-control" rows="1" placeholder="What is the expected output/result?"></textarea>
                        <small class="form-text text-muted">The expected output or deliverable.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">Timeline <?php showAsterisk() ?></label>
                        <input type="text" name="timeline" class="form-control" placeholder="e.g., June - October 2025" required>
                        <small class="form-text text-muted">When this objective should be accomplished.</small>
                    </div>
                </div>
            </div>

            <hr class="my-3">
            <?php requiredLegend() ?>

            <div class="text-right">
                <a href="<?= customUri('pis', 'IPCRF Details', $ipcrf['id']) ?>" class="btn btn-secondary btn-sm">Cancel</a>
                <button type="submit" name="save-objective" class="btn btn-primary btn-sm">
                    <i class="fas fa-save mr-1"></i> Save Objective
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Existing Objectives -->
<?php if (!empty($objectives)): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-2 bg-light">
            <h6 class="m-0 font-weight-bold text-dark small text-uppercase">Existing Objectives for this KRA</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="bg-light">
                        <tr class="text-center small">
                            <th width="5%">#</th>
                            <th width="25%">Objective</th>
                            <th width="8%">Weight</th>
                            <th width="20%">Performance Indicator</th>
                            <th width="15%">Target</th>
                            <th width="15%">Timeline</th>
                            <th width="7%">Action</th>
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
                                <td class="text-center align-middle">
                                    <form action="" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="verifier" value="<?= cipher($kraId) ?>">
                                        <input type="hidden" name="objective-verifier" value="<?= cipher($obj['id']) ?>">
                                        <button type="submit" name="delete-objective" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this objective?')">
                                            <i class="fas fa-trash fa-sm"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
