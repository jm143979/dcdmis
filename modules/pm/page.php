<?php
// modules/pm/page.php
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
$myIpcrfList = pmIpcrfList($employeeId);
$rateeList = $activeCycle ? pmIpcrfByValidator($employeeId, $activeCycle['id']) : [];

messageAlert($showAlert, $message, $success);
?>

<div class="d-flex align-items-center justify-content-between flex-row mt-2 mb-3">
    <nav class="d-flex align-items-center flex-row m-0">
        <ol class="breadcrumb m-0 p-0 bg-transparent">
            <li class="breadcrumb-item"><a href="<?= uri() . '/' . $activeApp ?>">Dashboard</a></li>
            <li class="breadcrumb-item active">Performance Management</li>
        </ol>
    </nav>
</div>

<?php contentTitle('Performance Management') ?>

<!-- Active Cycle Info -->
<?php if ($activeCycle): ?>
    <div class="alert alert-info d-flex align-items-center small p-2 mt-3">
        <i class="fas fa-info-circle mr-2"></i>
        <div>
            <strong>Active RPMS Cycle:</strong> <?= e($activeCycle['title']) ?> (<?= e($activeCycle['school_year']) ?>)
            &mdash; <?= date('M d, Y', strtotime($activeCycle['date_start'])) ?> to <?= date('M d, Y', strtotime($activeCycle['date_end'])) ?>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning d-flex align-items-center small p-2 mt-3">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <div>No active RPMS cycle. Please contact your administrator.</div>
    </div>
<?php endif; ?>

<!-- RPMS Phases -->
<?php
$currentIpcrf = $activeCycle ? pmIpcrfByEmployee($employeeId, $activeCycle['id']) : null;
$currentPhase = $currentIpcrf ? (int) $currentIpcrf['phase'] : 0;
$currentStatus = $currentIpcrf ? $currentIpcrf['status'] : null;

$phases = [
    1 => [
        'title' => 'Performance Planning &amp; Commitment',
        'desc' => 'Create IPCRF and define KRAs/objectives',
        'icon' => 'fa-file-signature',
        'color' => 'primary',
    ],
    2 => [
        'title' => 'Performance Monitoring &amp; Coaching',
        'desc' => 'Track progress and update actual results',
        'icon' => 'fa-chart-line',
        'color' => 'info',
    ],
    3 => [
        'title' => 'Performance Review &amp; Evaluation',
        'desc' => 'Rate performance and evaluate results',
        'icon' => 'fa-clipboard-check',
        'color' => 'warning',
    ],
    4 => [
        'title' => 'Performance Rewarding &amp; Development',
        'desc' => 'Development planning and recognition',
        'icon' => 'fa-award',
        'color' => 'success',
    ],
];
?>
<div class="row mt-3">
    <?php foreach ($phases as $phaseNum => $phase):
        $isActive = ($currentPhase === $phaseNum);
        $isCompleted = ($currentPhase > $phaseNum);
        $isLocked = ($currentPhase < $phaseNum) || !$currentIpcrf;

        if ($isActive && $currentIpcrf) {
            if ($phaseNum === 1) {
                $phaseLink = customUri('pis', 'IPCRF Details', $currentIpcrf['id']);
            } elseif ($phaseNum === 2) {
                $phaseLink = customUri('pis', 'IPCRF Details', $currentIpcrf['id']);
            } elseif ($phaseNum === 3) {
                $phaseLink = customUri('pis', 'IPCRF Details', $currentIpcrf['id']);
            } else {
                $phaseLink = customUri('pis', 'IPCRF Details', $currentIpcrf['id']);
            }
        } elseif ($isCompleted && $currentIpcrf) {
            $phaseLink = customUri('pis', 'IPCRF Details', $currentIpcrf['id']);
        } elseif (!$currentIpcrf && $phaseNum === 1 && $activeCycle) {
            $phaseLink = customUri('pis', 'Create IPCRF', $employeeId);
        } else {
            $phaseLink = '#';
        }

        if ($isActive) {
            $statusLabel = '<span class="badge badge-' . $phase['color'] . ' px-2 py-1 mt-2"><i class="fas fa-spinner fa-sm mr-1"></i>In Progress</span>';
        } elseif ($isCompleted) {
            $statusLabel = '<span class="badge badge-success px-2 py-1 mt-2"><i class="fas fa-check fa-sm mr-1"></i>Completed</span>';
        } elseif (!$currentIpcrf && $phaseNum === 1 && $activeCycle) {
            $statusLabel = '<span class="badge badge-primary px-2 py-1 mt-2"><i class="fas fa-plus fa-sm mr-1"></i>Get Started</span>';
        } else {
            $statusLabel = '<span class="badge badge-light text-muted px-2 py-1 mt-2"><i class="fas fa-lock fa-sm mr-1"></i>Locked</span>';
        }

        $cardOpacity = $isLocked && !(!$currentIpcrf && $phaseNum === 1 && $activeCycle) ? 'opacity: 0.55;' : '';
        $cursorStyle = $phaseLink !== '#' ? 'cursor: pointer;' : 'cursor: default;';
    ?>
        <div class="col-xl-3 col-md-6 mb-3">
            <a href="<?= $phaseLink ?>" class="text-decoration-none" style="<?= $cursorStyle ?>">
                <div class="card border-left-<?= $phase['color'] ?> shadow-sm h-100 phase-card" style="transition: transform 0.15s, box-shadow 0.15s; <?= $cardOpacity ?>">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="text-xs font-weight-bold text-<?= $phase['color'] ?> text-uppercase">Phase <?= $phaseNum ?></div>
                            <i class="fas <?= $phase['icon'] ?> fa-lg text-<?= $phase['color'] ?>" style="opacity: 0.3;"></i>
                        </div>
                        <div class="small font-weight-bold text-gray-800"><?= $phase['title'] ?></div>
                        <div class="small text-muted mt-1"><?= $phase['desc'] ?></div>
                        <?= $statusLabel ?>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<style>
.phase-card:hover { transform: translateY(-3px); box-shadow: 0 .35rem 1rem rgba(0,0,0,.12) !important; }
a.text-decoration-none:hover { text-decoration: none !important; }
</style>

<!-- My IPCRF Section -->
<div class="card border-left-primary shadow mb-4">
    <div class="card-header py-3">
        <div class="d-sm-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary text-uppercase">My IPCRF</h6>
            <?php if ($activeCycle && !pmIpcrfByEmployee($employeeId, $activeCycle['id'])): ?>
                <a href="<?= customUri('pis', 'Create IPCRF', $employeeId) ?>" class="btn btn-primary btn-icon-split btn-sm my-1 p-0 d-inline-flex align-items-stretch overflow-hidden">
                    <span class="icon text-white-50 d-inline-flex align-items-center justify-content-center"><i class="fas fa-plus fa-fw"></i></span>
                    <span class="text d-inline-flex align-items-center">Create IPCRF</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($myIpcrfList)): ?>
            <p class="text-muted text-center mb-0">No IPCRF records found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-center" width="100%">
                    <thead>
                        <tr>
                            <th class="align-middle">School Year</th>
                            <th class="align-middle">Cycle</th>
                            <th class="align-middle">Phase</th>
                            <th class="align-middle">Status</th>
                            <th class="align-middle">Final Rating</th>
                            <th class="align-middle">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myIpcrfList as $ipcrf): ?>
                            <tr>
                                <td class="align-middle"><?= e($ipcrf['school_year']) ?></td>
                                <td class="align-middle"><?= e($ipcrf['cycle_title']) ?></td>
                                <td class="align-middle"><span class="badge badge-light px-2 py-1">Phase <?= e($ipcrf['phase']) ?></span></td>
                                <td class="align-middle"><?= pmStatusBadge($ipcrf['status']) ?></td>
                                <td class="align-middle"><?= $ipcrf['final_rating'] ? number_format($ipcrf['final_rating'], 2) . ' (' . e($ipcrf['adjectival_rating']) . ')' : '-' ?></td>
                                <td class="align-middle">
                                    <a href="<?= customUri('pis', 'IPCRF Details', $ipcrf['id']) ?>" class="btn btn-sm btn-outline-primary" title="View IPCRF">
                                        <i class="fas fa-eye fa-sm"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Validator Section - Ratees to Review -->
<?php if (!empty($rateeList)): ?>
    <div class="card border-left-success shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success text-uppercase">
                <i class="fas fa-user-check mr-1"></i> Ratees for Review
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-center" width="100%">
                    <thead>
                        <tr>
                            <th class="align-middle">Ratee</th>
                            <th class="align-middle">Cycle</th>
                            <th class="align-middle">Phase</th>
                            <th class="align-middle">Status</th>
                            <th class="align-middle">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rateeList as $ratee): ?>
                            <tr>
                                <td class="align-middle text-uppercase"><?= e($ratee['ratee_name']) ?></td>
                                <td class="align-middle"><?= e($ratee['cycle_title']) ?></td>
                                <td class="align-middle"><span class="badge badge-light px-2 py-1">Phase <?= e($ratee['phase']) ?></span></td>
                                <td class="align-middle"><?= pmStatusBadge($ratee['status']) ?></td>
                                <td class="align-middle">
                                    <a href="<?= customUri('pis', 'Review IPCRF', $ratee['id']) ?>" class="btn btn-sm btn-outline-success" title="Review">
                                        <i class="fas fa-clipboard-check fa-sm"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
