<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// connection to database
require_once('../connection/connection.php');

// method GET to get user data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['uid'])) {
        // Use only one check for 'uid'
        $query = "SELECT CB.uid, CB.name AS car_brand_name, CB.created_at, 
                 CU.name AS created_by, CB.updated_at, UU.name AS updated_by
          FROM CarBrand CB 
          LEFT JOIN users CU ON CU.account_uid = CB.created_by
          LEFT JOIN users UU ON UU.account_uid = CB.updated_by WHERE CB.uid = ?";
        $stmt = mysqli_prepare($connect, $query);
        $uid = $_GET['uid'];
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $uid);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $carbrand = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $carbrand[] = $row;
            }

            http_response_code(200);
            echo json_encode(["StatusCode" => 200, "Status" => "Success", "Data" => $carbrand]);
        } else {
            http_response_code(500);
            echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to prepare the SQL query"]);
        }
    } else {
       $query = "SELECT CB.uid, CB.name AS car_brand_name, CB.created_at, 
                 CU.name AS created_by, CB.updated_at, UU.name AS updated_by
          FROM CarBrand CB 
          LEFT JOIN users CU ON CU.account_uid = CB.created_by
          LEFT JOIN users UU ON UU.account_uid = CB.updated_by;";
        $result = mysqli_query($connect, $query);

        if ($result) {
            $carbrand = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $carbrand[] = $row;
            }

            http_response_code(200);
            echo json_encode(["StatusCode" => 200, "Status" => "Success", "Data" => $carbrand]);
        } else {
            http_response_code(404);
            echo json_encode(["StatusCode" => 404, "Status" => "Not Found", "Message" => "Data not found"]);
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['account_uid']) && isset($_POST['name'])) {
        $account_uid = $_POST['account_uid'];
        $name = $_POST['name'];

        $query = "INSERT INTO CarBrand (uid, name, created_at, created_by) VALUES (UUID(), ?, NOW(), ?)";
        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $name, $account_uid);
            if (mysqli_stmt_execute($stmt)) {
                http_response_code(200);
                echo json_encode(["StatusCode" => 200, "Status" => "Success", "Message" => "Data inserted successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Error inserting data"]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to prepare statement"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["StatusCode" => 400, "Status" => "Bad Request", "Message" => "Missing account_uid or name"]);
    }

} else if($_SERVER['REQUEST_METHOD'] === 'PUT'){
    // Retrieve data from the request body
    $input = json_decode(file_get_contents('php://input'), true);

    // Check if required fields are provided
    if (isset($input['uid']) && (isset($input['name']) || isset($input['account_uid']))) {
        $uid = $input['uid'];
        $name = $input['name'] ?? null;
        $account_uid = $input['account_uid'] ?? null;

        // Build the SQL query dynamically based on the provided fields
        $query = "UPDATE CarBrand SET ";
        $params = [];
        $types = '';

        if ($name) {
            $query .= "name = ?, ";
            $params[] = $name;
            $types .= 's';
        }

        if ($account_uid) {
            $query .= 'updated_by = ?, updated_at = NOW()';
            $params[] = $account_uid;
            $types .= 's';
        }

        // Remove the trailing comma and space, and add WHERE clause
        $query = rtrim($query, ", ") . " WHERE uid = ?";
        $params[] = $uid;
        $types .= 's';

        // Prepare the statement
        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            // Bind parameters and execute
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            if (mysqli_stmt_execute($stmt)) {
                http_response_code(200);
                echo json_encode([
                    "StatusCode" => 200,
                    "Status" => "Success",
                    "Message" => "Car brand data updated successfully"
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "StatusCode" => 500,
                    "Status" => "Error",
                    "Message" => "Failed to update car brand data - " . mysqli_error($connect)
                ]);
            }
        } else {
            http_response_code(500);
            echo json_encode([
                "StatusCode" => 500,
                "Status" => "Error",
                "Message" => "Failed to prepare SQL statement"
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            "StatusCode" => 400,
            "Status" => "Bad Request",
            "Message" => "Missing uid or fields to update"
        ]);
    }

// method DELETE to delete user data
} else if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
    // Retrieve data from the request body
    $input = json_decode(file_get_contents('php://input'), true);

    // Check if uid is provided
    if (isset($input['uid'])) {
        $uid = $input['uid'];

        // Prepare the DELETE query
        $query = "DELETE FROM CarBrand WHERE uid = ?";
        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            // Bind parameters and execute
            mysqli_stmt_bind_param($stmt, 's', $uid);
            if (mysqli_stmt_execute($stmt)) {
                http_response_code(200);
                echo json_encode([
                    "StatusCode" => 200,
                    "Status" => "Success",
                    "Message" => "Car brand data deleted successfully"
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "StatusCode" => 500,
                    "Status" => "Error",
                    "Message" => "Failed to delete car brand data - " . mysqli_error($connect)
                ]);
            }
        } else {
            http_response_code(500);
            echo json_encode([
                "StatusCode" => 500,
                "Status" => "Error",
                "Message" => "Failed to prepare SQL statement"
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            "StatusCode" => 400,
            "Status" => "Bad Request",
            "Message" => "Missing uid for deletion"
        ]);
    }
} 

?>