<?php
// pis/app.php
$activeApp = $_SESSION["{$prefix}activeApp"] = HOME;
$page = $appTitle = 'Personnel Information System';

if (!isset($userId)) {
    redirect("{$baseUri}/login");
}

if (isset($_SESSION["{$prefix}change_password"])) {
    redirect("{$baseUri}/login/change");
}

if (isset($_POST['primary-search-button'])) {
    redirect(customUri('pis', 'Search', sanitize($_POST['primary-search-text'])));
}

if (isset($_POST['update-identification'])) {
    $card = sanitize($_POST['card-type']);
    $number = sanitize($_POST['card-number']);
    $place = sanitize($_POST['card-place']);
    $date = sanitize($_POST['card-date']);
    $showAlert = true;
    $result = !employeeIdentification($userId) ?
        createIdentification($card, $number, $place, $date, $userId) :
        updateIdentification($card, $number, $place, $date, $userId);

    if ($result === false) {
        $success = false;
        $message = 'We encountered an error on our end. Please try again later.';
        return;
    }


    if ($result === 0) {
        $message = 'No changes have been made to government issued ID.';
    } else {
        $message = 'Government issued ID has been updated successfully.';
        $success = true;

        createSystemLog($stationId, $userId, 'Updated identification details', $userId, clientIp());
    }
}

if (isset($_POST['save-payslip'])) {
    $employeeId = sanitize(decipher($_POST['verifier'] ?? null));
    $payslipId = sanitize(decipher($_POST['data-verifier'] ?? null));
    $description = sanitize($_POST['description']);
    $oldFilename = sanitize(decipher($_POST['file-verifier'] ?? null));
    $showAlert = true;
    $stagedFile = null;

    try {
        if (empty($employeeId)) {
            throw new Exception('Invalid or expired transaction.');
        }

        if (!empty($_FILES['file-upload']['tmp_name']) && is_uploaded_file($_FILES['file-upload']['tmp_name'])) {
            $stagedFile = stageUploadedFile(
                $_FILES['file-upload'],
                ['application/pdf' => 'pdf'],
                root() . "/uploads/201_files/{$employeeId}",
                "PAYSLIP"
            );
        }

        beginTransaction();

        $newFilename = $stagedFile ? "uploads/201_files/{$employeeId}/{$stagedFile['secure_name']}" : $oldFilename;

        if (empty($newFilename)) {
            throw new Exception('No changes have been made to payslips.');
        }

        $ext = pathinfo($newFilename, PATHINFO_EXTENSION);
        $hasExistingRecord = fileAttachment($employeeId, $payslipId);

        if (!$hasExistingRecord) {
            $result = createFileAttachment(20, $description, $newFilename, $ext, $employeeId);
            $logMessage = 'Added payslip.';
        } else {
            $result = updateFileAttachment(20, $description, $newFilename, $ext, $employeeId, $payslipId);
            $logMessage = 'Updated payslip.';
        }

        if ($result === false) {
            throw new Exception('We encountered an error on our end. Please try again later.');
        }

        if ($stagedFile) {
            commitStagedFile($stagedFile);
        }

        commit();

        $success = true;
        $actionText = $hasExistingRecord ? 'updated' : 'added';
        $message = "Payslip has been {$actionText} successfully.";

        createSystemLog($stationId, $userId, $logMessage, $employeeId, clientIp());

        if ($stagedFile && !empty($oldFilename) && file_exists(root() . "/{$oldFilename}")) {
            unlink(root() . "/{$oldFilename}");
        }
    } catch (Exception $e) {
        rollBack();

        if ($stagedFile && file_exists($stagedFile['full_path'])) {
            unlink($stagedFile['full_path']);
        }

        $success = false;
        $message = $e->getMessage();
    }
}

if (isset($_POST['delete-payslip'])) {
    $employeeId = sanitize(decipher($_POST['verifier'] ?? null));
    $payslipId = sanitize(decipher($_POST['data-verifier'] ?? null));
    $showAlert = true;
    $success = false;
    $file = fileAttachment($employeeId, $payslipId);

    if (!$file) {
        $message = 'The requested payslip file does not exist.';
        return;
    }

    $filename = $file['file_name'];
    $filePath = root() . "/{$filename}";

    if (file_exists($filePath)) {
        if (!unlink($filePath)) {
            $message = 'We encountered an error deleting the physical file. Please try again.';
            return;
        }
    }

    $result = deleteFileAttachment($employeeId, $payslipId);

    if ($result === false) {
        $message = 'We encountered an error updating the database. Please try again later.';
        return;
    }

    if ($result === 0) {
        $message = 'No changes have been made to the payslip database record.';
        return;
    }

    $success = true;
    $message = 'Payslip has been deleted successfully.';

    createSystemLog($stationId, $userId, 'Deleted employee payslip', $employeeId, clientIp());
}

if (isset($_POST['submit-transfer-request'])) {
    $targetStationId = sanitize($_POST['target-station']);
    $reason = sanitize($_POST['reason']);
    $showAlert = true;
    $success = false;
    $stagedFile = null;

    try {
        if (empty($targetStationId)) {
            throw new Exception('Please select a preferred station assignment.');
        }
        if (empty($reason)) {
            throw new Exception('Please state your reason for the transfer request.');
        }
        if (empty($_FILES['attachment']['tmp_name']) || !is_uploaded_file($_FILES['attachment']['tmp_name'])) {
            throw new Exception('Please upload a supporting document.');
        }

        $currStation = station($userId);
        $currentStationId = $currStation ? $currStation['station_id'] : '';

        if (empty($currentStationId)) {
            throw new Exception('Your current station assignment could not be resolved. Please contact HR.');
        }

        if ($currentStationId === $targetStationId) {
            throw new Exception('Your target station must be different from your current station.');
        }

        $isTeaching = false;
        if ($currStation) {
            $pos = positions($currStation['position_id']);
            if ($pos && $pos['category'] === 'Teaching') {
                $isTeaching = true;
            }
        }

        $specialization = null;
        if ($isTeaching) {
            $specialization = sanitize($_POST['specialization'] ?? '');
            if (empty($specialization)) {
                throw new Exception('Please fill up your major subject / area of specialization.');
            }
        }

        // Stage the uploaded file
        $stagedFile = stageUploadedFile(
            $_FILES['attachment'],
            [
                'application/pdf' => 'pdf',
            ],
            root() . "/uploads/transfer_requests/{$userId}",
            "TRANSFER"
        );

        beginTransaction();

        $attachmentPath = "uploads/transfer_requests/{$userId}/" . $stagedFile['secure_name'];
        $result = createTransferRequest($userId, $currentStationId, $targetStationId, $reason, $attachmentPath, $specialization);

        if ($result === false) {
            throw new Exception('We encountered an error saving your request. Please try again later.');
        }

        commitStagedFile($stagedFile);
        commit();

        $success = true;
        $message = 'Your transfer request has been submitted successfully.';
        createSystemLog($stationId, $userId, 'Submitted transfer request', $userId, clientIp());

    } catch (Exception $e) {
        rollBack();
        if ($stagedFile && file_exists($stagedFile['full_path'])) {
            unlink($stagedFile['full_path']);
        }
        $success = false;
        $message = $e->getMessage();
    }
}

if (isset($_POST['cancel-transfer-request'])) {
    $requestId = sanitize(decipher($_POST['data-verifier'] ?? null));
    $showAlert = true;
    $success = false;

    try {
        if (empty($requestId)) {
            throw new Exception('Invalid transfer request selected.');
        }

        $request = getTransferRequest($requestId);
        if (!$request || $request['employee_id'] != $userId) {
            throw new Exception('The requested transfer request could not be found.');
        }

        if ($request['status'] !== 'Pending') {
            throw new Exception('Only pending transfer requests can be canceled.');
        }

        beginTransaction();

        $result = deleteTransferRequest($requestId, $userId);

        if ($result === false) {
            throw new Exception('We encountered an error canceling your request. Please try again later.');
        }

        commit();

        // Unlink attachment
        if (!empty($request['attachment_path']) && file_exists(root() . "/" . $request['attachment_path'])) {
            unlink(root() . "/" . $request['attachment_path']);
        }

        $success = true;
        $message = 'Your transfer request has been canceled successfully.';
        createSystemLog($stationId, $userId, 'Canceled transfer request', $userId, clientIp());

    } catch (Exception $e) {
        rollBack();
        $success = false;
        $message = $e->getMessage();
    }
}

// ========== PERFORMANCE MANAGEMENT ==========

if (isset($_POST['create-ipcrf'])) {
    $employeeId = (int) sanitize(decipher($_POST['verifier'] ?? null));
    $cycleId = (int) sanitize(decipher($_POST['cycle-verifier'] ?? null));
    $validatorId = !empty($_POST['validator_id']) ? (int) sanitize($_POST['validator_id']) : null;
    $kraTitles = $_POST['kra_title'] ?? [];
    $kraWeights = $_POST['kra_weight'] ?? [];
    $showAlert = true;
    $success = false;

    try {
        if (empty($employeeId) || empty($cycleId)) {
            throw new Exception('Invalid request parameters.');
        }

        if (empty($kraTitles) || empty($kraWeights)) {
            throw new Exception('Please define at least one Key Result Area.');
        }

        $totalWeight = array_sum(array_map('intval', $kraWeights));
        if ($totalWeight !== 100) {
            throw new Exception("KRA weights must total 100%. Current total: {$totalWeight}%.");
        }

        if (pmIpcrfByEmployee($employeeId, $cycleId)) {
            throw new Exception('You already have an IPCRF for this cycle.');
        }

        beginTransaction();

        $ipcrfId = createPmIpcrf($cycleId, $employeeId, $validatorId);
        if (!$ipcrfId) {
            throw new Exception('Failed to create IPCRF record.');
        }

        foreach ($kraTitles as $i => $title) {
            $title = sanitize($title);
            $weight = (int) sanitize($kraWeights[$i]);
            if (empty($title) || $weight <= 0) continue;

            $result = createPmKra($ipcrfId, $title, $weight, $i + 1);
            if (!$result) {
                throw new Exception('Failed to create KRA: ' . $title);
            }
        }

        commit();

        $success = true;
        $message = 'IPCRF has been created successfully. You can now add objectives to each KRA.';
        createSystemLog($stationId, $userId, 'Created IPCRF', $employeeId, clientIp());

        redirect(customUri('pis', 'IPCRF Details', $ipcrfId));

    } catch (Exception $e) {
        rollBack();
        $message = $e->getMessage();
    }
}

if (isset($_POST['save-objective'])) {
    $kraId = (int) sanitize(decipher($_POST['verifier'] ?? null));
    $ipcrfId = (int) sanitize(decipher($_POST['ipcrf-verifier'] ?? null));
    $objective = sanitize($_POST['objective'] ?? '');
    $indicator = sanitize($_POST['performance_indicator'] ?? '');
    $target = sanitize($_POST['target'] ?? '');
    $weight = (int) sanitize($_POST['weight'] ?? 0);
    $timeline = sanitize($_POST['timeline'] ?? '');
    $showAlert = true;
    $success = false;

    try {
        if (empty($kraId) || empty($ipcrfId) || empty($objective)) {
            throw new Exception('Objective is required.');
        }

        if ($weight <= 0 || $weight > 100) {
            throw new Exception('Weight must be between 1 and 100.');
        }

        if (empty($indicator)) {
            throw new Exception('Performance Indicator is required.');
        }

        if (empty($timeline)) {
            throw new Exception('Timeline is required.');
        }

        $kra = pmKra($kraId);
        $ipcrf = pmIpcrf($ipcrfId);

        if (!$kra || !$ipcrf || (int) $ipcrf['employee_id'] !== $userId) {
            throw new Exception('Invalid request.');
        }

        if ($ipcrf['status'] !== 'Draft' && $ipcrf['status'] !== 'Returned') {
            throw new Exception('Cannot add objectives to a submitted IPCRF.');
        }

        $existingCount = count(pmObjectives($kraId));
        $result = createPmObjective($kraId, $ipcrfId, $objective, $indicator, $target, $weight, $timeline, $existingCount + 1);

        if (!$result) {
            throw new Exception('Failed to save objective.');
        }

        $success = true;
        $message = 'Objective has been added successfully.';
        createSystemLog($stationId, $userId, 'Added IPCRF objective', $userId, clientIp());

    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}

if (isset($_POST['delete-objective'])) {
    $kraId = (int) sanitize(decipher($_POST['verifier'] ?? null));
    $objectiveId = (int) sanitize(decipher($_POST['objective-verifier'] ?? null));
    $showAlert = true;
    $success = false;

    try {
        if (empty($kraId) || empty($objectiveId)) {
            throw new Exception('Invalid request.');
        }

        $obj = pmObjective($objectiveId);
        if (!$obj || (int) $obj['kra_id'] !== $kraId) {
            throw new Exception('Objective not found.');
        }

        $ipcrf = pmIpcrf($obj['ipcrf_id']);
        if (!$ipcrf || (int) $ipcrf['employee_id'] !== $userId) {
            throw new Exception('Unauthorized.');
        }

        if ($ipcrf['status'] !== 'Draft' && $ipcrf['status'] !== 'Returned') {
            throw new Exception('Cannot delete objectives from a submitted IPCRF.');
        }

        $result = deletePmObjective($objectiveId);
        if (!$result) {
            throw new Exception('Failed to delete objective.');
        }

        $success = true;
        $message = 'Objective has been deleted.';
        createSystemLog($stationId, $userId, 'Deleted IPCRF objective', $userId, clientIp());

    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}

if (isset($_POST['submit-ipcrf'])) {
    $ipcrfId = (int) sanitize(decipher($_POST['verifier'] ?? null));
    $remarks = sanitize($_POST['ratee_remarks'] ?? '');
    $showAlert = true;
    $success = false;

    try {
        $ipcrf = pmIpcrf($ipcrfId);
        if (!$ipcrf || (int) $ipcrf['employee_id'] !== $userId) {
            throw new Exception('Invalid request.');
        }

        if ($ipcrf['status'] !== 'Draft' && $ipcrf['status'] !== 'Returned') {
            throw new Exception('This IPCRF cannot be submitted.');
        }

        $kras = pmKras($ipcrfId);
        if (empty($kras)) {
            throw new Exception('Cannot submit an IPCRF without KRAs.');
        }

        $hasObjectives = false;
        foreach ($kras as $kra) {
            if (!empty(pmObjectives($kra['id']))) {
                $hasObjectives = true;
                break;
            }
        }

        if (!$hasObjectives) {
            throw new Exception('Cannot submit an IPCRF without objectives. Add at least one objective.');
        }

        $result = updatePmIpcrfStatus($ipcrfId, 'Submitted', $remarks, 'ratee_remarks');
        if ($result === false) {
            throw new Exception('Failed to submit IPCRF.');
        }

        $success = true;
        $message = 'IPCRF has been submitted for validation.';
        createSystemLog($stationId, $userId, 'Submitted IPCRF for validation', $userId, clientIp());

    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}

if (isset($_POST['save-ratings'])) {
    $ipcrfId = (int) sanitize(decipher($_POST['verifier'] ?? null));
    $validatorRemarks = sanitize($_POST['validator_remarks'] ?? '');
    $objIds = $_POST['obj_id'] ?? [];
    $ratingsQ = $_POST['rating_q'] ?? [];
    $ratingsE = $_POST['rating_e'] ?? [];
    $ratingsT = $_POST['rating_t'] ?? [];
    $objRemarks = $_POST['obj_remarks'] ?? [];
    $showAlert = true;
    $success = false;

    try {
        $ipcrf = pmIpcrf($ipcrfId);
        if (!$ipcrf || (int) $ipcrf['validator_id'] !== $userId) {
            throw new Exception('Unauthorized.');
        }

        beginTransaction();

        foreach ($objIds as $i => $encId) {
            $objId = (int) sanitize(decipher($encId));
            $q = !empty($ratingsQ[$i]) ? (float) $ratingsQ[$i] : null;
            $e2 = !empty($ratingsE[$i]) ? (float) $ratingsE[$i] : null;
            $t = !empty($ratingsT[$i]) ? (float) $ratingsT[$i] : null;
            $rem = sanitize($objRemarks[$i] ?? '');

            if ($q !== null && $e2 !== null && $t !== null) {
                updatePmObjectiveRating($objId, $q, $e2, $t, $rem);
            }
        }

        if (!empty($validatorRemarks)) {
            update('pm_ipcrf', ['validator_remarks' => $validatorRemarks], '`id` = ?', [$ipcrfId]);
        }

        commit();

        $success = true;
        $message = 'Ratings have been saved successfully.';
        createSystemLog($stationId, $userId, 'Saved IPCRF ratings', $ipcrf['employee_id'], clientIp());

    } catch (Exception $e) {
        rollBack();
        $message = $e->getMessage();
    }
}

if (isset($_POST['validate-ipcrf'])) {
    $ipcrfId = (int) sanitize(decipher($_POST['verifier'] ?? null));
    $validatorRemarks = sanitize($_POST['validator_remarks'] ?? '');
    $objIds = $_POST['obj_id'] ?? [];
    $ratingsQ = $_POST['rating_q'] ?? [];
    $ratingsE = $_POST['rating_e'] ?? [];
    $ratingsT = $_POST['rating_t'] ?? [];
    $objRemarks = $_POST['obj_remarks'] ?? [];
    $showAlert = true;
    $success = false;

    try {
        $ipcrf = pmIpcrf($ipcrfId);
        if (!$ipcrf || (int) $ipcrf['validator_id'] !== $userId) {
            throw new Exception('Unauthorized.');
        }

        if ($ipcrf['status'] !== 'Submitted' && $ipcrf['status'] !== 'Validated') {
            throw new Exception('This IPCRF cannot be validated.');
        }

        beginTransaction();

        foreach ($objIds as $i => $encId) {
            $objId = (int) sanitize(decipher($encId));
            $q = !empty($ratingsQ[$i]) ? (float) $ratingsQ[$i] : null;
            $e2 = !empty($ratingsE[$i]) ? (float) $ratingsE[$i] : null;
            $t = !empty($ratingsT[$i]) ? (float) $ratingsT[$i] : null;
            $rem = sanitize($objRemarks[$i] ?? '');

            if ($q === null || $e2 === null || $t === null) {
                throw new Exception('All objectives must be rated (Q, E, T) before validation.');
            }

            updatePmObjectiveRating($objId, $q, $e2, $t, $rem);
        }

        $finalRating = pmComputeFinalRating($ipcrfId);
        $adjectival = pmAdjectivalRating($finalRating);

        updatePmIpcrfFinalRating($ipcrfId, $finalRating, $adjectival);
        updatePmIpcrfStatus($ipcrfId, 'Validated', $validatorRemarks, 'validator_remarks');
        updatePmIpcrfPhase($ipcrfId, 3);

        commit();

        $success = true;
        $message = "IPCRF has been validated. Final Rating: {$finalRating} ({$adjectival}).";
        createSystemLog($stationId, $userId, 'Validated IPCRF', $ipcrf['employee_id'], clientIp());

    } catch (Exception $e) {
        rollBack();
        $message = $e->getMessage();
    }
}

if (isset($_POST['return-ipcrf'])) {
    $ipcrfId = (int) sanitize(decipher($_POST['verifier'] ?? null));
    $validatorRemarks = sanitize($_POST['validator_remarks'] ?? '');
    $showAlert = true;
    $success = false;

    try {
        $ipcrf = pmIpcrf($ipcrfId);
        if (!$ipcrf || (int) $ipcrf['validator_id'] !== $userId) {
            throw new Exception('Unauthorized.');
        }

        $result = updatePmIpcrfStatus($ipcrfId, 'Returned', $validatorRemarks, 'validator_remarks');
        if ($result === false) {
            throw new Exception('Failed to return IPCRF.');
        }

        $success = true;
        $message = 'IPCRF has been returned to the ratee for revision.';
        createSystemLog($stationId, $userId, 'Returned IPCRF to ratee', $ipcrf['employee_id'], clientIp());

    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}