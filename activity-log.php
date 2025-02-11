<?php
session_start();
include('config.php');

$limit = 25; // Number of rows per page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Sorting logic
$columns = ['logID', 'accountID', 'activityType', 'activityDate', 'activityTime'];
$sort_column = isset($_GET['sort']) && in_array($_GET['sort'], $columns) ? $_GET['sort'] : 'logID';
$sort_order = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'DESC' : 'ASC';

// Date and Time Filters
$where_clauses = [];
$params = [];

// Start Date Filter
if (!empty($_GET['start_date'])) {
  $where_clauses[] = "activityDate >= ?";
  $params[] = $_GET['start_date'];
}

// End Date Filter
if (!empty($_GET['end_date'])) {
  $where_clauses[] = "activityDate <= ?";
  $params[] = $_GET['end_date'];
}

// Start Time Filter
if (!empty($_GET['start_time'])) {
  $where_clauses[] = "activityTime >= ?";
  $params[] = $_GET['start_time'];
}

// End Time Filter
if (!empty($_GET['end_time'])) {
  $where_clauses[] = "activityTime <= ?";
  $params[] = $_GET['end_time'];
}

// Combine filters into SQL WHERE clause
$where_sql = "";
if (!empty($where_clauses)) {
  $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Get total count with filters
$totalQuery = "SELECT COUNT(*) AS total FROM activitylog $where_sql";
$stmt = $conn->prepare($totalQuery);
if (!empty($params)) {
  $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$totalResult = $stmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$total = $totalRow['total'];
$pages = ceil($total / $limit);

// Fetch paginated data with filters
$query = "SELECT * FROM activitylog $where_sql ORDER BY $sort_column $sort_order LIMIT ?, ?";
$params[] = $start;
$params[] = $limit;

$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat('s', count($params) - 2) . 'ii', ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BantayAlisto Activity Log</title>
  <link rel="icon" type="image/png" href="images/logo-square.png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins&family=Inter&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

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

    label {
      color: var(--secondary-text);
    }

    input.form-control {
      background-color: var(--card-bg);
      color: var(--primary-text);
      border: transparent;
    }

    .input-group .input-icon {
      color: var(--primary-text);
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

    .page-link {
      color: var(--primary-text);
      background-color: transparent;
      border: 1px solid var(--card-bg);
      margin: 0.5rem;
      font-size: 0.875rem;
    }

    .page-item.active .page-link {
      color: var(--accent);
      background-color: rgba(56, 189, 248, 0.1);
      font-weight: bold;
    }

    .page-link:hover {
      background-color: rgba(56, 189, 248, 0.1);
      color: var(--accent);
      border-color: transparent;
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

      <!-- Filters -->
      <form method="GET" class="mb-3">
        <div class="row">
          <div class="col-md-3">
            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" class="form-control">
          </div>
          <div class="col-md-3">
            <label for="end_date">End Date</label>
            <input type="date" id="end_date" name="end_date" class="form-control">
          </div>
          <div class="col-md-3">
            <label for="start_time">Start Time</label>
            <input type="time" id="start_time" name="start_time" class="form-control"
              value="<?= isset($_GET['start_time']) ? $_GET['start_time'] : '' ?>">
          </div>
          <div class="col-md-3">
            <label for="end_time">End Time</label>
            <input type="time" id="end_time" name="end_time" class="form-control"
              value="<?= isset($_GET['end_time']) ? $_GET['end_time'] : '' ?>">
          </div>

          <!-- Submit Filter -->
          <div class="col-md-3 mt-2">
            <button type="submit" class="btn btn-primary mt-4">Filter</button>
            <a href="activity-log.php" class="btn btn-secondary mt-4">Reset</a>
          </div>
        </div>
      </form>

      <!-- Table -->
      <table class="table">
        <thead>
          <tr>
            <th><a href="?page=<?= $page ?>&sort=logID&order=<?= $toggle_order ?>">
                <i class="fa-solid fa-sort"></i> Log ID</a></th>
            <th><a href="?page=<?= $page ?>&sort=accountID&order=<?= $toggle_order ?>">
                <i class="fa-solid fa-sort"></i> Account ID</a></th>
            <th><a href="?page=<?= $page ?>&sort=activityType&order=<?= $toggle_order ?>">
                <i class="fa-solid fa-sort"></i> Activity Type</a></th>
            <th><a href="?page=<?= $page ?>&sort=activityDate&order=<?= $toggle_order ?>">
                <i class="fa-solid fa-sort"></i> Activity Date</a></th>
            <th><a href="?page=<?= $page ?>&sort=activityTime&order=<?= $toggle_order ?>">
                <i class="fa-solid fa-sort"></i> Activity Time</a></th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= $row['logID'] ?></td>
                <td><?= $row['accountID'] ?></td>
                <td><?= $row['activityType'] ?></td>
                <td><?= $row['activityDate'] ?></td>
                <td><?= $row['activityTime'] ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5">No activity logs found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="d-flex justify-content-center">
        <nav aria-label="Page navigation">
          <ul class="pagination">
            <?php if ($page > 1): ?>
              <li class="page-item"><a class="page-link"
                  href="?page=<?= ($page - 1) ?>&sort=<?= $sort_column ?>&order=<?= $sort_order ?>">Previous</a></li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $pages; $i++): ?>
              <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link"
                  href="?page=<?= $i ?>&sort=<?= $sort_column ?>&order=<?= $sort_order ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <?php if ($page < $pages): ?>
              <li class="page-item"><a class="page-link"
                  href="?page=<?= ($page + 1) ?>&sort=<?= $sort_column ?>&order=<?= $sort_order ?>">Next</a></li>
            <?php endif; ?>
          </ul>
        </nav>
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

    // Prevent date filters to select future dates
    document.addEventListener("DOMContentLoaded", function () {
      let today = new Date().toISOString().split("T")[0];
      document.getElementById("start_date").setAttribute("max", today);
      document.getElementById("end_date").setAttribute("max", today);
    });

  </script>
</body>

</html>