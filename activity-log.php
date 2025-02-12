<?php
session_start();
include('config.php');

// Enable MySQLi error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
  // Set default column and order
  $column = isset($_GET['column']) ? $_GET['column'] : 'logID';
  $order = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'DESC' : 'ASC';
  $filter = isset($_GET['activityType']) ? $_GET['activityType'] : '';
  $time_filter = isset($_GET['timeFilter']) ? $_GET['timeFilter'] : '';
  $custom_start = isset($_GET['customStart']) ? $_GET['customStart'] : '';
  $custom_end = isset($_GET['customEnd']) ? $_GET['customEnd'] : '';

  // Pagination settings
  $limit = 25;
  $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
  $offset = ($page - 1) * $limit;

  // Whitelist allowed column names to prevent SQL injection
  $allowed_columns = ['logID', 'accountID', 'activityType', 'activityDate', 'activityTime'];
  if (!in_array($column, $allowed_columns)) {
    $column = 'logID'; // Default column
  }

  // Build filtering condition
  $filter_query = "WHERE 1=1";
  if (!empty($filter)) {
    $filter_query .= " AND activityType = '" . $conn->real_escape_string($filter) . "'";
  }

  // Time period filter
  if (!empty($time_filter)) {
    $current_date = date('Y-m-d');
    switch ($time_filter) {
      case 'last15':
        $filter_query .= " AND activityDate >= DATE_SUB('$current_date', INTERVAL 15 DAY)";
        break;
      case 'last30':
        $filter_query .= " AND activityDate >= DATE_SUB('$current_date', INTERVAL 30 DAY)";
        break;
      case 'last3months':
        $filter_query .= " AND activityDate >= DATE_SUB('$current_date', INTERVAL 3 MONTH)";
        break;
      case 'last6months':
        $filter_query .= " AND activityDate >= DATE_SUB('$current_date', INTERVAL 6 MONTH)";
        break;
      case 'custom':
        if (!empty($custom_start) && !empty($custom_end)) {
          $filter_query .= " AND activityDate BETWEEN '$custom_start' AND '$custom_end'";
        }
        break;
    }
  }

  // Get total records count
  $total_result = $conn->query("SELECT COUNT(*) AS total FROM activitylog $filter_query");
  $total_rows = $total_result->fetch_assoc()['total'];
  $total_pages = ceil($total_rows / $limit);

  // Prepare the SQL query with pagination
  $sql = "SELECT logID, accountID, activityType, activityDate, activityTime FROM activitylog $filter_query ORDER BY $column $order LIMIT $limit OFFSET $offset";
  $result = $conn->query($sql);

  // Get distinct activity types for filtering dropdown
  $activity_types_result = $conn->query("SELECT DISTINCT activityType FROM activitylog");
  $activity_types = [];
  while ($row = $activity_types_result->fetch_assoc()) {
    $activity_types[] = $row['activityType'];
  }

  // Check for query execution errors
  if (!$result) {
    die("Error fetching data: " . $conn->error);
  }

} catch (Exception $e) {
  // Display an alert with the SQL error
  echo "<script>alert('SQL Error: " . addslashes($e->getMessage()) . "');</script>";
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BantayAlisto Activity Log</title>
  <link rel="icon" type="image/png" href="images/logo-square.png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins&family=Inter&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

  <style>
    :root {
      --background: #0f172a;
      --card-bg: #1e293b;
      --primary-text: #e2e8f0;
      --secondary-text: #94a3b8;
      --accent: #38bdf8;
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

    h3 {
      color: var(--accent);
      font-weight: bold;
    }

    .table {
      background-color: transparent;
    }

    .table thead,
    .table tbody,
    .table tr,
    .table th,
    .table td {
      background-color: transparent;
    }

    th a {
      color: var(--secondary-text);
      text-decoration: none;

    }

    .table thead,
    .table th {
      text-align: center;
    }

    .table td {
      font-size: 0.875rem;
      color: var(--primary-text);
      text-align: center;
    }

    .pagination {
      text-align: center;
    }

    .pagination a {
      color: var(--primary-text);
      background-color: transparent;
      border: 2px solid var(--card-bg);
      margin: 0.25rem;
      padding: 0.5rem;
      font-size: 0.875rem;
      text-decoration: none;
      transition: 0.3s;
    }

    .pagination a:hover {
      background-color: rgba(56, 189, 248, 0.1);
      color: var(--accent);
      border-color: transparent;
    }

    .pagination a.active {
      color: var(--accent);
      background-color: rgba(56, 189, 248, 0.1);
      font-weight: bold;
    }

    .dropdown-container {
      position: relative;
      display: inline-block;
      padding-right: 2rem;
    }

    .filterLabel {
      color: var(--text-secondary);
      font-size: 0.85rem;
      font-weight: 500;
      margin-bottom: 0.5rem;
    }

    .filterInput {
      background-color: var(--card-bg);
      color: var(--secondary-text);
      border: 1px solid rgba(148, 163, 184, 0.2);
      border-radius: 6px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      padding: 0.25rem;
    }

    select,
    input[type="date"] {
      background-color: var(--background-secondary);
      padding: 5px;
      border: 1px solid #ccc;
      border-radius: 5px;
      transition: background-color 0.3s, color 0.3s;
    }

    /* Dropdown Content */
    select:hover,
    input[type="date"]:hover {
      background-color: var(--card-bg);
      border: 1px solid rgba(148, 163, 184, 0.2);
      border-radius: 6px;
    }

    select:focus,
    input[type="date"]:focus {
      border-color: var(--accent-primary);
      box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
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

    footer {
      background-color: var(--card-bg);
      color: var(--secondary-text);
      font-size: 0.75rem;
      padding: 1rem 0;
      text-align: center;
      position: relative;
      bottom: 0;
      width: 100%;
      margin-top: 2rem;
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
      <li><a href="mapview.php">Map View</a></li>
      <li><a href="analytics.php">Analytics</a></li>
      <li><a href="forecast.php">Forecasts</a></li>
      <li><a class="active" href="activity-log.php">Activity Logs</a></li>
    </ul>
    <div class="date-display" id="datetime"></div>
  </nav>

  <div class="container mt-4">
    <h3>Activity Log</h3>
    <div class="container mt-4">
      <div class="d-flex justify-content-center">
        <form method="GET" style="text-align: center; margin-bottom: 20px;">
          <div class="dropdown-container">
            <label class="filterLabel" for="timeFilter">Time Period: </label>
            <select class="filterInput" name="timeFilter" id="timeFilter"
              onchange="toggleCustomRange(); this.form.submit();">
              <option value="">All Time</option>
              <option value="last15" <?= $time_filter == 'last15' ? 'selected' : '' ?>>Last 15 Days</option>
              <option value="last30" <?= $time_filter == 'last30' ? 'selected' : '' ?>>Last 30 Days</option>
              <option value="last3months" <?= $time_filter == 'last3months' ? 'selected' : '' ?>>Last 3 Months</option>
              <option value="last6months" <?= $time_filter == 'last6months' ? 'selected' : '' ?>>Last 6 Months</option>
              <option value="custom" <?= $time_filter == 'custom' ? 'selected' : '' ?>>Custom Range</option>
            </select>
            <div id="customDateRange" class="dropdown-content"
              style="display: <?= $time_filter == 'custom' ? 'block' : 'none' ?>;">
              <label for="customStart">From: </label>
              <input type="date" name="customStart" value="<?= htmlspecialchars($custom_start) ?>">
              <label for="customEnd">To: </label>
              <input type="date" name="customEnd" value="<?= htmlspecialchars($custom_end) ?>">
              <button type="submit">Apply</button>
            </div>
          </div>
          <input type="hidden" name="column" value="<?= $column ?>">
          <input type="hidden" name="order" value="<?= $order ?>">

          <label class="filterLabel" for="activityType">Activity Type: </label>
          <select class="filterInput" name="activityType" id="activityType" onchange="this.form.submit()">
            <option value="">All</option>
            <?php foreach ($activity_types as $type): ?>
              <option value="<?= htmlspecialchars($type) ?>" <?= $filter == $type ? 'selected' : '' ?>>
                <?= htmlspecialchars($type) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <!-- Table -->
      <table class="table">
        <thead>
          <tr>
            <th><a
                href="?column=logID&order=<?= $order == 'ASC' ? 'desc' : 'asc' ?>&page=<?= $page ?>&activityType=<?= $filter ?>">Log
                ID</a></th>
            <th><a
                href="?column=accountID&order=<?= $order == 'ASC' ? 'desc' : 'asc' ?>&page=<?= $page ?>&activityType=<?= $filter ?>">Account
                ID</a></th>
            <th><a
                href="?column=activityType&order=<?= $order == 'ASC' ? 'desc' : 'asc' ?>&page=<?= $page ?>&activityType=<?= $filter ?>">Activity
                Type</a></th>
            <th><a
                href="?column=activityDate&order=<?= $order == 'ASC' ? 'desc' : 'asc' ?>&page=<?= $page ?>&activityType=<?= $filter ?>">Activity
                Date</a></th>
            <th><a
                href="?column=activityTime&order=<?= $order == 'ASC' ? 'desc' : 'asc' ?>&page=<?= $page ?>&activityType=<?= $filter ?>">Activity
                Time</a></th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['logID']) ?></td>
                <td><?= htmlspecialchars($row['accountID']) ?></td>
                <td><?= htmlspecialchars($row['activityType']) ?></td>
                <td><?= htmlspecialchars($row['activityDate']) ?></td>
                <td><?= htmlspecialchars($row['activityTime']) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5">No records found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center">
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?column=<?= $column ?>&order=<?= $order ?>&page=<?= $page - 1 ?>">Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?column=<?= $column ?>&order=<?= $order ?>&page=<?= $i ?>"
            class="pagination-link <?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
          <a href="?column=<?= $column ?>&order=<?= $order ?>&page=<?= $page + 1 ?>">Next</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <footer>
    <p>©Bantay Alisto Crime Mapping 2025. All Rights Reserved.</p>
  </footer>

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

    function toggleCustomRange() {
      const timeFilter = document.getElementById("timeFilter").value;
      document.getElementById("customDateRange").style.display = timeFilter === "custom" ? "block" : "none";
    }
  </script>
</body>

</html>