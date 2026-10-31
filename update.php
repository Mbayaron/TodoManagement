<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$title = trim($_POST["title"] ?? "");
$description = trim($_POST["description"] ?? "");
$due_date = $_POST["due_date"] ?: null;
$status = $_POST["status"] ?? "Pending";

if (!$id || $id < 1 || $title === "" ||
    !in_array($status, ["Pending", "Completed"], true)) {
    exit("Invalid task information.");
}

$stmt = $conn->prepare(
    "UPDATE tasks
     SET title = ?, description = ?, due_date = ?, status = ?
     WHERE id = ?"
);

$stmt->bind_param(
    "ssssi",
    $title,
    $description,
    $due_date,
    $status,
    $id
);

$stmt->execute();
$stmt->close();

header("Location: index.php");
exit;
?>