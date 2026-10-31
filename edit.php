<?php
include "db.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("Invalid task ID.");
}

$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$task = $result->fetch_assoc();
$stmt->close();

if (!$task) {
    exit("Task not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Task</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Edit Task</h1>

    <form method="POST" action="update.php">
        <input type="hidden" name="id"
               value="<?= (int)$task["id"] ?>">

        <label>Task Title</label>
        <input type="text" name="title"
               maxlength="100"
               value="<?= htmlspecialchars($task["title"]) ?>"
               required>

        <label>Description</label>
        <textarea name="description"><?= htmlspecialchars(
            $task["description"] ?? ""
        ) ?></textarea>

        <label>Due Date</label>
        <input type="date" name="due_date"
               value="<?= htmlspecialchars($task["due_date"] ?? "") ?>">

        <label>Status</label>
        <select name="status">
            <option value="Pending"
                <?= $task["status"] === "Pending" ? "selected" : "" ?>>
                Pending
            </option>

            <option value="Completed"
                <?= $task["status"] === "Completed" ? "selected" : "" ?>>
                Completed
            </option>
        </select>

        <button type="submit">Update Task</button>
        <a href="index.php">Cancel</a>
    </form>
</div>

</body>
</html>