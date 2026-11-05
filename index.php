<?php
$host = 'localhost';
$db   = 'stemmate_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("<div style='color:red; padding:20px;'>Database Connection Error: " . $e->getMessage() . "</div>");
}

$message = "";
$error = "";
$action = $_POST['action'] ?? 's1_signin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'do_signin') {
        $action = 's2_home';
    }
    elseif ($action === 'go_filter') {
        $action = 's3_filter';
    }
    elseif ($action === 'apply_filter') {
        $action = 's4_results';
    }
    elseif ($action === 'view_activity') {
        $action = 's5_activity_detail';
    }
    elseif ($action === 'save_offline') {
        $activityId = $_POST['activity_id'] ?? 1;
        $stmt = $pdo->prepare("INSERT INTO saved_activities (activity_id) VALUES (?)");
        $stmt->execute([$activityId]);
        $action = 's6_saved_confirm';
    }
    elseif ($action === 'go_saved_list') {
        $action = 's7_saved_activities';
    }
    elseif ($action === 'start_create_plan') {
        $action = 's8_session_plan_draft';
    }
    elseif ($action === 'submit_plan') {
        $s1 = trim($_POST['step_1'] ?? '');
        $s2 = trim($_POST['step_2'] ?? '');
        $s3 = trim($_POST['step_3'] ?? '');
        $s4 = trim($_POST['step_4'] ?? '');
        $s5 = trim($_POST['step_5'] ?? '');
        $safety = trim($_POST['safety_notes'] ?? '');

        if (empty($s1) || empty($s2) || empty($s3) || empty($s4) || empty($s5) || empty($safety)) {
            $error = "Validation Error [QR-03]: 2 problems to fix. All 5 steps and safety notes must be completed.";
            $action = 's9_errors';
        } else {
            $stmt = $pdo->prepare("INSERT INTO session_plans (activity_id, step_1, step_2, step_3, step_4, step_5, safety_notes, status) VALUES (1, ?, ?, ?, ?, ?, ?, 'queued_sync')");
            $stmt->execute([$s1, $s2, $s3, $s4, $s5, $safety]);
            $action = 's10_plan_saved_device';
        }
    }
    elseif ($action === 'go_sync_status') {
        $action = 's11_sync_status';
    }
    elseif ($action === 'simulate_sync_fail') {
        $action = 's12_sync_failed';
    }
    elseif ($action === 'simulate_conflict') {
        $action = 's13_two_versions';
    }
    elseif ($action === 'resolve_conflict') {
        $resolution = $_POST['resolution'] ?? 'keep_both';
        $stmt = $pdo->prepare("UPDATE session_plans SET status = 'synced' ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $action = 's14_plan_synced';
    }
    elseif ($action === 'view_volunteer') {
        $action = 's15_volunteer_view';
    }
    elseif ($action === 'sign_out') {
        $action = 's16_signed_out';
    }
}

$savedList = $pdo->query("SELECT sa.*, a.title, a.level, a.duration_mins FROM saved_activities sa JOIN activities a ON sa.activity_id = a.id")->fetchAll();
$latestPlan = $pdo->query("SELECT * FROM session_plans ORDER BY id DESC LIMIT 1")->fetch();
$activity = $pdo->query("SELECT * FROM activities WHERE id = 1")->fetch();
$activityList = $pdo->query("SELECT * FROM activities")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>STEMMate Namibia - Complete Vertical Slice </title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7f6; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 650px; background: #fff; margin: 0 auto; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-top: 5px solid #004d40; }
        h1 { color: #004d40; font-size: 22px; border-bottom: 2px solid #00796b; padding-bottom: 8px; }
        h3 { color: #00796b; }
        .status-bar { background: #e0f2f1; padding: 10px 14px; font-size: 13px; font-weight: bold; border-left: 4px solid #00796b; margin-bottom: 15px; display: flex; justify-content: space-between; border-radius: 4px; }
        .alert-error { background: #ffebee; color: #c62828; padding: 12px; border-left: 4px solid #c62828; margin-bottom: 15px; font-size: 14px; border-radius: 4px; }
        .alert-success { background: #e8f5e9; color: #2e7d32; padding: 12px; border-left: 4px solid #2e7d32; margin-bottom: 15px; font-size: 14px; border-radius: 4px; }
        label { display: block; margin-top: 12px; font-weight: bold; font-size: 14px; color: #004d40; }
        input[type="text"], textarea, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #b0bec5; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        button { background: #00796b; color: white; border: none; padding: 12px 20px; margin-top: 15px; cursor: pointer; border-radius: 4px; font-weight: bold; font-size: 14px; width: 100%; }
        button:hover { background: #004d40; }
        .btn-secondary { background: #546e7a; }
        .btn-secondary:hover { background: #37474f; }
        .card { background: #fafafa; border: 1px solid #cfd8dc; padding: 15px; margin-top: 15px; border-radius: 6px; border-left: 4px solid #00796b; }
        .meta-tag { display: inline-block; background: #efebe9; color: #4e342e; padding: 4px 10px; font-size: 12px; border-radius: 4px; margin-right: 6px; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="status-bar">
        <span>Connection: <span style="color: #2e7d32;">Online / Sync Ready</span></span>
        <span>PIN Protected </span>
    </div>

    <h1>STEMMate Namibia — Complete User Flow</h1>

    <?php if (!empty($error) && $action !== 's9_errors'): ?>
        <div class="alert-error"><strong>Error:</strong> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- S1: SIGN IN PAGE -->
    <?php if ($action === 's1_signin'): ?>
        <h3>S1: Sign In Page</h3>
        <p>Enter facilitator credentials or select language.</p>
        <form method="POST">
            <input type="hidden" name="action" value="do_signin">
            <label>Facilitator Username:</label>
            <input type="text" value="Kasanga Leonel" readonly style="background:#eee;">
            <label>Select Language:</label>
            <select name="language"><option value="English">English</option></select>
            <button type="submit">Sign In</button>
        </form>

  
    <?php elseif ($action === 's2_home'): ?>
        <h3>Home Page</h3>
        <p>Welcome, Kasanga Leonel. Select an action to proceed.</p>
        <form method="POST"><input type="hidden" name="action" value="go_filter"><button type="submit">Find an Activity </button></form>
        <form method="POST"><input type="hidden" name="action" value="go_saved_list"><button type="submit" class="btn-secondary" style="margin-top:10px;"> My Saved Activities & Plans </button></form>
        <form method="POST"><input type="hidden" name="action" value="sign_out"><button type="submit" style="background:#c62828; margin-top:10px;">Sign Out </button></form>

   
    <?php elseif ($action === 's3_filter'): ?>
        <h3>Filter Activities</h3>
        <form method="POST">
            <input type="hidden" name="action" value="apply_filter">
            <label>Level:</label>
            <select name="level"><option value="Grade 5-7">Grade 5-7</option></select>
            <label>Duration:</label>
            <select name="duration"><option value="<30">&lt; 30 minutes</option></select>
            <label>Available Materials in Kit:</label>
            <input type="text" value="Copper wire, 1.5V battery, LED bulb" readonly style="background:#eee;">
            <button type="submit">Apply Filters</button>
        </form>


    <?php elseif ($action === 's4_results'): ?>
        <h3>Results </h3>
        <div class="card">
            <h4>Simple Circuit Challenge</h4>
            <span class="meta-tag">Grade 5-7</span><span class="meta-tag">25 min</span>
            <p style="font-size:13px; color:#555; margin-top:8px;"><em>1 more match is hidden because switch components are missing from your kit.</em></p>
            <form method="POST"><input type="hidden" name="action" value="view_activity"><button type="submit">View Activity Detail</button></form>
        </div>

  
    <?php elseif ($action === 's5_activity_detail'): ?>
        <h3>Activity Detail</h3>
        <div class="card">
            <h4><?php echo htmlspecialchars($activity['title']); ?></h4>
            <p><strong>Topic:</strong> <?php echo htmlspecialchars($activity['topic']); ?></p>
            <p><strong>Materials:</strong> <?php echo htmlspecialchars($activity['materials_required']); ?></p>
            <p><strong>Safety Notes:</strong> <?php echo htmlspecialchars($activity['safety_notes']); ?></p>
            <p><strong>Download Size:</strong> <?php echo htmlspecialchars($activity['download_size_mb']); ?> MB</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="save_offline">
                <input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
                <button type="submit"> Save for Offline Use (0.3 MB) </button>
            </form>
        </div>
<?php elseif ($action === 's4_results'): ?>
        <h3>Results</h3>
        
        <?php foreach ($activityList as $act): ?>
        <div class="card">
            <h4><?php echo htmlspecialchars($act['title']); ?></h4>
            <span class="meta-tag"><?php echo htmlspecialchars($act['level']); ?></span>
            <span class="meta-tag"><?php echo htmlspecialchars($act['duration_mins']); ?> min</span>
            <span class="meta-tag"><?php echo htmlspecialchars($act['download_size_mb']); ?> MB</span>
            <p style="font-size:13px; color:#555; margin-top:8px;"><strong>Topic:</strong> <?php echo htmlspecialchars($act['topic']); ?></p>
            <form method="POST" style="margin-top:10px;">
                <input type="hidden" name="action" value="view_activity">
                <input type="hidden" name="activity_id" value="<?php echo $act['id']; ?>">
                <button type="submit">View Activity Detail</button>
            </form>
        </div>
        <?php endforeach; ?>

        <div style="margin-top:15px; font-size:13px; color:#555; background:#fafafa; padding:10px; border-radius:4px; border:1px dashed #b0bec5;">
            <em>1 more match is hidden because switch components are missing from your kit.</em>
        </div>

        <form method="POST" style="margin-top:15px;">
            <input type="hidden" name="action" value="go_filter">
            <button type="submit" class="btn-secondary">Back to Filters</button>
        </form>
    <?php elseif ($action === 's6_saved_confirm'): ?>
        <h3>Saved for Offline Use</h3>
        <div class="alert-success">Activity successfully downloaded and cached on device storage (0.3 MB).</div>
        <form method="POST"><input type="hidden" name="action" value="go_saved_list"><button type="submit">View My Saved Activities</button></form>

    <!-- S7: MY SAVED ACTIVITIES -->
    <?php elseif ($action === 's7_saved_activities'): ?>
        <h3>My Saved Activities</h3>
        <div class="card">
            <span class="meta-tag" style="background:#c8e6c9; color:#2e7d32;">Available Offline</span>
            <h4>Simple Circuit Challenge</h4>
            <p>Grade 5-7 • 25 mins</p>
            <form method="POST"><input type="hidden" name="action" value="start_create_plan"><button type="submit">Create Session Plan from Activity [FR-03]</button></form>
        </div>
        <form method="POST" style="margin-top:15px;"><input type="hidden" name="action" value="go_sync_status"><button type="submit" class="btn-secondary">Check Synchronization Status [FR-05]</button></form>

    <!-- S8: SESSION PLAN (DRAFT) -->
    <?php elseif ($action === 's8_session_plan_draft' || $action === 's9_errors'): ?>
        <h3>Session Plan Draft</h3>
        <p>Autosave is active. Provide all 5 steps and safety notes.</p>
        
        <?php if ($action === 's9_errors'): ?>
            <div class="alert-error"><strong>⚠️ 2 problems to fix:</strong> Please ensure all 5 step fields and safety notes are fully filled out.</div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="submit_plan">
            <label>Step 1:</label><input type="text" name="step_1" placeholder="Introduce components">
            <label>Step 2:</label><input type="text" name="step_2" placeholder="Distribute wires and batteries">
            <label>Step 3:</label><input type="text" name="step_3" placeholder="Connect terminals">
            <label>Step 4:</label><input type="text" name="step_4" placeholder="Observe LED illumination">
            <label>Step 5:</label><input type="text" name="step_5" placeholder="Clean up equipment">
            <label>Safety Notes:</label><textarea name="safety_notes" rows="2" placeholder="Safety warnings..."></textarea>
            <button type="submit">Save & Complete Plan</button>
        </form>

    <!-- S10: PLAN SAVED ON DEVICE (OFFLINE QUEUED) -->
    <?php elseif ($action === 's10_plan_saved_device'): ?>
        <h3>Plan Saved on Device</h3>
        <div class="alert-success">Plan saved locally and queued for offline sync.</div>
        <form method="POST"><input type="hidden" name="action" value="go_sync_status"><button type="submit">Proceed to Sync Status</button></form>

    <!-- S11: SYNC STATUS -->
    <?php elseif ($action === 's11_sync_status'): ?>
        <h3>Sync Status [FR-05]</h3>
        <div class="card"><strong>Queue Status:</strong> 1 plan waiting to sync.</div>
        <form method="POST" style="margin-top:10px;"><input type="hidden" name="action" value="simulate_sync_fail"><button type="submit" class="btn-secondary">Simulate Sync Interruption (Test S12)</button></form>
        <form method="POST" style="margin-top:10px;"><input type="hidden" name="action" value="simulate_conflict"><button type="submit" style="background:#f57c00;">Simulate Conflict (Test S13)</button></form>

    <!-- S12: SYNC DID NOT FINISH -->
    <?php elseif ($action === 's12_sync_failed'): ?>
        <h3>S12: Sync Did Not Finish</h3>
        <div class="alert-error"><strong>⚠️ Connection lost:</strong> Upload was interrupted. Local data is fully protected on your device.</div>
        <form method="POST"><input type="hidden" name="action" value="go_sync_status"><button type="submit">🔄 Retry Sync</button></form>
        <form method="POST" style="margin-top:10px;"><input type="hidden" name="action" value="simulate_conflict"><button type="submit" class="btn-secondary">Check Version Conflicts</button></form>

    <!-- S13: TWO VERSIONS FOUND (CONFLICT) -->
    <?php elseif ($action === 's13_two_versions'): ?>
        <h3>Two Versions Found [FR-05]</h3>
        <div class="alert-error"><strong>⚠️ Conflict:</strong> A server version exists alongside your local edit.</div>
        <form method="POST">
            <input type="hidden" name="action" value="resolve_conflict">
            <div class="card"><label style="margin-top:0;"><input type="radio" name="resolution" value="keep_both" checked> <strong>Keep Both (Recommended)</strong> — Preserves local edits and saves server version.</label></div>
            <div class="card" style="margin-top:10px;"><label style="margin-top:0;"><input type="radio" name="resolution" value="overwrite"> Overwrite local version.</label></div>
            <button type="submit" style="background:#00796b;">Confirm Resolution</button>
        </form>

    <!-- S14: PLAN SYNCED SUCCESS -->
    <?php elseif ($action === 's14_plan_synced'): ?>
        <h3>Plan Synced Successfully</h3>
        <div class="alert-success">Plan synchronized successfully with central server!</div>
        <form method="POST"><input type="hidden" name="action" value="view_volunteer"><button type="submit">View Volunteer Assistant Screen [FR-06]</button></form>

    <!-- S15: PLAN VIEW (VOLUNTEER ASSISTANT) -->
    <?php elseif ($action === 's15_volunteer_view'): ?>
        <h3>Volunteer Assistant Plan View</h3>
        <div class="card">
            <h4>Simple Circuit Challenge (Session Plan)</h4>
            <p><strong>Duration:</strong> 25 mins</p>
            <p><strong>Steps:</strong><br>
            1. <?php echo htmlspecialchars($latestPlan['step_1'] ?? 'Introduce components'); ?><br>
            2. <?php echo htmlspecialchars($latestPlan['step_2'] ?? 'Distribute wires'); ?><br>
            3. <?php echo htmlspecialchars($latestPlan['step_3'] ?? 'Connect terminals'); ?><br>
            4. <?php echo htmlspecialchars($latestPlan['step_4'] ?? 'Observe LED'); ?><br>
            5. <?php echo htmlspecialchars($latestPlan['step_5'] ?? 'Clean up'); ?></p>
            <p><strong>Safety Warnings:</strong> <?php echo htmlspecialchars($latestPlan['safety_notes'] ?? 'None'); ?></p>
        </div>
        <form method="POST"><input type="hidden" name="action" value="sign_out"><button type="submit" class="btn-secondary">Finish & Lock Device</button></form>

    <!-- S16: SIGNED OUT / PIN LOCKED -->
    <?php elseif ($action === 's16_signed_out'): ?>
        <h3>Signed Out / Device Locked</h3>
        <div class="alert-success">Device securely signed out. All local plans are protected behind PIN lock. No pupil data is exposed.</div>
        <form method="POST"><input type="hidden" name="action" value="s1_signin"><button type="submit">Return to Sign In [S1]</button></form>
    <?php endif; ?>

</div>
</body>
</html>