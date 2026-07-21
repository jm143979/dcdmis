<?php
// modules/pm/create-ipcrf.php
if (!$isPis) {
    require_once(root() . '/modules/error/403.php');
    return;
}

$employeeId = (int) sanitize(decode($_GET['id'] ?? null));

if ($userId !== $employeeId || !employee($employeeId)) {
    require_once(root() . '/modules/error/no-results-found.php');
    return;
}

$employee = employee($employeeId);
$activeCycle = pmActiveCycle();

if (!$activeCycle) {
    require_once(root() . '/modules/error/no-results-found.php');
    return;
}

$existingIpcrf = pmIpcrfByEmployee($employeeId, $activeCycle['id']);
if ($existingIpcrf) {
    redirect(customUri('pis', 'IPCRF Details', $existingIpcrf['id']));
}

messageAlert($showAlert, $message, $success);
?>

<div class="d-flex align-items-center justify-content-between flex-row mt-2 mb-3">
    <nav class="d-flex align-items-center flex-row m-0">
        <ol class="breadcrumb m-0 p-0 bg-transparent">
            <li class="breadcrumb-item"><a href="<?= uri() . '/' . $activeApp ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= customUri('pis', 'Performance Management', $employeeId) ?>">Performance Management</a></li>
            <li class="breadcrumb-item active">Create IPCRF</li>
        </ol>
    </nav>
</div>

<div class="card border-left-primary shadow mb-4">
    <div class="card-header py-3">
        <?php contentTitleWithLink('Create IPCRF - ' . e($activeCycle['title']), customUri('pis', 'Performance Management', $employeeId)) ?>
    </div>
    <div class="card-body">
        <form action="" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="verifier" value="<?= cipher($employeeId) ?>">
            <input type="hidden" name="cycle-verifier" value="<?= cipher($activeCycle['id']) ?>">

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">Ratee <?php showAsterisk() ?></label>
                        <input type="text" class="form-control" value="<?= e(strtoupper(toName($employee['last_name'], $employee['first_name'], $employee['middle_name'], $employee['name_extension']))) ?>" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">RPMS Cycle <?php showAsterisk() ?></label>
                        <input type="text" class="form-control" value="<?= e($activeCycle['title']) ?> (<?= e($activeCycle['school_year']) ?>)" readonly>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">Rater (Immediate Supervisor) <?php showAsterisk() ?></label>
                        <select name="validator_id" class="form-control" required>
                            <option value="">-- Select Rater --</option>
                            <?php
                            $sectionHeads = sections();
                            $validatorAssignment = pmValidatorOf($employeeId, $activeCycle['id']);
                            $selectedId = $validatorAssignment ? $validatorAssignment['validator_id'] : null;
                            $listed = [];

                            if ($sectionHeads):
                                foreach ($sectionHeads as $sec):
                                    $headId = $sec['head_id'];
                                    if (empty($headId) || $headId == $employeeId || isset($listed[$headId])) continue;
                                    $head = employee($headId);
                                    if (!$head) continue;
                                    $listed[$headId] = true;
                                    $headName = strtoupper(toName($head['last_name'], $head['first_name'], $head['middle_name'], $head['name_extension']));
                            ?>
                                    <option value="<?= e($headId) ?>" <?= $selectedId == $headId ? 'selected' : '' ?>>
                                        <?= e($headName) ?> — <?= e($sec['name']) ?>
                                    </option>
                            <?php
                                endforeach;
                            endif;
                            ?>
                        </select>
                        <small class="form-text text-muted">Select your immediate supervisor / section head.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">Period Covered</label>
                        <input type="text" class="form-control" value="<?= date('M d, Y', strtotime($activeCycle['date_start'])) ?> - <?= date('M d, Y', strtotime($activeCycle['date_end'])) ?>" readonly>
                    </div>
                </div>
            </div>

            <hr class="my-3">

            <h6 class="font-weight-bold text-primary mb-3">Key Result Areas (KRA)</h6>
            <p class="small text-muted mb-3">Define your KRAs and their respective weights. Weights must total 100%.</p>

            <div id="kra-container">
                <div class="kra-item border rounded p-3 mb-3">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group mb-2">
                                <label class="font-weight-bold small">KRA Title <?php showAsterisk() ?></label>
                                <input type="text" name="kra_title[]" class="form-control" placeholder="e.g., Content Knowledge and Pedagogy" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="font-weight-bold small">Weight (%) <?php showAsterisk() ?></label>
                                <input type="number" name="kra_weight[]" class="form-control" min="1" max="100" placeholder="e.g., 25" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm mb-3" onclick="addKra()">
                <i class="fas fa-plus mr-1"></i> Add KRA
            </button>

            <hr class="my-3">
            <?php requiredLegend() ?>

            <div class="text-right">
                <a href="<?= customUri('pis', 'Performance Management', $employeeId) ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" name="create-ipcrf" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Create IPCRF
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function addKra() {
    const container = document.getElementById('kra-container');
    const count = container.querySelectorAll('.kra-item').length + 1;
    const html = `
        <div class="kra-item border rounded p-3 mb-3">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group mb-2">
                        <label class="font-weight-bold small">KRA Title <span class="text-danger small"> *</span></label>
                        <input type="text" name="kra_title[]" class="form-control" placeholder="Enter KRA title" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="font-weight-bold small">Weight (%) <span class="text-danger small"> *</span></label>
                        <input type="number" name="kra_weight[]" class="form-control" min="1" max="100" placeholder="%" required>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger btn-sm mb-2" onclick="this.closest('.kra-item').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}
</script>
