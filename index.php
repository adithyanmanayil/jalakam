<?php
require_once "connection.php";

// -------------------------------------------------------------------------
// 1. FILTER SEARCH MECHANISM CORE LOGIC (Local Body Removed)
// -------------------------------------------------------------------------
$selected_district = '';
$selected_course = '';
$selected_type = '';
$selected_gender = '';
$search_executed = false;
$result_set = null;

if (isset($_POST['search_schools'])) {
    $selected_district = mysqli_real_escape_string($conn, $_POST['district'] ?? '');
    $selected_course = mysqli_real_escape_string($conn, $_POST['course'] ?? '');
    $selected_type = mysqli_real_escape_string($conn, $_POST['school_type'] ?? '');
    $selected_gender = mysqli_real_escape_string($conn, $_POST['gender'] ?? '');
    $search_executed = true;

    $conditions = [
        "district = '$selected_district'",
        "FIND_IN_SET('$selected_course', course_codes) > 0"
    ];

    if (!empty($selected_type)) {
        $conditions[] = "school_type = '$selected_type'";
    }
    if (!empty($selected_gender)) {
        $conditions[] = "school_gender = '$selected_gender'";
    }

    $query = "SELECT school_code, school_name, school_type, local_body, school_gender 
              FROM schools 
              WHERE " . implode(' AND ', $conditions) . " ORDER BY school_code ASC";
              
    $result_set = mysqli_query($conn, $query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ജാലകം</title>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        :root {
            --bg-main: #F8FAFC;
            --card-bg: #FFFFFF;
            --card-border: rgba(15, 23, 42, 0.06);
            
            /* Slate Accent Palette */
            --accent-primary: #0F172A;
            --accent-secondary: #475569;
            --accent-highlight: #38BDF8;
            --accent-soft: #F1F5F9;
            
            --text-main: #0F172A;
            --text-muted: #64748B;
            
            --radius-main: 16px;
            --radius-inner: 10px;
            --smooth-transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            letter-spacing: -0.01em;
        }

        body {
            background: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
            padding: 50px 16px 140px 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            -webkit-tap-highlight-color: transparent;
            -webkit-font-smoothing: antialiased;
        }

        .container { width: 100%; max-width: 760px; }
        
        header { text-align: center; margin: 10px 0 40px 0; animation: fadeIn 0.5s ease-out; }
        header h1 { 
            font-size: 2.8rem; 
            font-weight: 800; 
            color: var(--accent-primary);
            letter-spacing: -0.03em; 
            margin-bottom: 6px;
        }
        header p { font-size: 1rem; color: var(--text-muted); font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; }

        .premium-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-main);
            padding: 32px;
            box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.04), 0 1px 3px rgba(15, 23, 42, 0.02);
            margin-bottom: 24px;
            animation: fadeIn 0.5s ease-out;
            position: relative;
        }

        .search-form { display: grid; grid-template-columns: 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--accent-secondary); padding-left: 2px; }

        select, input[type="text"] {
            width: 100%; padding: 13px 16px; font-size: 0.95rem; font-weight: 500; background: #FFFFFF;
            border: 1px solid #E2E8F0; border-radius: var(--radius-inner); color: var(--text-main); outline: none; 
            transition: var(--smooth-transition); -webkit-appearance: none;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }
        select { background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'></polyline></svg>"); background-repeat: no-repeat; background-position: right 14px center; background-size: 15px; cursor: pointer; padding-right: 40px; }
        select:focus, input[type="text"]:focus { border-color: var(--accent-primary); box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.06); }

        .radio-segment { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; background: var(--accent-soft); padding: 4px; border-radius: var(--radius-inner); }
        .radio-label {
            background: transparent; border: none; padding: 11px; border-radius: calc(var(--radius-inner) - 2px);
            display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 600;
            cursor: pointer; transition: var(--smooth-transition); color: var(--text-muted);
        }
        .radio-label input[type="radio"] { display: none; }
        .radio-label:has(input[type="radio"]:checked) {
            background: #FFFFFF; 
            color: var(--text-main);
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
        }

        .btn-submit {
            background: var(--accent-primary); color: #ffffff; border: none; padding: 14px; 
            font-size: 0.95rem; font-weight: 700; border-radius: var(--radius-inner); cursor: pointer; 
            transition: var(--smooth-transition); width: 100%; margin-top: 5px; text-transform: uppercase; letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }
        .btn-submit:hover { background: #1e293b; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(15, 23, 42, 0.2); }
        .btn-submit:active { transform: translateY(0); }

        .results-container h2 { font-size: 1.4rem; margin-bottom: 20px; color: var(--text-main); font-weight: 700; letter-spacing: -0.3px; }
        
        .select-all-bar {
            display: flex; align-items: center; gap: 10px; background: var(--accent-soft); 
            padding: 12px 16px; border-radius: var(--radius-inner); margin-bottom: 16px; font-weight: 700; font-size: 0.9rem;
            color: var(--text-main);
        }

        .school-card-item {
            background: #FFFFFF; border: 1px solid rgba(15, 23, 42, 0.06); border-radius: var(--radius-inner);
            padding: 18px; margin-bottom: 12px; display: flex; align-items: flex-start; gap: 14px;
            transition: var(--smooth-transition);
            animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .school-card-item:hover { transform: translateY(-2px); border-color: rgba(15, 23, 42, 0.15); box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04); }
        .school-card-item:has(.school-chk:checked) { border-color: var(--accent-primary); background: rgba(15, 23, 42, 0.01); }

        .checkbox-container { padding-top: 2px; }
        input[type="checkbox"].school-chk, input[type="checkbox"]#master-select {
            appearance: none; -webkit-appearance: none; width: 20px; height: 20px; 
            border: 1.5px solid #CBD5E1; border-radius: 5px; background: #FFFFFF; cursor: pointer; position: relative;
            transition: var(--smooth-transition);
        }
        input[type="checkbox"]:checked { background: var(--accent-primary); border-color: var(--accent-primary); }
        input[type="checkbox"]:checked::after {
            content: ''; position: absolute; left: 6px; top: 2px; width: 4px; height: 9px;
            border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg);
        }

        .school-details { flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .school-title { font-size: 1.05rem; font-weight: 600; color: var(--text-main); line-height: 1.4; }
        
        .badge-row { display: flex; flex-wrap: wrap; gap: 6px; }
        .badge { font-size: 0.75rem; font-weight: 600; padding: 4px 8px; border-radius: 5px; background: var(--accent-soft); color: var(--text-muted); }
        .badge.code { background: #FFF7ED; color: #C2410C; font-family: monospace; font-weight: 700; }
        .badge.type { background: #EFF6FF; color: #1D4ED8; }

        /* Floating Light Clean Action Bar */
        .floating-action-bar {
            position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%);
            width: calc(100% - 24px); max-width: 500px; background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(15, 23, 42, 0.08); border-radius: 16px;
            padding: 12px 20px; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.12); 
            transition: bottom 0.4s cubic-bezier(0.25, 1, 0.5, 1);
            z-index: 999;
        }
        .floating-action-bar.active { bottom: 24px; }
        .bar-info { font-weight: 700; color: var(--text-main); font-size: 0.95rem; }
        .bar-buttons { display: flex; gap: 8px; }
        .btn-bar { padding: 10px 18px; font-size: 0.9rem; font-weight: 700; border-radius: 8px; cursor: pointer; border: none; transition: var(--smooth-transition); }
        .btn-cancel { background: var(--accent-soft); color: var(--text-muted); }
        .btn-cancel:hover { background: #E2E8F0; color: var(--text-main); }
        .btn-pdf { background: var(--accent-primary); color: white; }
        .btn-pdf:hover { background: #1e293b; }

        .no-results { text-align: center; padding: 40px 10px; color: var(--text-muted); font-size: 0.95rem; }

        /* HIGH-CONTRAST TARGET PDF LAYOUT COMPONENT */
        #hidden-pdf-template {
            position: absolute; left: -9999px; top: -9999px;
            width: 750px; background: #ffffff; padding: 35px; color: #000000;
        }
        .print-title { text-align: center; font-size: 24px; font-weight: bold; color: #1e293b; margin-bottom: 4px; }
        .print-subtitle { text-align: center; font-size: 13px; color: #64748b; margin-bottom: 25px; font-style: italic; }
        table.pdf-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
        table.pdf-table th, table.pdf-table td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; }
        table.pdf-table th { background-color: #0F172A; color: white; font-weight: bold; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        @media (min-width: 650px) {
            body { padding: 60px 24px 140px 24px; }
            .search-form { grid-template-columns: repeat(2, 1fr); gap: 20px; }
            .full-width { grid-column: span 2; }
            .radio-segment { grid-template-columns: repeat(4, 1fr); }
            .btn-submit { grid-column: span 2; width: auto; justify-self: flex-end; padding: 12px 40px; }
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>ജാലകം</h1>
        <p>Higher Secondary Finder</p>
    </header>

    <div class="premium-card">
        <form method="POST" class="search-form">
            <div class="form-group">
                <label for="district">Select District *</label>
                <select name="district" id="district" required>
                    <option value="" disabled <?php echo empty($selected_district) ? 'selected' : ''; ?>>Choose a district...</option>
                    <?php
                    $dist_query = "SELECT DISTINCT district FROM schools ORDER BY district ASC";
                    $dist_result = mysqli_query($conn, $dist_query);
                    while($row = $dist_result->fetch_assoc()) {
                        $selected = ($selected_district === $row['district']) ? 'selected' : '';
                        echo "<option value='{$row['district']}' {$selected}>{$row['district']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="course">Select Course Combination *</label>
                <select name="course" id="course" required>
                    <option value="" disabled <?php echo empty($selected_course) ? 'selected' : ''; ?>>Choose a combination...</option>
                    <?php
                    $course_query = "SELECT course_code, course_name FROM courses ORDER BY course_code ASC";
                    $course_result = mysqli_query($conn, $course_query);
                    while($row = $course_result->fetch_assoc()) {
                        $selected = ($selected_course == $row['course_code']) ? 'selected' : '';
                        echo "<option value='{$row['course_code']}' {$selected}>{$row['course_code']} - {$row['course_name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group full-width">
                <label for="school_type">School Management Type</label>
                <select name="school_type" id="school_type">
                    <option value="">All Management Types</option>
                    <?php
                    $type_query = "SELECT DISTINCT school_type FROM schools WHERE school_type IS NOT NULL AND school_type != ''";
                    $type_result = mysqli_query($conn, $type_query);
                    while($row = $type_result->fetch_assoc()) {
                        $selected = ($selected_type === $row['school_type']) ? 'selected' : '';
                        echo "<option value='{$row['school_type']}' {$selected}>{$row['school_type']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group full-width">
                <label>Institution Category</label>
                <div class="radio-segment">
                    <label class="radio-label"><input type="radio" name="gender" value="" <?php echo empty($selected_gender) ? 'checked' : ''; ?>> All</label>
                    <label class="radio-label"><input type="radio" name="gender" value="Co-Education" <?php echo ($selected_gender === 'Co-Education') ? 'checked' : ''; ?>> Mixed</label>
                    <label class="radio-label"><input type="radio" name="gender" value="Girls" <?php echo ($selected_gender === 'Girls') ? 'checked' : ''; ?>> Girls</label>
                    <label class="radio-label"><input type="radio" name="gender" value="Boys" <?php echo ($selected_gender === 'Boys') ? 'checked' : ''; ?>> Boys</label>
                </div>
            </div>

            <button type="submit" name="search_schools" class="btn-submit">Search Schools</button>
        </form>
    </div>

    <?php if ($search_executed): ?>
        <div class="premium-card results-container">
            <h2>Available Institutions</h2>
            
            <?php if ($result_set && mysqli_num_rows($result_set) > 0): ?>
                <div id="pdf-form">
                    
                    <div class="select-all-bar">
                        <input type="checkbox" id="master-select">
                        <label for="master-select" style="cursor:pointer; width: 100%; padding-left: 4px;">Select All List Items</label>
                    </div>

                    <div class="cards-wrapper">
                        <?php 
                        $index = 0;
                        while($row = $result_set->fetch_assoc()): 
                            $delay = $index * 0.02;
                        ?>
                            <div class="school-card-item" style="animation-delay: <?php echo $delay; ?>s;"
                                 data-code="<?php echo htmlspecialchars($row['school_code']); ?>"
                                 data-name="<?php echo htmlspecialchars($row['school_name']); ?>"
                                 data-type="<?php echo htmlspecialchars($row['school_type']); ?>"
                                 data-lb="<?php echo htmlspecialchars($row['local_body']); ?>"
                                 data-gender="<?php echo htmlspecialchars($row['school_gender']); ?>">
                                <div class="checkbox-container">
                                    <input type="checkbox" class="school-chk">
                                </div>
                                <div class="school-details">
                                    <div class="school-title"><?php echo htmlspecialchars($row['school_name']); ?></div>
                                    <div class="badge-row">
                                        <span class="badge code"><?php echo htmlspecialchars($row['school_code']); ?></span>
                                        <span class="badge type"><?php echo htmlspecialchars($row['school_type']); ?></span>
                                        <span class="badge"><?php echo htmlspecialchars($row['local_body']); ?></span>
                                        <span class="badge"><?php echo htmlspecialchars($row['school_gender']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php 
                        $index++;
                        endwhile; 
                        ?>
                    </div>
                    
                    <div class="floating-action-bar" id="action-bar">
                        <div class="bar-info" id="selected-count">0 Selected</div>
                        <div class="bar-buttons">
                            <button type="button" class="btn-bar btn-cancel" id="btn-clear-selection">Cancel</button>
                            <button type="button" class="btn-bar btn-pdf" id="btn-download-pdf">Get PDF</button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-results">No institutions found matching those precise filter inputs.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div id="hidden-pdf-template">
    <div class="print-title">JALAKAM DIRECTORY ECOSYSTEM</div>
    <div class="print-subtitle">Selected Higher Secondary Schools Option List</div>
    <table class="pdf-table">
        <thead>
            <tr>
                <th style="width: 12%; text-align: center;">Code</th>
                <th style="width: 50%;">School Name</th>
                <th style="width: 13%; text-align: center;">Type</th>
                <th style="width: 13%; text-align: center;">Local Body</th>
                <th style="width: 12%; text-align: center;">Category</th>
            </tr>
        </thead>
        <tbody id="pdf-table-body">
        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const masterSelect = document.getElementById('master-select');
    const checkboxes = document.querySelectorAll('.school-chk');
    const actionBar = document.getElementById('action-bar');
    const countDisplay = document.getElementById('selected-count');
    const btnClear = document.getElementById('btn-clear-selection');
    const btnDownload = document.getElementById('btn-download-pdf');
    const pdfTableBody = document.getElementById('pdf-table-body');

    if(!masterSelect) return;

    function updateActionBar() {
        const checkedCount = document.querySelectorAll('.school-chk:checked').length;
        if (checkedCount > 0) {
            countDisplay.textContent = `${checkedCount} Selected`;
            actionBar.classList.add('active');
        } else {
            actionBar.classList.remove('active');
            masterSelect.checked = false;
        }
    }

    masterSelect.addEventListener('change', function() {
        checkboxes.forEach(chk => chk.checked = masterSelect.checked);
        updateActionBar();
    });

    checkboxes.forEach(chk => {
        chk.addEventListener('change', function() {
            if(!this.checked) masterSelect.checked = false;
            else if(document.querySelectorAll('.school-chk:checked').length === checkboxes.length) {
                masterSelect.checked = true;
            }
            updateActionBar();
        });
    });

    btnClear.addEventListener('click', function() {
        checkboxes.forEach(chk => chk.checked = false);
        masterSelect.checked = false;
        updateActionBar();
    });

    if (btnDownload) {
        btnDownload.addEventListener('click', function() {
            const checkedCards = document.querySelectorAll('.school-card-item:has(.school-chk:checked)');
            
            pdfTableBody.innerHTML = '';
            
            checkedCards.forEach(card => {
                const code = card.getAttribute('data-code');
                const name = card.getAttribute('data-name');
                const type = card.getAttribute('data-type');
                const lb = card.getAttribute('data-lb');
                const gender = card.getAttribute('data-gender');
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="text-align: center; font-weight: bold;">${code}</td>
                    <td>${name}</td>
                    <td style="text-align: center;">${type}</td>
                    <td style="text-align: center;">${lb}</td>
                    <td style="text-align: center;">${gender}</td>
                `;
                pdfTableBody.appendChild(tr);
            });

            const targetContainer = document.getElementById('hidden-pdf-template');
            
            window.html2canvas(targetContainer, { scale: 2 }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');
                
                const imgWidth = 210; 
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                
                pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
                pdf.save('Selected_PlusOne_Schools.pdf');
            });
        });
    }
});
</script>
</body>
</html>