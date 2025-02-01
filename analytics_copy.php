<?php

include('connect.php');

// Get the selected year
$year = isset($_GET['year']) ? $_GET['year'] : 'all';

// Condition for SQL queries (if a specific year is selected, add WHERE clause)
$year_condition = ($year === 'all') ? "" : " WHERE YEAR(`DATE COMMITTED`) = $year";

// Get total crime cases
$total_cases_query = "SELECT COUNT(*) AS count FROM sanjuan $year_condition";
$total_cases_result = $conn->query($total_cases_query);
$total_cases = ($row = $total_cases_result->fetch_assoc()) ? (int) $row['count'] : 0;

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
                // $result = $conn->query($pending_cases);                
                // if ($result) {
                //     $row = $result->fetch_assoc();
                //     $count = $row['count']; // Fetch the count value
                //     echo '<p class="card-text">' . $count . '</p>';
                // } else {
                //     echo '<p class="card-text">No Data</p>';
                // }
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
                // $result = $conn->query($cleared_cases);                
                // if ($result) {
                //     $row = $result->fetch_assoc();
                //     $count = $row['count']; // Fetch the count value
                //     echo '<p class="card-text">' . $count . '</p>';
                // } else {
                //     echo '<p class="card-text">No Data</p>';
                // }
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
                // $result = $conn->query($solved_cases);                
                // if ($result) {
                //     $row = $result->fetch_assoc();
                //     $count = $row['count']; // Fetch the count value
                //     echo '<p class="card-text">' . $count . '</p>';
                // } else {
                //     echo '<p class="card-text">No Data</p>';
                // }
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
                    //$count = $row['count']; // Fetch the count value
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
        <!-- Card 1 -->
        <div class="col-lg-3 col-md-6 mb-1">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Crime Volume</h5> 
              <?php 
                // (INDEX + NI)/TOTAL
                // $result = $conn->query($crime_volume);                
                // if ($result) {
                //     $row = $result->fetch_assoc();
                //     $count = number_format($row['count'], 2); //round to 2 decimal places
                //     echo '<p class="card-text">' . $count . '%</p>';
                // } else {
                //     echo '<p class="card-text">No Data</p>';
                // }
                ?>
            </div>
          </div>
        </div>
        <!-- Card 2 -->
        <div class="col-lg-3 col-md-6 mb-1">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Crime Rate</h5><span>  per 100,000</span>
              <?php 
                // (TOTAL/POPULATION)*100000
                // $result = $conn->query($crime_rate);                
                // if ($result) {
                //     $row = $result->fetch_assoc();
                //     $count = number_format($row['count'], 2); //round to 2 decimal places
                //     echo '<p class="card-text">' . $count . '%</p>';
                // } else {
                //     echo '<p class="card-text">No Data</p>';
                // }
                ?>
            </div>
          </div>
        </div>
        <!-- Card 3 -->
        <div class="col-lg-3 col-md-6 mb-1">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Crime Clearance Efficiency</h5>
              <?php 
                // (CLEARED/TOTAL)*100
                ?>
            </div>
          </div>
        </div>
        <!-- Card 4 -->
        <div class="col-lg-3 col-md-6 mb-1">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Card 4</h5>
              <p class="card-text">This is card 4 content.</p>
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
            <div class="card-body d-flex justify-content-center align-items-center">
              <h5 class="card-title">Card 2</h5>
            </div>
          </div>
        </div>
        <!-- Card 3 -->
        <div class="col-lg-4 col-md-6">
          <div class="card" style="height: 300px;">
            <div class="card-body d-flex justify-content-center align-items-center">
              <h5 class="card-title">Card 3</h5>
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
    </script>
  </body>
</html>