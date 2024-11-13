<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// connection to database
require_once('../connection/connection.php');

// method GET to get user data
if($_SERVER['REQUEST_METHOD'] === 'GET'){

    //get detail user
    if (isset($_GET['account_uid']) || isset($_GET['username']) || isset($_GET['name'])) {
       // Initialize the base query and parameters array
        $query = "SELECT username, name FROM users WHERE ";
        $conditions = [];
        $params = [];

        // Add conditions based on which parameters are provided
        if (isset($_GET['account_uid'])) {
            $conditions[] = "account_uid = ?";
            $params[] = $_GET['account_uid'];
        }
        if (isset($_GET['username'])) {
            $conditions[] = "username = ?";
            $params[] = $_GET['username'];
        }
        if (isset($_GET['name'])) {
            $conditions[] = "name = ?";
            $params[] = $_GET['name'];
        }

        // Join conditions with "OR" and finalize the query
        $query .= implode(" OR ", $conditions);

        // Prepare the statement
        $stmt = mysqli_prepare($connect, $query);

        // Dynamically bind parameters based on provided values
        if ($stmt) {
            // Generate type string for bind_param (e.g., "sss" for three strings)
            $types = str_repeat("s", count($params));
            mysqli_stmt_bind_param($stmt, $types, ...$params);

            // Execute the statement and fetch results
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            // Fetch all rows
            $accounts = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $accounts[] = $row;
            }

            // Send response
            http_response_code(200);
            echo json_encode([
                "StatusCode" => 200,
                "Status" => "Success",
                "Data" => $accounts
            ]);
        } else {
            // Handle query preparation error
            http_response_code(500);
            echo json_encode([
                "StatusCode" => 500,
                "Status" => "Error",
                "Message" => "Failed to prepare the SQL query"
            ]);
        }

    //get all user data     
    } else {
        $query = "SELECT * FROM users";
        $result = mysqli_query($connect, $query);

        if($result){
            //set accounts variable to array
            $accounts = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $accounts[] = $row;
            }
            //give back response 200 if success
            http_response_code(200);
            echo json_encode(
                array(
                    "StatusCode" => 200,
                    'Status' => 'Success',
                    "Data" => $accounts  // Changed $employees to $accounts
                )
            );
        } else {
            http_response_code(404);
            echo json_encode(
                array(
                    "StatusCode" => 404,
                    'Status' => 'Not Found',
                    "Message" => 'Your data has not found!! Please check again your data!!'
                )
            );
        }
    }
    
// method POST to insert user data
} else if($_SERVER['REQUEST_METHOD'] === 'POST'){

    // retrieve user data
    if (isset($_POST['username']) && isset($_POST['name'])) {
        $username = $_POST['username'];
        $name = $_POST['name'];

        $query = "INSERT INTO users (account_uid, name, username, created_at) VALUES (UUID(), '$name', '$username', NOW())";
        if (mysqli_query($connect, $query)) {
            // If success then send back response 200 to users
            http_response_code(200);
            echo json_encode(
                array(
                    "StatusCode" => 200,
                    'Status' => 'Success',
                    "message" => "Success: Data inserted successfully"
                )
            );
        } else {
            // If failed then send back response 500 to users
            http_response_code(500);
            echo json_encode(
                array(
                    "StatusCode" => 500,
                    'Status' => 'Error',
                    "message" => "Error: Unable to insert data - " . mysqli_error($connect)
                )
            );
        }
    } else {
        // If POST data is missing
        http_response_code(400);
        echo json_encode(
            array(
                "StatusCode" => 400,
                'Status' => 'Bad Request',
                "message" => "Error: Missing username or name in POST data"
            )
        );
    }

// method PUT to update user data
} else if($_SERVER['REQUEST_METHOD'] === 'PUT'){
    // Retrieve data from the request body
    $input = json_decode(file_get_contents('php://input'), true);

    // Check if required fields are provided
    if (isset($input['account_uid']) && (isset($input['username']) || isset($input['name']))) {
        $account_uid = $input['account_uid'];
        $username = $input['username'] ?? null;
        $name = $input['name'] ?? null;

        // Build the SQL query dynamically based on the provided fields
        $query = "UPDATE users SET ";
        $params = [];
        $types = '';

        if ($username) {
            $query .= "username = ?, ";
            $params[] = $username;
            $types .= 's';
        }

        if ($name) {
            $query .= "name = ?, ";
            $params[] = $name;
            $types .= 's';
        }

        // Remove the trailing comma and space, and add WHERE clause
        $query = rtrim($query, ", ") . " WHERE account_uid = ?";
        $params[] = $account_uid;
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
                    "Message" => "User data updated successfully"
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "StatusCode" => 500,
                    "Status" => "Error",
                    "Message" => "Failed to update user data - " . mysqli_error($connect)
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
            "Message" => "Missing account_uid or fields to update"
        ]);
    }

// method DELETE to delete user data
} else if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
    // Retrieve data from the request body
    $input = json_decode(file_get_contents('php://input'), true);

    // Check if account_uid is provided
    if (isset($input['account_uid'])) {
        $account_uid = $input['account_uid'];

        // Prepare the DELETE query
        $query = "DELETE FROM users WHERE account_uid = ?";
        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            // Bind parameters and execute
            mysqli_stmt_bind_param($stmt, 's', $account_uid);
            if (mysqli_stmt_execute($stmt)) {
                http_response_code(200);
                echo json_encode([
                    "StatusCode" => 200,
                    "Status" => "Success",
                    "Message" => "User data deleted successfully"
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "StatusCode" => 500,
                    "Status" => "Error",
                    "Message" => "Failed to delete user data - " . mysqli_error($connect)
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
            "Message" => "Missing account_uid for deletion"
        ]);
    }
} 

?>