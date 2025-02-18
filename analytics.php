<?php
session_start();
include('config.php');


// Get the selected year
$year = isset($_GET['year']) ? $_GET['year'] : 'all';
// Condition for SQL queries
$year_condition = ($year === 'all') ? "" : "YEAR(`dateCommitted`) = $year";

// YEARLY FILTER
$dateFilter_query = "SELECT DISTINCT YEAR(dateCommitted) AS year FROM crimemapping 
                    WHERE YEAR(dateCommitted) ORDER BY year DESC";
$dateFilter_result = $conn->query($dateFilter_query);

$years = [];
if ($dateFilter_result->num_rows > 0) {
  while ($row = $dateFilter_result->fetch_assoc()) {
    $years[] = $row['year'];
  }
}
$year = isset($_GET['year']) ? $_GET['year'] : 'all';
$year_condition = ($year === 'all') ? "" : "YEAR(`dateCommitted`) = $year";


// PENDING CASES
$pending_query = "SELECT COUNT(*) AS count FROM crimemapping WHERE"
  . (!empty($year_condition) ? " $year_condition AND" : "")
  . " `caseStatus` = 'Under Investigation'";
$pending_result = $conn->query($pending_query);
$pending_cases = ($pending_result && $row = $pending_result->fetch_assoc()) ? (int) $row['count'] : 0;

// CLEARED CASES
$cleared_query = "SELECT COUNT(*) AS count FROM crimemapping WHERE"
  . (!empty($year_condition) ? "  $year_condition AND" : "")
  . " `caseStatus` = 'Cleared'";
$cleared_result = $conn->query($cleared_query);
$cleared_cases = ($cleared_result && $row = $cleared_result->fetch_assoc()) ? (int) $row['count'] : 0;

// SOLVED CASES
$solved_query = "SELECT COUNT(*) AS count FROM crimemapping WHERE"
  . (!empty($year_condition) ? " $year_condition AND" : "")
  . " `caseStatus` = 'Solved'";
$solved_result = $conn->query($solved_query);
$solved_cases = ($solved_result && $row = $solved_result->fetch_assoc()) ? (int) $row['count'] : 0;

// TOTAL CASES
$total_query = "SELECT COUNT(*) AS count FROM crimemapping"
  . (!empty($year_condition) ? " WHERE $year_condition" : "");
$total_result = $conn->query($total_query);
$total_cases = ($total_result && $row = $total_result->fetch_assoc()) ? (int) $row['count'] : 0;

// CRIME VOLUME = (INDEX + NI)/TOTAL
$volume_query = "SELECT (SUM(CASE WHEN `crimeClassification` = 'index' THEN 1 ELSE 0 END) + 
                  SUM(CASE WHEN `crimeClassification` = 'non-index' THEN 1 ELSE 0 END)) / 
                  COUNT(*) * 100 AS count FROM crimemapping"
  . (!empty($year_condition) ? " WHERE $year_condition" : "");
$volume_result = $conn->query($volume_query);
$crime_volume = ($volume_result && $row = $volume_result->fetch_assoc()) ? number_format($row['count'], 2) : "0.00";

// CRIME RATE = (TOTAL/POPULATION)*100000
$population = 126347; // as of 2020  
$crime_rate = ($population > 0) ? ($total_cases / $population) * 100000 : 0;
$crime_rate = number_format($crime_rate, 2);

// CLEARANCE EFFICIENCY = (CLEARED/TOTAL)*100
$crime_clearance = ($total_cases > 0) ? ($cleared_cases / $total_cases) * 100 : 0;
$crime_clearance = number_format($crime_clearance, 2);

// SOLUTION EFFIENCY = (SOLVED/TOTAL)*100
$crime_solution = ($total_cases > 0) ? ($solved_cases / $total_cases) * 100 : 0;
$crime_solution = number_format($crime_solution, 2);

// DAILY TREND
$daily_query = "SELECT time, crime_count, rank FROM (
                SELECT `timeCommitted` AS time, 
                      COUNT(*) AS crime_count, 
                      RANK() OVER (ORDER BY COUNT(*) DESC) AS rank 
                FROM crimemapping"
  . (!empty($year_condition) ? " WHERE $year_condition" : "");
$daily_query .= " GROUP BY `timeCommitted`) ranked_time WHERE rank <= 10 ORDER BY crime_count DESC";
$daily_result = $conn->query($daily_query);
$peakTimes = [];
$dailyTrends = [];
foreach ($daily_result as $row) {
  $peakTimes[] = $row['time'];
  $dailyTrends[] = $row['crime_count'];
}
$peakTimes_json = json_encode($peakTimes);
$dailyTrends_json = json_encode($dailyTrends);

// WEEKLY TREND
$weekly_query = "SELECT day, crime_count, rank FROM (
                SELECT DAYNAME(`dateCommitted`) AS day, 
                COUNT(*) AS crime_count, 
                RANK() OVER (ORDER BY COUNT(*) DESC) 
                AS rank FROM crimemapping"
  . (!empty($year_condition) ? " WHERE $year_condition" : "")
  . " GROUP BY DAYNAME(`dateCommitted`) ) ranked_days WHERE rank <= 7 ORDER BY crime_count DESC";
$weekly_result = $conn->query($weekly_query);
$weekly_trend = $weekly_result->fetch_assoc();
$peakDays = [];
$weeklyTrends = [];
foreach ($weekly_result as $row) {
  $peakDays[] = $row['day'];
  $weeklyTrends[] = $row['crime_count'];
}
$peakDays_json = json_encode($peakDays);
$weeklyTrends_json = json_encode($weeklyTrends);

// MONTHLY TREND
$monthly_query = "SELECT month, crime_count, rank FROM (
                  SELECT MONTHNAME(`dateCommitted`) AS month, 
                  COUNT(*) AS crime_count, 
                  RANK() OVER (ORDER BY COUNT(*) DESC) 
                  AS rank FROM crimemapping"
  . (!empty($year_condition) ? " WHERE $year_condition" : "")
  . " GROUP BY MONTHNAME(`dateCommitted`) ) ranked_months 
                    WHERE rank <= 12 ORDER BY crime_count DESC";
$monthly_result = $conn->query($monthly_query);
$monthly_trend = $monthly_result->fetch_assoc();
$peakMonths = [];
$monthlyTrends = [];
foreach ($monthly_result as $row) {
  $peakMonths[] = $row['month'];
  $monthlyTrends[] = $row['crime_count'];
}
$peakMonths_json = json_encode($peakMonths);
$monthlyTrends_json = json_encode($monthlyTrends);

// PREVALENT INCIDENT TYPE IN SAN JUAN
$sanjuan_query = "SELECT incidentType, COUNT(*) AS crime_count FROM crimemapping"
  . (!empty($year_condition) ? " WHERE $year_condition" : "")
  . " GROUP BY incidentType ORDER BY crime_count DESC";
$sanjuan_result = $conn->query($sanjuan_query);
$sanjuanType = [];
$sanjuanCount = [];
while ($row = $sanjuan_result->fetch_assoc()) {
  $sanjuanType[] = $row['incidentType'];
  $sanjuanCount[] = $row['crime_count'];
}
$sanjuanType_json = json_encode($sanjuanType);
$sanjuanCount_json = json_encode($sanjuanCount);

// CRIME AGAINST CLASSIFICATION
$against_query = "SELECT CASE 
                  WHEN LOWER(TRIM(crimeAgainst)) LIKE '%person%' THEN 'Crime Against Person' 
                  WHEN LOWER(TRIM(crimeAgainst)) LIKE '%property%' THEN 'Crime Against Property' 
                  WHEN LOWER(TRIM(crimeAgainst)) LIKE '%interest%' THEN 'Crime Against Interest' 
                  WHEN LOWER(TRIM(crimeAgainst)) LIKE '%laws%' THEN 'Special Laws' 
                  ELSE 'Others' 
              END AS crimeAgainst, 
              COUNT(*) AS crime_count FROM crimemapping "
  . (!empty($year_condition) ? " WHERE $year_condition " : "")
  . "GROUP BY CASE
                  WHEN LOWER(TRIM(crimeAgainst)) LIKE '%person%' THEN 'Crime Against Person' 
                  WHEN LOWER(TRIM(crimeAgainst)) LIKE '%property%' THEN 'Crime Against Property' 
                  WHEN LOWER(TRIM(crimeAgainst)) LIKE '%interest%' THEN 'Crime Against Interest' 
                  WHEN LOWER(TRIM(crimeAgainst)) LIKE '%laws%' THEN 'Special Laws' 
                  ELSE 'Others' 
                END";
$against_result = $conn->query($against_query);
$crimeAgainst = [];
$againstCounts = [];
while ($row = $against_result->fetch_assoc()) {
  $crimeAgainst[] = $row['crimeAgainst'];
  $againstCounts[] = $row['crime_count'];
}
$crimeAgainst_json = json_encode($crimeAgainst);
$againstCounts_json = json_encode($againstCounts);

// HIGH RISK BRGY
$highRisk_query = "SELECT Barangay, COUNT(*) AS Count, 
                  DENSE_RANK() OVER (ORDER BY COUNT(*) DESC) AS Rank
                  FROM crimemapping WHERE 1
                  " . (!empty($year_condition) ? " AND $year_condition" : "") . "
                  GROUP BY Barangay
                  ORDER BY Count DESC";
$highRisk_result = $conn->query($highRisk_query);
$brgy_list = [];
while ($row = $highRisk_result->fetch_assoc()) {
  $brgy_list[] = [
    'rank' => $row['Rank'],
    'barangay' => $row['Barangay']
  ];
}
$brgy_json = json_encode($brgy_list);

// PREVALENT INCIDENT TYPE PER BARANGAY
$barangay_query = "SELECT DISTINCT BARANGAY FROM crimemapping";
$barangay_result = $conn->query($barangay_query);
$barangays = [];
while ($row = $barangay_result->fetch_assoc()) {
  $barangays[] = $row['BARANGAY'];
}
$brgyIncident_query = "SELECT BARANGAY, incidentType, crime_count FROM (
                  SELECT BARANGAY, incidentType, COUNT(*) AS crime_count, 
                        ROW_NUMBER() OVER (PARTITION BY BARANGAY ORDER BY COUNT(*) DESC) AS rank
                  FROM crimemapping
                  " . (!empty($year_condition) ? " WHERE $year_condition" : "") . "
                  GROUP BY BARANGAY, incidentType
                ) ranked
                ORDER BY BARANGAY, crime_count DESC";
$brgyIncident_result = $conn->query($brgyIncident_query);

$brgyIncident_data = [];
while ($row = $brgyIncident_result->fetch_assoc()) {
  $barangay = $row['BARANGAY'];
  if (!isset($brgyIncident_data[$barangay])) {
    $brgyIncident_data[$barangay] = [];
  }
  $brgyIncident_data[$barangay][] = [
    "offense" => $row['incidentType'],
    "crime_count" => $row['crime_count']
  ];
}


// ACTIVITY LOG
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // Ensure user is logged in
  if (!isset($_SESSION['account_id'])) {
    echo "<script>alert('Error: You must be logged in to perform this action.');</script>";
    exit();
  }

  $accountID = $_SESSION['account_id'];
  $currentDate = date("Y-m-d");
  $currentTime = date("H:i:s");

  // Upload csv
  if (isset($_POST["uploadFile"])) {
    if (isset($_SESSION['uploaded']) && $_SESSION['uploaded'] === true) {
      exit();
    }

    $csvTemp = $_FILES['csvfile']['tmp_name'];
    $getContent = file($csvTemp);

    if (!$getContent) {
      echo "<script>alert('Error: Unable to read CSV file.');</script>";
      exit();
    }

    // Insert into managedataset
    $datasetName = "sanjuan_" . date('Ymd_His');
    $uploadDate = date('Y-m-d');
    $uploadTime = date('H:i:s');
    $newDataset = mysqli_query($conn, "INSERT INTO managedataset (accountID, datasetName, uploadDate, uploadTime) VALUES ('$accountID', '$datasetName', '$uploadDate', '$uploadTime')");

    if (!$newDataset) {
      echo "<script>alert('Error inserting dataset: " . mysqli_error($conn) . "');</script>";
      exit();
    }

    $datasetID = mysqli_insert_id($conn);

    // Insert into crime mapping
    $stmt = $conn->prepare("INSERT INTO crimemapping (barangay, typeOfPlace, dateCommitted, timeCommitted, incidentType, crimeAgainst, crimeClassification, offense, offenseType, caseStatus, lat, lng) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    for ($i = 1; $i < count($getContent); $i++) {
      $expRow = str_getcsv($getContent[$i]);
      if (count($expRow) >= 12) {
        try {
          $stmt->bind_param(
            "ssssssssssdd",
            $expRow[0],  // barangay
            $expRow[1],  // typeOfPlace
            $expRow[2],  // dateCommitted
            $expRow[3],  // timeCommitted
            $expRow[4],  // incidentType
            $expRow[5],  // crimeAgainst
            $expRow[6],  // crimeClassification
            $expRow[7],  // offense
            $expRow[8],  // offenseType
            $expRow[9],  // caseStatus
            $expRow[10], // lat
            $expRow[11]  // lng
          );

          $stmt->execute();
        } catch (Exception $e) {
          error_log("Error inserting row $i: " . $e->getMessage());
          continue;
        }
      }
    }
    $stmt->close();

    // Insert into activity log
    $activityType = "User admin uploaded a dataset";
    $activityStmt = $conn->prepare("INSERT INTO activitylog (accountID, activityType, activityDate, activityTime) VALUES (?, ?, ?, ?)");
    $activityStmt->bind_param("isss", $accountID, $activityType, $currentDate, $currentTime);
    $activityStmt->execute();
    $activityStmt->close();

    $_SESSION['uploaded'] = true;
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
    exit();
  }

  // Generate Report
  if (isset($_POST['log_activity'])) {
    $activityType = "User admin generated a report";

    $stmt = $conn->prepare("INSERT INTO activitylog (accountID, activityType, activityDate, activityTime) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
      echo "Database error: " . $conn->error;
      exit();
    }

    $stmt->bind_param("isss", $accountID, $activityType, $currentDate, $currentTime);

    if ($stmt->execute()) {
      echo "Activity logged successfully.";
    } else {
      echo "Error logging activity: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    exit();
  }
}

// Upload success
if (isset($_GET['success']) && $_GET['success'] == 1) {
  echo "<script>alert('CSV file has been successfully uploaded and data inserted into the database.');</script>";
  unset($_SESSION['uploaded']); // Reset session flag
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>San Juan Analytics</title>
  <link rel="icon" type="image/png" href="images/logo-square.png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Bootstrap JavaScript Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins&family=Inter&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />

  <style>
    :root {
      --background: #0f172a;
      --card-bg: #1e293b;
      --primary-text: #e2e8f0;
      --secondary-text: #94a3b8;
      --accent: #38bdf8;
      --chart-1: #38bdf8;
      --chart-2: #f59e0b;
      --chart-3: #ef4444;
    }

    body {
      background-color: var(--background);
      color: var(--primary-text);
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 0;
    }

    .header {
      background-color: var(--background);
      padding: 15px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid rgba(148, 163, 184, 0.1);
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .logo img {
      width: 120px;
      height: auto;
      filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
    }

    .logo-text {
      display: flex;
      flex-direction: column;
    }

    .logo-text h1 {
      font-family: "Oswald", sans-serif;
      font-size: 28px;
      margin-bottom: 2px;
      color: var(--accent);
      letter-spacing: 0.5px;
    }

    .logo-text p {
      font-family: "Poppins", sans-serif;
      font-size: 12px;
      color: var(--text-secondary);
    }

    .nav {
      background-color: var(--card-bg);
      padding: 12px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .nav-links {
      display: flex;
      gap: 25px;
      list-style: none;
      margin: 0;
      padding: 0;
    }

    .nav-links a {
      color: var(--secondary-text);
      text-decoration: none;
      font-size: 14px;
      padding: 8px 16px;
      border-radius: 6px;
      transition: all 0.3s ease;
    }

    .nav-links a:hover {
      background-color: rgba(56, 189, 248, 0.1);
      color: var(--accent);
    }

    .nav-links a.active {
      color: var(--accent);
      background-color: rgba(56, 189, 248, 0.1);
    }

    .date-display {
      text-align: right;
      padding: 8px 16px;
      font-size: 14px;
      color: var(--text-secondary);
      font-weight: 500;
    }

    .year-toggle-container {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin: 1rem 0;
    }

    .pill-toggle {
      background: var(--card-bg);
      padding: 0.25rem;
      border-radius: 100px;
      display: inline-block;
    }

    .pill-toggle-group {
      display: flex;
      position: relative;
      gap: 0.25rem;
    }

    .pill-toggle-group input[type="radio"] {
      display: none;
    }

    .pill-toggle-option {
      padding: 0.5rem 1.25rem;
      font-size: 0.875rem;
      color: var(--secondary-text);
      cursor: pointer;
      border-radius: 100px;
      transition: all 0.3s ease;
      position: relative;
      z-index: 1;
      user-select: none;
    }

    .pill-toggle-option.year-value {
      color: var(--accent);
    }

    .pill-toggle-option.active {
      color: var(--background) !important;
      background: var(--primary-text);
    }

    .pill-toggle-option:hover {
      background-color: rgba(56, 189, 248, 0.1);
      color: var(--accent);
      cursor: pointer;
    }

    .card {
      background-color: var(--card-bg);
      border-radius: 0.5rem;
      margin-bottom: 1rem;
      height: 100%;
      display: flex;
      flex-direction: column;
      transition: all 0.3s ease;
    }

    .card-title {
      color: var(--secondary-text);
      font-weight: bold;
      margin-bottom: 1.5rem;
      font-size: 1rem;
    }

    .card-body {
      padding: 1.25rem;
      flex: 1;
      display: flex;
      flex-direction: column;
      border: 1px solid transparent;
      transition: border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card-text {
      color: var(--accent);
      font-size: 1.5rem;
      font-weight: 600;
      margin: 0;
    }

    .card:hover {
      transform: translateY(-4px);
      border: 2px solid #38bdf8;
      box-shadow: 0 8px 12px -1px rgba(0, 0, 0, 0.2);
    }

    select {
      background-color: #ffffff;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 0.375rem;
      color: #000000;
      font-size: 0.875rem;
      padding: 0.5rem;
    }

    select option {
      background-color: #ffffff;
      color: #000000;
    }

    .chart-container {
      position: relative;
      height: 300px !important;
      width: 100%;
      margin: 0 auto;
      padding: 1rem;
      aspect-ratio: 16/9;
      overflow-y: auto;
      scrollbar-width: thin;
      scrollbar-color: #1e293b #94a3b8;
    }

    canvas {
      max-width: 100%;
    }

    #sanjuanContainer {
      height: 1000px;
    }

    #brgyIncidentContainer {
      height: 400px;
    }

    #barangayDropdown {
      background-color: rgba(56, 189, 248, 0.1);
      color: var(--primary-text);
      padding: 5px;
      padding-left: 1rem;
      border: 1px solid #38bdf8;
      border-radius: 5px;
      font-size: 0.85rem;
      transition: background-color 0.3s ease;
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
    }

    #brgyList {
      color: var(--primary-text);
      font-size: 0.875rem;
      list-style: none;
      padding: 1rem;
      margin: 0;
      height: 300px;
      overflow-y: auto;
      scrollbar-width: thin;
      scrollbar-color: #1e293b #94a3b8;
    }

    #brgyList li {
      padding: 12px;
      margin: 8px 0;
      background: rgba(148, 163, 184, 0.05);
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    #brgyList li:hover {
      background: rgba(148, 163, 184, 0.1);
      transform: translateX(4px);
    }

    #crimeAgainst {
      height: 300px !important;
      width: 100% !important;
      margin: 0 auto;
    }

    .row {
      margin-bottom: 1.5rem;
      display: flex;
      align-items: stretch;
    }

    .col-lg-5,
    .col-lg-7 {
      display: flex;
      flex-direction: column;
    }

    .container {
      padding-top: 2rem;
      padding-bottom: 2rem;
    }

    .logout-btn {
      padding: 8px 16px;
      background-color: transparent;
      color: var(--text-primary);
      border: transparent;
      cursor: pointer;
    }

    .logout-btn:hover {
      background-color: var(--border-color);
    }

    @media print {

      .navbar,
      select,
      .btn-primary {
        display: none !important;
      }

      body {
        background-color: white !important;
      }

      .card {
        break-inside: avoid;
      }
    }

    /* Responsive styles */
    @media (max-width: 768px) {
      .pill-toggle {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }

      .pill-toggle-group {
        width: max-content;
      }

      .pill-toggle-option {
        padding: 0.375rem 1rem;
        font-size: 0.813rem;
      }
    }

    .footer {
      background-color: var(--background-primary);
      padding: 1rem 48px;
      width: 100%;
      margin-top: 2rem;
    }

    .footer-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 0;
    }

    .footer-logo {
      height: 180px;
      width: auto;
    }

    .footer-links {
      display: flex;
      gap: 2rem;
      align-items: center;
    }

    .footer-links a {
      color: #e2e8f0;
      text-decoration: none;
      font-weight: bold;
      font-family: 'Poppins', sans-serif;
      font-size: 14px;
    }

    .footer-separator {
      height: 2px;
      background-color: rgb(255, 255, 255);
      margin: 0;
    }

    .footer-bottom {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-family: 'Poppins', sans-serif;
      font-size: 14px;
      color: #94a3b8;
      padding: 16px 0 10px 0;
    }
  </style>
</head>

<body>
  <header class="header">
    <div class="logo">
      <img src="images/LOGO-1.png" alt="Bantay Alisto Logo" />
      <div class="logo-text">
        <h1>BANTAY ALISTO</h1>
        <p>Crime Mapping and Forecasting Prototype</p>
      </div>
    </div>
    <button class="logout-btn" onclick="logout()">Logout</button>
  </header>

  <nav class="nav">
    <ul class="nav-links">
      <li><a href="dashboard.php">Overview</a></li>
      <li><a href="map.php">Map View</a></li>
      <li><a class="active" href="analytics.php">Analytics</a></li>
      <li><a href="forecast.html">Forecasts</a></li>
      <li><a href="activity-log.php">Activity Logs</a></li>
    </ul>
    <div class="date-display" id="datetime"></div>
  </nav>


  <div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div class="year-toggle-container">
        <div class="pill-toggle">
          <form method="GET" id="yearForm" class="pill-toggle-group">
            <input type="radio" id="all" name="year" value="all" <?= ($year == 'all') ? 'checked' : '' ?>>
            <label for="all" class="pill-toggle-option" style="color: var(--accent)" onclick="submitForm('all')">All
              Time</label>

            <?php foreach ($years as $yr): ?>
              <input type="radio" id="year_<?= $yr ?>" name="year" value="<?= $yr ?>" <?= ($year == $yr) ? 'checked' : '' ?>>
              <label for="year_<?= $yr ?>" class="pill-toggle-option year-value"
                onclick="submitForm('<?= $yr ?>')"><?= $yr ?></label>
            <?php endforeach; ?>
          </form>
        </div>
      </div>
      <div class="d-flex gap-4 ms-auto align-items-center">
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data"
          id="uploadForm">
          <input type="file" name="csvfile" accept=".csv" id="csvfile" style="display: none;"
            onchange="document.getElementById('uploadForm').submit();">
          <a href="#" class="text-decoration-none d-flex align-items-center gap-2" style="color: var(--secondary-text);"
            onclick="document.getElementById('csvfile').click();">
            <i class="bi bi-upload"></i>
            <span style="text-decoration: underline;">Upload CSV</span>
          </a>

          </button>
          <input type="hidden" name="uploadFile" value="1">
        </form>
        <a href="#" class="text-decoration-none d-flex align-items-center gap-2" style="color: var(--secondary-text);"
          onclick="printReport()">
          <i class="bi bi-file-earmark-text"></i>
          <span style="text-decoration: underline;">Generate Report</span>
        </a>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-3 col-md-6">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Pending Cases</h6>
            <p class="card-text"><?= $pending_cases ?: 'No Data' ?></p>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Cleared Cases</h6>
            <p class="card-text"><?= $cleared_cases ?: 'No Data' ?></p>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Solved Cases</h6>
            <p class="card-text"><?= $solved_cases ?: 'No Data' ?></p>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Total Cases</h6>
            <p class="card-text"><?= $total_cases ?: 'No Data' ?></p>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-lg-3 col-md-6">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Crime Volume</h6>
            <p class="card-text"><?= $crime_volume ?>%</p>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Crime Rate <span style="font-size: 0.75rem">(per 100,000)</span></h6>
            <p class="card-text"><?= $crime_rate ?>%</p>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Crime Clearance Efficiency</h6>
            <p class="card-text"><?= $crime_clearance ?>%</p>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Crime Solution Efficiency</h6>
            <p class="card-text"><?= $crime_solution ?>%</p>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-lg-4">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Peak Hours of the Day</h6>
            <div class="chart-container">
              <canvas id="dailyTrend"></canvas>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Peak Days of the Week</h6>
            <div class="chart-container">
              <canvas id="weeklyTrend"></canvas>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Peak Months of the Year</h6>
            <div class="chart-container">
              <canvas id="monthlyTrend"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-lg-7">
        <div class="card" style="height: 400px;">
          <div class="card-body">
            <h6 class="card-title">Prevalent Offenses in San Juan</h6>
            <div class="chart-container">
              <div id="sanjuanContainer">
                <canvas id="sanjuanChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Crime Classification</h6>
            <canvas id="crimeAgainst"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-lg-5">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">High Risk Barangays</h6>
            <ul id="brgyList"></ul>
          </div>
        </div>
      </div>
      <div class="col-lg-7">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Number of Offenses per Barangay</h6>
            <select id="barangayDropdown" class="mb-3">
              <option value="">-- Select Barangay --</option>
              <?php
              sort($barangays);
              foreach ($barangays as $barangay):
                ?>
                <option value="<?= htmlspecialchars($barangay) ?>">
                  <?= htmlspecialchars($barangay) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="chart-container">
              <div id="brgyIncidentContainer">
                <canvas id="brgyIncidentChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="session-timeout.js"></script>
  <script>
    // DateTime
    function updateDateTime() {
      const dt = new Date();
      const options = {
        timeZone: "Asia/Manila",
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        hour12: true
      };
      document.getElementById("datetime").innerHTML = dt.toLocaleString("en-US", options) + " PHT";
    }
    updateDateTime();
    setInterval(updateDateTime, 60000);

    function submitForm(selectedYear) {
      // Remove active class from all options
      document.querySelectorAll('.pill-toggle-option').forEach(option => {
        option.classList.remove('active');
      });

      // Add active class to selected option
      const selectedOption = selectedYear === 'all'
        ? document.querySelector('label[for="all"]')
        : document.querySelector(`label[for="year_${selectedYear}"]`);

      if (selectedOption) {
        selectedOption.classList.add('active');
      }

      // Set the radio input value
      const radioInput = selectedYear === 'all'
        ? document.getElementById('all')
        : document.getElementById(`year_${selectedYear}`);

      if (radioInput) {
        radioInput.checked = true;
      }

      // Submit the form
      document.getElementById('yearForm').submit();
    }

    // Set initial active state based on PHP variable
    document.addEventListener('DOMContentLoaded', function () {
      const currentYear = '<?= $year ?>';
      const activeOption = currentYear === 'all'
        ? document.querySelector('label[for="all"]')
        : document.querySelector(`label[for="year_${currentYear}"]`);

      if (activeOption) {
        activeOption.classList.add('active');
      }
    });

    // Chart Configuration
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
    Chart.defaults.font.family = 'Inter, sans-serif';

    const backgroundColors = <?= $crimeAgainst_json ?>.map((_, index) => {
      const hue = 210; // Fixed hue for blue
      const saturation = 50 + (index * 25) % 100; // Saturation varies from 50% to 100% in increments
      const lightness = 30 + (index * 20) % 70; // Lightness varies from 30% to 100% (shades from darker to lighter)

      return `hsl(${hue}, ${saturation}%, ${lightness}%)`;
    });

    const chartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(255, 255, 255, 0.1)'
          }
        },
        x: {
          grid: {
            color: 'rgba(255, 255, 255, 0.1)'
          }
        }
      }
    };

    // Daily Trend
    new Chart(document.getElementById('dailyTrend').getContext('2d'), {
      type: 'line',
      data: {
        labels: <?= $peakTimes_json ?>,
        datasets: [{
          label: 'Crimes',
          data: <?= $dailyTrends_json ?>,
          borderColor: '#38bdf8',
          backgroundColor: 'rgba(56, 189, 248, 0.1)',
          tension: 0.4,
          fill: true
        }]
      },
      options: chartOptions
    });

    // Weekly Trend
    new Chart(document.getElementById('weeklyTrend').getContext('2d'), {
      type: 'bar',
      data: {
        labels: <?= $peakDays_json ?>,
        datasets: [{
          label: 'Crimes',
          data: <?= $weeklyTrends_json ?>,
          backgroundColor: '#38bdf8',
          borderRadius: 4
        }]
      },
      options: chartOptions
    });

    // Monthly Trend
    new Chart(document.getElementById('monthlyTrend').getContext('2d'), {
      type: 'line',
      data: {
        labels: <?= $peakMonths_json ?>,
        datasets: [{
          label: 'Crimes',
          data: <?= $monthlyTrends_json ?>,
          borderColor: '#38bdf8',
          backgroundColor: 'rgba(56, 189, 248, 0.1)',
          tension: 0.4,
          fill: true
        }]
      },
      options: chartOptions
    });

    // Prevalent Incidents in San Juan
    new Chart(document.getElementById('sanjuanChart').getContext('2d'), {
      type: 'bar',
      data: {
        labels: <?= $sanjuanType_json ?>,
        datasets: [{
          label: 'Number of Crimes',
          data: <?= $sanjuanCount_json ?>,
          backgroundColor: '#38bdf8',
          borderRadius: 4,
        }]
      },
      options: {
        ...chartOptions,
        indexAxis: 'y',
        plugins: {
          legend: { display: false }
        },
        scales: {
          x: {
            beginAtZero: true,
            grid: {
              color: 'rgba(255, 255, 255, 0.1)'
            }
          },
          y: {
            grid: {
              display: false
            }
          }
        },
        layout: {
          padding: {
            left: 10,
            right: 10
          }
        },
      }
    });

    // Crime Against Pie Chart   
    new Chart(document.getElementById('crimeAgainst').getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: <?= $crimeAgainst_json ?>,
        datasets: [{
          data: <?= $againstCounts_json ?>,
          backgroundColor: backgroundColors,
          // backgroundColor: ['#38bdf8', '#f59e0b', '#ef4444']
          borderWidth: 1.25
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 15
            }
          }
        },
        layout: {
          padding: {
            top: 0,
            bottom: 20
          }
        }
      }
    });

    // High Risk Barangays
    const brgyData = <?= $brgy_json ?>;
    const brgyList = document.getElementById('brgyList');
    brgyData.forEach(item => {
      const li = document.createElement('li');
      li.textContent = `${item.rank}. ${item.barangay}`;
      brgyList.appendChild(li);
    });

    // Offenses per Barangay
    const brgyIncident = <?= json_encode($brgyIncident_data) ?>;
    let brgyIncidentChart;

    document.getElementById('barangayDropdown').addEventListener('change', function () {
      const barangay = this.value;
      if (!barangay) return;

      const data = brgyIncident[barangay];
      if (!data) {
        alert('No data available for this barangay');
        return;
      }

      if (brgyIncidentChart) {
        brgyIncidentChart.destroy();
      }

      brgyIncidentChart = new Chart(document.getElementById('brgyIncidentChart').getContext('2d'), {
        type: 'bar',
        data: {
          labels: data.map(d => d.offense),
          datasets: [{
            label: 'Number of Crimes',
            data: data.map(d => d.crime_count),
            backgroundColor: '#38bdf8',
            borderRadius: 4
          }]
        },
        options: {
          ...chartOptions,
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            x: {
              beginAtZero: true,
              grid: {
                color: 'rgba(255, 255, 255, 0.1)'
              }
            },
            y: {
              grid: {
                display: false
              },
              ticks: {
                callback: function (value) {
                  const label = this.getLabelForValue(value);
                  if (label.length > 40) {
                    return label.substr(0, 37) + '...';
                  }
                  return label;
                },
                maxRotation: 0,
                minRotation: 0,
                font: {
                  size: 11
                }
              }
            }
          }
        }
      });
    });

    // Function to generate report
    function printReport() {
      fetch(window.location.href, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          log_activity: 1
        })
      })
        .then(response => response.text())
        .then(data => {
          console.log(data);
          if (data.includes("Activity logged successfully")) {
            window.print();
          } else {
            alert("Failed to log activity: " + data);
          }
        })
        .catch(error => console.error('Error:', error));
    }


    window.logout = function () {
      // Clear all local storage
      localStorage.clear();
      sessionStorage.clear();

      // Send a request to destroy the server-side session
      fetch('log-out.php', {
        method: 'POST',
        credentials: 'same-origin'
      })
        .then(() => {
          // Redirect to login page after session destruction
          window.location.href = "log-in.php";
        })
        .catch(error => {
          console.error('Logout error:', error);
          // Fallback redirect
          window.location.href = "log-in.php";
        });
    };
    document.querySelectorAll('.pill-toggle-option').forEach(option => {
      option.addEventListener('click', function () {
        this.closest('form').submit();
      });
    });

  </script>
  <footer class="footer">
    <div class="footer-top">
      <img src="images/LOGO-3.png" alt="Bantay Alisto Logo" class="footer-logo">
      <div class="footer-links">
        <a href="#">Terms of Service</a>
        <a href="#">Privacy Policy</a>
      </div>
    </div>
    <div class="footer-separator"></div>
    <div class="footer-bottom">
      <span>© 2025 — All Rights Reserved.</span>
      <span>Bantay Alisto Crime Mapping</span>
    </div>
  </footer>
</body>

</html>