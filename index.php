<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $due_date = $_POST["due_date"] ?: null;

    if ($title !== "") {
        $stmt = $conn->prepare(
            "INSERT INTO tasks (title, description, due_date)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param("sss", $title, $description, $due_date);
        $stmt->execute();
        $stmt->close();

        header("Location: index.php");
        exit;
    }
}

$result = $conn->query("SELECT * FROM tasks ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Task and To-Do List Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Task and To-Do List</h1>
    <p>Organize your daily activities.</p>

    <h2>Add New Task</h2>

    <form method="POST" action="index.php">
        <label>Task Title</label>
        <input type="text" name="title" required maxlength="100">

        <label>Description</label>
        <textarea name="description"></textarea>

        <label>Due Date</label>
        <input type="date" name="due_date">

        <button type="submit">Add Task</button>
    </form>

    <h2>My Tasks</h2>

    <div class="task-list">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($task = $result->fetch_assoc()): ?>
                <div class="task">
                    <h3>
                        <?= htmlspecialchars($task["title"]) ?>
                    </h3>

                    <p>
                        <?= nl2br(htmlspecialchars(
                            $task["description"] ?? ""
                        )) ?>
                    </p>

                    <p>
                        Due Date:
                        <?= htmlspecialchars($task["due_date"] ?? "Not set") ?>
                    </p>

                    <p>
                        Status:
                        <strong>
                            <?= htmlspecialchars($task["status"]) ?>
                        </strong>
                    </p>

                    <a class="edit"
                       href="edit.php?id=<?= (int)$task["id"] ?>">
                        Edit
                    </a>

                    <form method="POST" action="delete.php"
                          class="delete-form"
                          onsubmit="return confirm('Delete this task?');">
                        <input type="hidden" name="id"
                               value="<?= (int)$task["id"] ?>">
                        <button type="submit" class="delete">
                            Delete
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No tasks yet. Add your first task above!</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>