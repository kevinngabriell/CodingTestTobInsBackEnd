<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// connection to database
require_once('../connection/connection.php');

// Helper function to generate unique UID
function generateUid() {
    return uniqid('', true);
}

// POST: Create a new rate entry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['rate']) && isset($_POST['created_by'])) {
        $uid = generateUid();
        $rate = $_POST['rate'];
        $created_by = $_POST['created_by'];

        $query = "INSERT INTO Rate (uid, rate, created_at, created_by) VALUES (?, ?, NOW(), ?)";
        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sds", $uid, $rate, $created_by);
            if (mysqli_stmt_execute($stmt)) {
                http_response_code(200);
                echo json_encode(["StatusCode" => 200, "Status" => "Success", "Message" => "Rate created successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to insert data: " . mysqli_error($connect)]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to prepare SQL statement"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["StatusCode" => 400, "Status" => "Bad Request", "Message" => "Missing required data"]);
    }
}

// GET: Fetch all rates or a specific rate by UID
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['uid'])) {
        // Get a single rate by UID
        $uid = $_GET['uid'];
        $query = "SELECT R.uid, rate, R.created_at, CU.name as created_by, R.updated_at, UU.name as updated_by
            FROM Rate R 
            LEFT JOIN users CU ON CU.account_uid = R.created_by
            LEFT JOIN users UU ON UU.account_uid = R.updated_by 
            WHERE R.uid = ?";
        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $uid);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $rate = mysqli_fetch_assoc($result);

            if ($rate) {
                http_response_code(200);
                echo json_encode(["StatusCode" => 200, "Status" => "Success", "Data" => $rate]);
            } else {
                http_response_code(404);
                echo json_encode(["StatusCode" => 404, "Status" => "Not Found", "Message" => "Rate not found"]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to prepare SQL statement"]);
        }
    } else {
        // Get all rates
        $query = "SELECT R.uid, rate, R.created_at, CU.name as created_by, R.updated_at, UU.name as updated_by
            FROM Rate R 
            LEFT JOIN users CU ON CU.account_uid = R.created_by
            LEFT JOIN users UU ON UU.account_uid = R.updated_by;";
        $result = mysqli_query($connect, $query);

        if ($result) {
            $rates = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $rates[] = $row;
            }

            http_response_code(200);
            echo json_encode(["StatusCode" => 200, "Status" => "Success", "Data" => $rates]);
        } else {
            http_response_code(500);
            echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to fetch rates"]);
        }
    }
}

// PUT: Update an existing rate entry
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['uid']) && isset($input['rate'])) {
        $uid = $input['uid'];
        $rate = $input['rate'];
        $updated_by = $input['updated_by'] ?? null;

        $query = "UPDATE Rate SET rate = ?, updated_at = NOW(), updated_by = ? WHERE uid = ?";
        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "dss", $rate, $updated_by, $uid);
            if (mysqli_stmt_execute($stmt)) {
                http_response_code(200);
                echo json_encode(["StatusCode" => 200, "Status" => "Success", "Message" => "Rate updated successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to update rate"]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to prepare SQL statement"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["StatusCode" => 400, "Status" => "Bad Request", "Message" => "Missing required data for update"]);
    }
}

// DELETE: Delete a rate entry
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['uid'])) {
        $uid = $input['uid'];
        $query = "DELETE FROM Rate WHERE uid = ?";
        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $uid);
            if (mysqli_stmt_execute($stmt)) {
                http_response_code(200);
                echo json_encode(["StatusCode" => 200, "Status" => "Success", "Message" => "Rate deleted successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to delete rate"]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to prepare SQL statement"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["StatusCode" => 400, "Status" => "Bad Request", "Message" => "Missing uid for deletion"]);
    }
}
?>