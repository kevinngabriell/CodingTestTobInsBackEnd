<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// connection to database
require_once('../connection/connection.php');

if (!$connect) {
    http_response_code(500);
    echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Database connection failed"]);
    exit();
}

// POST: Create new car type
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['name']) && isset($_POST['brand']) && isset($_POST['created_by'])) {
        $name = $_POST['name'];
        $brand = $_POST['brand'];
        $created_by = $_POST['created_by'];

        $query = "INSERT INTO CarType (uid, name, brand, created_at, created_by) VALUES (UUID(), ?, ?, NOW(), ?)";
        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $name, $brand, $created_by);
            if (mysqli_stmt_execute($stmt)) {
                http_response_code(200);
                echo json_encode(["StatusCode" => 200, "Status" => "Success", "Message" => "Data inserted successfully"]);
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

// GET: Fetch all car types or specific car type
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['uid'])) {
        // Get a single car type by UID
        $uid = $_GET['uid'];
        $query = "SELECT 
            CT.uid, 
            CT.name, 
            CB.name AS brand, 
            CT.created_at, 
            CU.name AS created_by, 
            CT.updated_at, 
            UU.name AS updated_by
        FROM 
            CarType CT
        LEFT JOIN 
            CarBrand CB ON CT.brand = CB.uid
        LEFT JOIN 
            users CU ON CT.created_by = CU.account_uid
        LEFT JOIN 
            users UU ON CT.updated_by = UU.account_uid
        WHERE CT.uid = ?";
        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $uid);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $carType = mysqli_fetch_assoc($result);

            if ($carType) {
                http_response_code(200);
                echo json_encode(["StatusCode" => 200, "Status" => "Success", "Data" => $carType]);
            } else {
                http_response_code(404);
                echo json_encode(["StatusCode" => 404, "Status" => "Not Found", "Message" => "Car type not found"]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to prepare SQL statement"]);
        }
    } else if (isset($_GET['brand'])){
        // Get list of car based on the brand UID
        $brand = $_GET['brand'];
        $query = "SELECT 
            CT.uid, 
            CT.name, 
            CB.name AS brand, 
            CT.created_at, 
            CU.name AS created_by, 
            CT.updated_at, 
            UU.name AS updated_by
        FROM 
            CarType CT
        LEFT JOIN 
            CarBrand CB ON CT.brand = CB.uid
        LEFT JOIN 
            users CU ON CT.created_by = CU.account_uid
        LEFT JOIN 
            users UU ON CT.updated_by = UU.account_uid
        WHERE CT.brand = ?";
        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $brand);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $carType = mysqli_fetch_assoc($result);

            if ($carType) {
                http_response_code(200);
                echo json_encode(["StatusCode" => 200, "Status" => "Success", "Data" => $carType]);
            } else {
                http_response_code(404);
                echo json_encode(["StatusCode" => 404, "Status" => "Not Found", "Message" => "Car type not found"]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to prepare SQL statement"]);
        }
    } else {
        // Get all car types
       $query = "SELECT 
            CT.uid, 
            CT.name, 
            CB.name AS brand, 
            CT.created_at, 
            CU.name AS created_by, 
            CT.updated_at, 
            UU.name AS updated_by
        FROM 
            CarType CT
        LEFT JOIN 
            CarBrand CB ON CT.brand = CB.uid
        LEFT JOIN 
            users CU ON CT.created_by = CU.account_uid
        LEFT JOIN 
            users UU ON CT.updated_by = UU.account_uid;";
        $result = mysqli_query($connect, $query);

        if ($result) {
            $carTypes = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $carTypes[] = $row;
            }

            http_response_code(200);
            echo json_encode(["StatusCode" => 200, "Status" => "Success", "Data" => $carTypes]);
        } else {
            http_response_code(500);
            echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to fetch car types"]);
        }
    }
}

// PUT: Update an existing car type
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['uid']) && (isset($input['name']) || isset($input['brand']))) {
        $uid = $input['uid'];
        $name = $input['name'] ?? null;
        $brand = $input['brand'] ?? null;
        $updated_by = $input['updated_by'] ?? null;

        $query = "UPDATE CarType SET ";
        $params = [];
        $types = '';

        if ($name) {
            $query .= "name = ?, ";
            $params[] = $name;
            $types .= 's';
        }

        if ($brand) {
            $query .= "brand = ?, ";
            $params[] = $brand;
            $types .= 's';
        }

        if ($updated_by) {
            $query .= "updated_by = ?, updated_at = NOW(), ";
            $params[] = $updated_by;
            $types .= 's';
        }

        $query = rtrim($query, ", ") . " WHERE uid = ?";
        $params[] = $uid;
        $types .= 's';

        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            if (mysqli_stmt_execute($stmt)) {
                http_response_code(200);
                echo json_encode(["StatusCode" => 200, "Status" => "Success", "Message" => "Car type updated successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to update car type"]);
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

// DELETE: Delete a car type
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['uid'])) {
        $uid = $input['uid'];
        $query = "DELETE FROM CarType WHERE uid = ?";
        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $uid);
            if (mysqli_stmt_execute($stmt)) {
                http_response_code(200);
                echo json_encode(["StatusCode" => 200, "Status" => "Success", "Message" => "Car type deleted successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["StatusCode" => 500, "Status" => "Error", "Message" => "Failed to delete car type"]);
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