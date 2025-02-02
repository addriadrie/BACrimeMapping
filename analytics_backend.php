<?php

// Get the selected year
$year = isset($_GET['year']) ? $_GET['year'] : 'all';

// Condition for SQL queries (if a specific year is selected, add WHERE clause)
$year_condition = ($year === 'all') ? "" : "YEAR(`DATE COMMITTED`) = $year";

// Get pending crime cases
$pending_query = "SELECT COUNT(*) AS count FROM sanjuan WHERE";
if (!empty($year_condition)) {
    $pending_query .= " $year_condition AND";
}

$pending_query .= " `CASE STATUS` = 'Under Investigation'";
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
// Get daily trend
// Fetch crime data by day
$daily_query = "SELECT time, crime_count, rank FROM (
  SELECT `TIME COMMITTED` AS time, 
         COUNT(*) AS crime_count, 
         RANK() OVER (ORDER BY COUNT(*) DESC) AS rank 
  FROM sanjuan";

// Add condition if year filtering is applied
if (!empty($year_condition)) {
    $daily_query .= " WHERE $year_condition";
}

$daily_query .= " GROUP BY `TIME COMMITTED` ) ranked_time
WHERE rank <= 10 ORDER BY crime_count DESC";

// Execute Query
$daily_result = $conn->query($daily_query);

// Extract Data for Chart.js
$times = [];
$daily_crimes = [];
foreach ($daily_result as $row) {
    $times[] = $row['time'];
    $daily_crimes[] = $row['crime_count'];
}

// Convert Data to JSON for JavaScript
$time_json = json_encode($times);
$daily_crimes_json = json_encode($daily_crimes);
//

//
// Get weekly trend
// Fetch crime data by week
$weekly_query = "SELECT day, crime_count, rank FROM (
  SELECT `DAY COMMITTED` AS day, 
  COUNT(*) AS crime_count, 
  RANK() OVER (ORDER BY COUNT(*) DESC) 
  AS rank FROM sanjuan";
if (!empty($year_condition)) {
    $weekly_query .= " WHERE $year_condition";
}

$weekly_query .= " GROUP BY `DAY COMMITTED` ) ranked_days
WHERE rank <= 7 ORDER BY crime_count DESC";

$weekly_result = $conn->query($weekly_query);
$weekly_trend = $weekly_result->fetch_assoc();

// Extract data for Chart.js
$days = [];
$weekly_crimes = [];
foreach ($weekly_result as $row) {
    $days[] = $row['day'];
    $weekly_crimes[] = $row['crime_count'];
}

// Convert data to JSON for JavaScript
$days_json = json_encode($days);
$weekly_crimes_json = json_encode($weekly_crimes);
//

//
// Get monthly trend
// Fetch crime data by month
$monthly_query = "SELECT month, crime_count, rank FROM (
                  SELECT `MONTH COMMITTED` AS month, 
                  COUNT(*) AS crime_count, 
                  RANK() OVER (ORDER BY COUNT(*) DESC) 
                  AS rank FROM sanjuan";
if (!empty($year_condition)) {
    $monthly_query .= " WHERE $year_condition";
}

$monthly_query .= " GROUP BY `MONTH COMMITTED` ) ranked_months 
                        WHERE rank <= 12 ORDER BY crime_count DESC";

$monthly_result = $conn->query($monthly_query);
$monthly_trend = $monthly_result->fetch_assoc();

// Extract data for Chart.js
$months = [];
$monthly_crimes = [];
foreach ($monthly_result as $row) {
    $months[] = $row['month'];
    $monthly_crimes[] = $row['crime_count'];
}

// Convert data to JSON for JavaScript
$months_json = json_encode($months);
$monthly_crimes_json = json_encode($monthly_crimes);


// Offenses
$offenses_query = "SELECT OFFENSE, COUNT(*) AS crime_count FROM sanjuan";
if (!empty($year_condition)) {
    $offenses_query .= " WHERE $year_condition";
}
$offenses_query .= " GROUP BY OFFENSE ORDER BY crime_count DESC";
$offenses_result = $conn->query($offenses_query);

// Initialize arrays
$offenses = [];
$crime_counts = [];
while ($row = $offenses_result->fetch_assoc()) {
    $offenses[] = $row['OFFENSE'];  // Crime types
    $crime_counts[] = $row['crime_count'];  // Count of each crime
}

// Convert to JSON
$offense_json = json_encode($offenses);
$offense_counts_json = json_encode($crime_counts);

// Close connection
$conn->close();
?>