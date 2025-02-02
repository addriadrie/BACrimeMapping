<?php

include('connect.php');

// Get the selected year
$year = isset($_GET['year']) ? $_GET['year'] : 'all';

// Condition for SQL queries (if a specific year is selected, add WHERE clause)
$year_condition = ($year === 'all') ? "" : "YEAR(`DATE COMMITTED`) = $year";

// Get  crime cases
$pending_query = "SELECT COUNT(*) AS count FROM sanjuan";
if (!empty($year_condition)) {
    $pending_query .= " WHERE $year_condition AND `CASE STATUS` = 'Under Investigation'";
} else {
    $pending_query .= " WHERE `CASE STATUS` = 'Under Investigation'";
}
$pending_result = $conn->query($pending_query);
$pending_cases = ($pending_result && $row = $pending_result->fetch_assoc()) ? (int) $row['count'] : 0;

// Get cleared crime cases
$cleared_query = "SELECT COUNT(*) AS count FROM sanjuan";
if (!empty($year_condition)) {
    $cleared_query .= " WHERE $year_condition AND `CASE STATUS` = 'Cleared'";
} else {
    $cleared_query .= " WHERE `CASE STATUS` = 'Cleared'";
}
$cleared_result = $conn->query($cleared_query);
$cleared_cases = ($cleared_result && $row = $cleared_result->fetch_assoc()) ? (int) $row['count'] : 0;

// Get solved crime cases
$solved_query = "SELECT COUNT(*) AS count FROM sanjuan";
if (!empty($year_condition)) {
    $solved_query .= " WHERE $year_condition AND `CASE STATUS` = 'Solved'";
} else {
    $solved_query .= " WHERE `CASE STATUS` = 'Solved'";
}
$solved_result = $conn->query($solved_query);
$solved_cases = ($solved_result && $row = $solved_result->fetch_assoc()) ? (int) $row['count'] : 0;

// Get total crime cases
$total_query = "SELECT COUNT(*) AS count FROM sanjuan";
if (!empty($year_condition)) {
    $total_query .= " WHERE $year_condition";
} 
$total_result = $conn->query($total_query);
$total_cases = ($total_result && $row = $total_result->fetch_assoc()) ? (int) $row['count'] : 0;

// Get crime volume
// (INDEX + NI)/TOTAL
$volume_query = "SELECT (SUM(CASE WHEN `CRIME CLASSIFICATION` = 'index' THEN 1 ELSE 0 END) + SUM(CASE WHEN `CRIME CLASSIFICATION` = 'non-index' THEN 1 ELSE 0 END)) / COUNT(*) * 100 AS count FROM sanjuan";
if (!empty($year_condition)) {
    $volume_query .= " WHERE $year_condition";
}
$volume_result = $conn->query($volume_query);
$crime_volume = ($volume_result && $row = $volume_result->fetch_assoc()) ? number_format($row['count'], 2) : "0.00";

// Get crime rate
// (TOTAL/POPULATION)*100000
$population = 126347; // as of 2020  
$crime_rate = ($population > 0) ? ($total_cases / $population) * 100000 : 0;
$crime_rate = number_format($crime_rate, 2);

// Get crime clearance
// (CLEARED/TOTAL)*100
$crime_clearance = ($total_cases > 0) ? ($cleared_cases / $total_cases) * 100 : 0;
$crime_clearance = number_format($crime_clearance, 2);

// Get crime solution
// (SOLVED/TOTAL)*100
$crime_solution = ($total_cases > 0) ? ($solved_cases / $total_cases) * 100 : 0;
$crime_solution = number_format($crime_solution, 2);

//
// Get monthly trend
// Fetch crime data by month
$monthly_query = "SELECT month, crime_count, rank FROM (
                  SELECT `MONTH COMMITTED` AS month, 
                  COUNT(*) AS crime_count, 
                  RANK() OVER (ORDER BY COUNT(*) DESC) 
                  AS rank FROM sanjuan"; 
if (!empty($year_condition)) {
    $monthly_query .= " WHERE $year_condition 
                        GROUP BY `MONTH COMMITTED` ) ranked_months 
                        WHERE rank <= 12 ORDER BY crime_count DESC";
} else {
    $monthly_query .= " GROUP BY `MONTH COMMITTED` ) ranked_months 
                        WHERE rank <= 12 ORDER BY crime_count DESC";
}
$monthly_result = $conn->query($monthly_query);
$monthly_trend = $monthly_result->fetch_assoc();
// Extract data for Chart.js
$months = [];
$crime_counts = [];
foreach ($monthly_result as $row) {
    $months[] = $row['month'];
    $crime_counts[] = $row['crime_count'];
}
// Convert data to JSON for JavaScript
$months_json = json_encode($months);
$crime_counts_json = json_encode($crime_counts);


// Close connection
$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>San Juan Analytics</title>
    <link rel="icon" type="image/png" href="images/logo-square.png" />
    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"/>
    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- STYLESHEET -->
    <link rel="stylesheet" href="stylesheet.css" />
    <!-- FONT -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter" rel="stylesheet"/>

    <style>
      .card-body {
        background-color: #1d232c;
        color: #e8e3e1;
      }

      .card-title {
        font-weight: bold;
        display: inline;
      }
    </style>

  </head>

  <body>
    <!-- NAVBAR -->
    <nav class="navbar">
      <div class="container">
        <a class="navbar-logo" href="#">
          <img src="images/logo-with-text.png" height="90" style="margin: 3%"/>
        </a>
        <span class="navbar-text" href="#" style="text-decoration: underline">Log out</span
        >
      </div>
    </nav>

    <!-- TABS -->
    <nav class="navbar navbar-expand-lg" style="background-color: #1d232c">
      <div class="container">
        <div class="collapse navbar-collapse">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link" href="#">Overview</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="mapview.html">Map View</a>
            </li>
            <li class="nav-item">
              <a class="nav-link active" aria-current="page" href="#">Analytics</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="forecast.html">Forecasts</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">Activity Logs</a>
            </li>
          </ul>
          <span class="navbar-text" id="datetime"></span>
        </div>
      </div>
    </nav>

    <div class="container mt-4">
      <!-- CONTROLS -->
      <form method="GET">
        <label for="year">Select Year:</label>
        <select name="year" id="year" onchange="this.form.submit()">
            <option value="all" <?= (!isset($_GET['year']) || $_GET['year'] == 'all') ? 'selected' : '' ?>>All Time</option>
            <option value="2019" <?= (isset($_GET['year']) && $_GET['year'] == '2019') ? 'selected' : '' ?>>2019</option>
            <option value="2020" <?= (isset($_GET['year']) && $_GET['year'] == '2020') ? 'selected' : '' ?>>2020</option>
            <option value="2021" <?= (isset($_GET['year']) && $_GET['year'] == '2021') ? 'selected' : '' ?>>2021</option>
            <option value="2022" <?= (isset($_GET['year']) && $_GET['year'] == '2022') ? 'selected' : '' ?>>2022</option>
            <option value="2023" <?= (isset($_GET['year']) && $_GET['year'] == '2023') ? 'selected' : '' ?>>2023</option>
        </select>
      </form>
    </div>

    <!-- CONTAINERS -->

    <!-- 1ST ROW OF CARDS -->
    <div class="container mt-4">
      <div class="row">
        <!-- Card 1 / Pending -->
        <div class="col-lg-3 col-md-6 mb-1">
          <div class="card">
            <div class="card-body">
              <p class="card-title">Pending Cases</p>
              <?php 
                if ($pending_cases > 0) {
                  echo '<p class="card-text">' . $pending_cases . '</p>';
                } else {
                    echo '<p class="card-text">No Data</p>';
                }
                ?>
            </div>
          </div>
        </div>
        <!-- Card 2 / Cleared -->
        <div class="col-lg-3 col-md-6 mb-1">
          <div class="card">
            <div class="card-body">
              <p class="card-title">Cleared Cases</p>
              <?php 
                if ($cleared_cases > 0) {
                  echo '<p class="card-text">' . $cleared_cases . '</p>';
                } else {
                    echo '<p class="card-text">No Data</p>';
                }
                ?>
            </div>
          </div>
        </div>
        <!-- Card 3 / Solved -->
        <div class="col-lg-3 col-md-6 mb-1">
          <div class="card">
            <div class="card-body">
              <p class="card-title">Solved Cases</p>
              <?php 
                if ($solved_cases > 0) {
                  echo '<p class="card-text">' . $solved_cases . '</p>';
                } else {
                    echo '<p class="card-text">No Data</p>';
                }
                ?>
            </div>
          </div>
        </div>          
        <!-- Card 4 / Total -->
        <div class="col-lg-3 col-md-6 mb-1">
          <div class="card">
            <div class="card-body">
              <p class="card-title">Total Cases</p>
                <?php          
                if ($total_cases > 0) {
                    echo '<p class="card-text">' . $total_cases . '</p>';
                } else {
                    echo '<p class="card-text">No Data</p>';
                }
                ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2ND ROW -->
    <div class="container mt-4">
      <div class="row">
        <!-- Card 1 / Volume-->
        <div class="col-lg-3 col-md-6 mb-1">
          <div class="card">
            <div class="card-body">
              <p class="card-title">Crime Volume</p>
              <?php 
                // (INDEX + NI)/TOTAL
                if ($crime_volume > 0) {
                  echo '<p class="card-text">' . $crime_volume . '%</p>';
                } else {
                    echo '<p class="card-text">No Data</p>';
                }
                ?>
            </div>
          </div>
        </div>
        <!-- Card 2 / Rate -->
        <div class="col-lg-3 col-md-6 mb-1">
          <div class="card">
            <div class="card-body">
              <p class="card-title">Crime Rate</p><span>  per 100,000</span>
              <?php 
                // (TOTAL/POPULATION)*100000
                if ($crime_rate > 0) {
                  echo '<p class="card-text">' . $crime_rate . '%</p>';
                } else {
                    echo '<p class="card-text">No Data</p>';
                }
                ?>
            </div>
          </div>
        </div>
        <!-- Card 3 / Clearance -->
        <div class="col-lg-3 col-md-6 mb-1">
          <div class="card">
            <div class="card-body">
              <p class="card-title">Crime Clearance Efficiency</p>
              <?php 
                // (CLEARED/TOTAL)*100
                if ($crime_clearance > 0) {
                  echo '<p class="card-text">' . $crime_clearance . '%</p>';
                } else {
                    echo '<p class="card-text">No Data</p>';
                }
                ?>
            </div>
          </div>
        </div>
        <!-- Card 4 / Solution -->
        <div class="col-lg-3 col-md-6 mb-1">
          <div class="card">
            <div class="card-body">
              <p class="card-title">Crime Solution Efficiency</p>
              <?php 
                // (SOLVED/TOTAL)*100
                if ($crime_solution > 0) {
                  echo '<p class="card-text">' . $crime_solution . '%</p>';
                } else {
                    echo '<p class="card-text">No Data</p>';
                }
                ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 3RD ROW -->
    <div class="container mt-4">
      <div class="row">
        <!-- Card 1 -->
        <div class="col-lg-4 col-md-6">
          <div class="card" style="height: 300px;"> <!-- Adjust height for square shape -->
            <div class="card-body d-flex justify-content-center align-items-center">
              <h5 class="card-title">Card 1</h5>
            </div>
          </div>
        </div>
        <!-- Card 2 -->
        <div class="col-lg-4 col-md-6">
          <div class="card" style="height: 300px;">
            <div class="card-body">
              <p class="card-title">Peak Days of the Week</p>
              <canvas id="weeklyTrend"></canvas>
            </div>
          </div>
        </div>
        <!-- Card 3 / Peak Months-->
        <div class="col-lg-4 col-md-6">
          <div class="card" style="height: 300px;">
            <div class="card-body">
              <p class="card-title">Peak Months of the Year</p>
              <canvas id="monthlyTrend"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 4TH ROW -->
    <div class="container mt-4">
      <div class="row">
        <!-- Larger Card -->
        <div class="col-lg-7">
          <div class="card" style="height: 300px;"> <!-- Fixed height for consistency -->
            <div class="card-body d-flex justify-content-center align-items-center">
              <h5 class="card-title">Larger Card</h5>
            </div>
          </div>
        </div>
        <!-- Smaller Card -->
        <div class="col-lg-5">
          <div class="card" style="height: 300px;"> <!-- Same height as the larger card -->
            <div class="card-body d-flex justify-content-center align-items-center">
              <h5 class="card-title">Smaller Card</h5>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 5TH ROW -->
    <div class="container mt-4">
      <div class="row">
         <!-- Smaller Card -->
         <div class="col-lg-5">
          <div class="card" style="height: 300px;"> <!-- Same height as the larger card -->
            <div class="card-body d-flex justify-content-center align-items-center">
              <h5 class="card-title">Smaller Card</h5>
            </div>
          </div>
        </div>
        <!-- Larger Card -->
        <div class="col-lg-7">
          <div class="card" style="height: 300px;"> <!-- Fixed height for consistency -->
            <div class="card-body d-flex justify-content-center align-items-center">
              <h5 class="card-title">Larger Card</h5>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
      <p>Disclaimer: Lorem ipsum odor amet, consectetuer adipiscing elit.</p>
    </footer>

    <script>
        // DATE AND TIME
        var dt = new Date();
        var options = {
          timeZone: "Asia/Manila",
          year: "numeric",
          month: "long",
          day: "numeric",
          hour: "2-digit",
          minute: "2-digit",
          hour12: true, // For AM/PM format
        };
        document.getElementById("datetime").innerHTML =
          dt.toLocaleString("en-US", options) + " PHT";


        // WEEKLY TREND
        // Retrieve data from PHP
        // const months = <?php echo $months_json; ?>;
        // const crimeCounts = <?php echo $crime_counts_json; ?>;

        // // Create the line chart
        // const ctx = document.getElementById('monthlyTrend').getContext('2d');
        // new Chart(ctx, {
        //     type: 'line',
        //     data: {
        //         labels: months,
        //         datasets: [{
        //             label: 'Crime Trends per Month',
        //             data: crimeCounts,
        //             borderColor: '#00CFFF', // Bright Cyan Line
        //             backgroundColor: 'rgba(0, 207, 255, 0.3)', // Transparent Cyan Fill
        //             borderWidth: 3,
        //             pointBackgroundColor: '#FFB400', // Yellow Dots on Data Points
        //             pointRadius: 5,
        //             fill: true
        //         }]
        //     },
        //     options: {
        //         responsive: true,
        //         scales: {
        //             y: {
        //                 beginAtZero: true
        //             }
        //         }
        //     }
        // });
      
        // MONTHLY TREND
        // Retrieve data from PHP
        const months = <?php echo $months_json; ?>;
        const crimeCounts = <?php echo $crime_counts_json; ?>;

        // Create the line chart
        const ctx = document.getElementById('monthlyTrend').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Crime Trends per Month',
                    data: crimeCounts,
                    borderColor: '#00CFFF', // Bright Cyan Line
                    backgroundColor: 'rgba(0, 207, 255, 0.3)', // Transparent Cyan Fill
                    borderWidth: 3,
                    pointBackgroundColor: '#FFB400', // Yellow Dots on Data Points
                    pointRadius: 5,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
  </body>
</html>