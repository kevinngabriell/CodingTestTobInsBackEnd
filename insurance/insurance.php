<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Connection to the database
require_once('../connection/connection.php');

if (!$connect) {
    http_response_code(500);
    echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Database connection failed"]);
    exit();
}

// Helper function to generate policy number based on date and sequence
function generatePolicyNumber($connect) {
    $datePrefix = date("Ymd");
    $query = "SELECT COUNT(*) AS count FROM Insurance WHERE policy_number LIKE '$datePrefix%'";
    $result = mysqli_query($connect, $query);
    $row = mysqli_fetch_assoc($result);
    $count = $row['count'] + 1;
    return $datePrefix . str_pad($count, 4, "0", STR_PAD_LEFT);
}

// POST: Create a new policy
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // List of required fields
    $requiredFields = [
        'insured', 'effective_date', 'expiration_date', 'car_brand', 
        'car_type', 'car_year', 'car_price', 'premium_rate', 
        'premium_price', 'created_by'
    ];
    $missingFields = [];

    // Check for missing fields
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }

    // If there are missing fields, respond with a detailed error message
    if (!empty($missingFields)) {
        http_response_code(400);
        echo json_encode([
            "StatusCode" => 400,
            "Status" => "Bad Request",
            "Message" => "Missing required data: " . implode(", ", $missingFields)
        ]);
        exit();
    }

    // All fields are present; proceed with insertion
    $policy_number = generatePolicyNumber($connect);
    $insured = $_POST['insured'];
    $effective_date = $_POST['effective_date'];
    $expiration_date = $_POST['expiration_date'];
    $car_brand = $_POST['car_brand'];
    $car_type = $_POST['car_type'];
    $car_year = $_POST['car_year'];
    $car_price = $_POST['car_price'];
    $premium_rate = $_POST['premium_rate'];
    $premium_price = $_POST['premium_price'];
    $created_by = $_POST['created_by'];

    // Prepare SQL query to insert data
    $query = "INSERT INTO Insurance (policy_number, insured, effective_date, expiration_date, car_brand, car_type, car_year, car_price, premium_rate, premium_price, created_at, created_by)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
    $stmt = mysqli_prepare($connect, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssssidsds", $policy_number, $insured, $effective_date, $expiration_date, $car_brand, $car_type, $car_year, $car_price, $premium_rate, $premium_price, $created_by);

        if (mysqli_stmt_execute($stmt)) {
            http_response_code(201);
            echo json_encode(["StatusCode" => 201, "Status" => "Success", "Message" => "Policy created successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to create policy: " . mysqli_error($connect)]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to prepare SQL statement"]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if a specific policy_number is provided in the query parameters
    if (isset($_GET['policy_number'])) {
        // Retrieve the details of a single policy
        $policy_number = $_GET['policy_number'];
        $query = "SELECT I.policy_number, UI.name as insured, I.effective_date, I.expiration_date, CONCAT(CB.name, ' - ', CT.name) AS car_name, I.car_price, I.car_year, I.premium_price, RI.rate as premium_rate 
                  FROM Insurance I
                  LEFT JOIN CarBrand CB ON I.car_brand = CB.uid
                  LEFT JOIN CarType CT ON I.car_type = CT.uid
                  LEFT JOIN users UI ON I.insured = UI.account_uid
                  LEFT JOIN Rate RI ON I.premium_rate = RI.uid 
                  WHERE I.policy_number = ?";
        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $policy_number);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $policy = mysqli_fetch_assoc($result);

            if ($policy) {
                // Return the policy details if found
                http_response_code(200);
                echo json_encode(["StatusCode" => 200, "Status" => "Success", "Data" => $policy]);
            } else {
                // Policy not found
                http_response_code(404);
                echo json_encode(["StatusCode" => 404, "Status" => "Not Found", "Message" => "Policy not found"]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to prepare SQL statement"]);
        }
    } else {
        // Retrieve all policies if no specific policy_number is provided
        $query = "SELECT I.policy_number, UI.name as insured, I.effective_date, I.expiration_date, CONCAT(CB.name, ' - ', CT.name) AS car_name, I.car_price, I.car_year, I.premium_price, RI.rate as premium_rate 
                  FROM Insurance I
                  LEFT JOIN CarBrand CB ON I.car_brand = CB.uid
                  LEFT JOIN CarType CT ON I.car_type = CT.uid
                  LEFT JOIN users UI ON I.insured = UI.account_uid
                  LEFT JOIN Rate RI ON I.premium_rate = RI.uid";
        $result = mysqli_query($connect, $query);

        if ($result) {
            $policies = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $policies[] = $row;
            }
            // Return the list of all policies
            http_response_code(200);
            echo json_encode(["StatusCode" => 200, "Status" => "Success", "Data" => $policies]);
        } else {
            http_response_code(500);
            echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to fetch policies"]);
        }
    }

} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['policy_number']) && isset($input['updated_by'])) {
        $policy_number = $input['policy_number'];
        $updated_by = $input['updated_by'];
        $effective_date = $input['effective_date'] ?? null;
        $expiration_date = $input['expiration_date'] ?? null;
        $car_brand = $input['car_brand'] ?? null;
        $car_type = $input['car_type'] ?? null;
        $car_year = $input['car_year'] ?? null;
        $car_price = $input['car_price'] ?? null;
        $premium_rate = $input['premium_rate'] ?? null;
        $premium_price = $input['premium_price'] ?? null;

        // Prepare the SQL query with only fields that are set for update
        $query = "UPDATE Insurance SET ";
        $params = [];
        $types = '';

        if ($effective_date) {
            $query .= "effective_date = ?, ";
            $params[] = $effective_date;
            $types .= 's';
        }
        if ($expiration_date) {
            $query .= "expiration_date = ?, ";
            $params[] = $expiration_date;
            $types .= 's';
        }
        if ($car_brand) {
            $query .= "car_brand = ?, ";
            $params[] = $car_brand;
            $types .= 's';
        }
        if ($car_type) {
            $query .= "car_type = ?, ";
            $params[] = $car_type;
            $types .= 's';
        }
        if ($car_year) {
            $query .= "car_year = ?, ";
            $params[] = $car_year;
            $types .= 'i';
        }
        if ($car_price) {
            $query .= "car_price = ?, ";
            $params[] = $car_price;
            $types .= 'd';
        }
        if ($premium_rate) {
            $query .= "premium_rate = ?, ";
            $params[] = $premium_rate;
            $types .= 's';
        }
        if ($premium_price) {
            $query .= "premium_price = ?, ";
            $params[] = $premium_price;
            $types .= 's';
        }

        // Append updated_by and updated_at fields
        $query .= "updated_by = ?, updated_at = NOW() WHERE policy_number = ?";
        $params[] = $updated_by;
        $params[] = $policy_number;
        $types .= 'ss';

        // Prepare and execute the SQL statement
        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);

            if (mysqli_stmt_execute($stmt)) {
                http_response_code(200);
                echo json_encode(["StatusCode" => 200, "Status" => "Success", "Message" => "Policy updated successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to update policy"]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to prepare SQL statement"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["StatusCode" => 400, "Status" => "Bad Request", "Message" => "Missing policy_number or updated_by"]);
    }

} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['policy_number'])) {
        $policy_number = $input['policy_number'];

        $query = "DELETE FROM Insurance WHERE policy_number = ?";
        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $policy_number);

            if (mysqli_stmt_execute($stmt)) {
                http_response_code(200);
                echo json_encode(["StatusCode" => 200, "Status" => "Success", "Message" => "Policy deleted successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to delete policy"]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to prepare SQL statement"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["StatusCode" => 400, "Status" => "Bad Request", "Message" => "Missing policy_number for deletion"]);
    }
}
?>