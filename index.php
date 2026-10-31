<?php
date_default_timezone_set("Asia/Manila");
require "db.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_task"])) {
    $task_name = trim($_POST["task_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $due_date = ($_POST["due_date"] ?? "") ?: null;
    $priority = $_POST["priority"] ?? "Medium";

    if ($task_name === "") {
        header("Location: index.php?error=empty#add-task");
        exit;
    }

    if (!in_array($priority, ["Low", "Medium", "High"], true)) {
        $priority = "Medium";
    }

    $stmt = $conn->prepare(
        "INSERT INTO tasks (task_name, description, due_date, priority)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $task_name, $description, $due_date, $priority);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php?message=added#my-tasks");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["complete_task"])) {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

    if ($id && $id > 0) {
        $stmt = $conn->prepare(
            "UPDATE tasks SET status = 'Completed' WHERE id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: index.php?message=completed#my-tasks");
    exit;
}


$total = (int)$conn->query(
    "SELECT COUNT(*) AS n FROM tasks"
)->fetch_assoc()["n"];

$pending = (int)$conn->query(
    "SELECT COUNT(*) AS n FROM tasks WHERE status = 'Pending'"
)->fetch_assoc()["n"];

$progress = (int)$conn->query(
    "SELECT COUNT(*) AS n FROM tasks WHERE status = 'In Progress'"
)->fetch_assoc()["n"];

$completed = (int)$conn->query(
    "SELECT COUNT(*) AS n FROM tasks WHERE status = 'Completed'"
)->fetch_assoc()["n"];

$completion_rate = $total > 0 ? round(($completed / $total) * 100) : 0;

$search = trim($_GET["search"] ?? "");
$status = $_GET["status"] ?? "All";

$valid_statuses = ["All", "Pending", "In Progress", "Completed"];

if (!in_array($status, $valid_statuses, true)) {
    $status = "All";
}

$sql = "SELECT * FROM tasks WHERE 1=1";

if ($search !== "") {
    $sql .= " AND (task_name LIKE ? OR description LIKE ?)";
}

if ($status !== "All") {
    $sql .= " AND status = ?";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if ($search !== "" && $status !== "All") {
    $like = "%" . $search . "%";
    $stmt->bind_param("sss", $like, $like, $status);
} elseif ($search !== "") {
    $like = "%" . $search . "%";
    $stmt->bind_param("ss", $like, $like);
} elseif ($status !== "All") {
    $stmt->bind_param("s", $status);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>TodoSystem | Task Management</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="app-layout">

    <aside class="sidebar">
        <a href="index.php" class="brand">
            <span class="brand-icon">✓</span>
            <span>
                <strong>Todo<span>System</span></strong>
                <small>Organize · Focus · Get Things Done</small>
            </span>
        </a>

        <nav class="sidebar-nav">
            <a href="#dashboard" class="nav-link active">
                <span class="nav-icon">⌂</span> Dashboard
            </a>
            <a href="#add-task" class="nav-link">
                <span class="nav-icon">＋</span> Add Task
            </a>
            <a href="#my-tasks" class="nav-link">
                <span class="nav-icon">☷</span> My Tasks
            </a>
        </nav>

        <div class="sidebar-note">
            <div class="plant-icon">✳</div>
            <p>Small steps every day lead to big results.</p>
        </div>

        <div class="sidebar-footer">PERSONAL TASK MANAGER</div>
    </aside>

    <main class="main-content">

        <header class="top-header" id="dashboard">
            <div class="heading">
                <p class="eyebrow">YOUR PERSONAL WORKSPACE</p>
                <h1>Task and To-Do List</h1>
                <p class="subtitle">Manage your daily activities in one place.</p>
            </div>

            <div class="date-panel">
                <div class="date-item">
                    <span class="date-icon">▦</span>
                    <div>
                        <small>Today's Date</small>
                        <strong><?= date("F d, Y") ?></strong>
                    </div>
                </div>

                <div class="date-item">
                    <span class="date-icon">◷</span>
                    <div>
                        <small>Current Time</small>
                        <strong id="clock"><?= date("h:i:s A") ?></strong>
                    </div>
                </div>

                <span class="started"><span class="live-dot"></span> Started</span>
            </div>
        </header>


        <?php if (isset($_GET["message"])): ?>
            <div class="notice success-notice">
                <?php
                $messages = [
                    "added" => "Your new task has been added successfully.",
                    "completed" => "Task marked as completed.",
                    "updated" => "Task updated successfully.",
                    "deleted" => "Task deleted successfully."
                ];
                echo e($messages[$_GET["message"]] ?? "Action completed.");
                ?>
            </div>
        <?php endif; ?>

        <?php if (($_GET["error"] ?? "") === "empty"): ?>
            <div class="notice error-notice">
                Please enter a task name.
            </div>
        <?php endif; ?>

        <section class="stats-grid">
            <article class="stat-card stat-total">
                <div class="stat-top">
                    <span class="stat-icon blue-icon">▤</span>
                    <span class="stat-label">Total Tasks</span>
                </div>
                <strong class="stat-number"><?= $total ?></strong>
                <div class="stat-bottom">
                    <span class="stat-line blue-line"></span>
                    <span class="stat-caption">All your tasks</span>
                </div>
            </article>

            <article class="stat-card stat-pending">
                <div class="stat-top">
                    <span class="stat-icon orange-icon">◷</span>
                    <span class="stat-label">Pending</span>
                </div>
                <strong class="stat-number"><?= $pending ?></strong>
                <div class="stat-bottom">
                    <span class="stat-line orange-line"></span>
                    <span class="stat-caption">Waiting to start</span>
                </div>
            </article>

            <article class="stat-card stat-progress">
                <div class="stat-top">
                    <span class="stat-icon blue-icon">◌</span>
                    <span class="stat-label">In Progress</span>
                </div>
                <strong class="stat-number"><?= $progress ?></strong>
                <div class="stat-bottom">
                    <span class="stat-line blue-line"></span>
                    <span class="stat-caption">Currently working on</span>
                </div>
            </article>

            <article class="stat-card stat-completed">
                <div class="stat-top">
                    <span class="stat-icon green-icon">✓</span>
                    <span class="stat-label">Completed</span>
                </div>
                <strong class="stat-number"><?= $completed ?></strong>
                <div class="stat-bottom">
                    <span class="stat-line green-line"></span>
                    <span class="stat-caption"><?= $completion_rate ?>% completion rate</span>
                </div>
            </article>
        </section>

        <section class="panel" id="add-task">
            <div class="panel-heading">
                <span class="heading-icon">＋</span>
                <div>
                    <h2>Add a New Task</h2>
                    <p>Fill in the details below to add a new task to your list.</p>
                </div>
            </div>

            <div class="panel-body">
                <form method="POST" action="index.php#add-task">
                    <div class="form-grid">
                        <div class="field">
                            <label for="task_name">Task Name <span class="required">*</span></label>
                            <input
                                type="text"
                                id="task_name"
                                name="task_name"
                                maxlength="255"
                                placeholder="Enter your task name"
                                required
                            >
                        </div>

                        <div class="field">
                            <label for="description">Description</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="2"
                                placeholder="Enter task details..."
                            ></textarea>
                        </div>

                        <div class="field">
                            <label for="due_date">Due Date</label>
                            <input type="date" id="due_date" name="due_date">
                        </div>

                        <div class="field">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                    </div>

                    <button class="btn btn-primary add-button" type="submit" name="add_task">
                        <span>＋</span> Add Task
                    </button>
                </form>
            </div>
        </section>

        <section class="panel" id="my-tasks">
            <div class="task-heading">
                <div class="panel-heading task-title-block">
                    <span class="heading-icon light-icon">☷</span>
                    <div>
                        <h2>My Tasks</h2>
                        <p>View and manage your tasks below.</p>
                    </div>
                </div>

                <form method="GET" action="index.php#my-tasks" class="filter-form">
                    <input
                        type="search"
                        name="search"
                        value="<?= e($search) ?>"
                        placeholder="⌕  Search task name or description"
                        aria-label="Search tasks"
                    >

                    <select name="status" aria-label="Filter by status">
                        <?php foreach ($valid_statuses as $option): ?>
                            <option value="<?= e($option) ?>"
                                <?= $status === $option ? "selected" : "" ?>>
                                <?= e($option === "All" ? "All Statuses" : $option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button class="btn btn-dark" type="submit">⌕ Search</button>
                    <a class="btn btn-light" href="index.php#my-tasks">↻ Clear</a>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Task Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Due Date</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($task = $result->fetch_assoc()): ?>
                            <?php
                            $task_status = $task["status"] ?? "Pending";
                            $task_priority = $task["priority"] ?? "Medium";

                            $is_overdue = !empty($task["due_date"])
                                && $task["due_date"] < date("Y-m-d")
                                && $task_status !== "Completed";
                            ?>
                            <tr>
                                <td class="id-cell"><?= (int)$task["id"] ?></td>
                                <td class="task-name-cell"><?= e($task["task_name"]) ?></td>
                                <td class="description-cell"><?= e($task["description"] ?? "") ?></td>

                                <td>
                                    <span class="badge status-<?= strtolower(str_replace(" ", "-", $task_status)) ?>">
                                        <span class="badge-dot"></span>
                                        <?= e($task_status) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge priority-<?= strtolower($task_priority) ?>">
                                        <?= $task_priority === "High" ? "⚑ " : "" ?><?= e($task_priority) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($task["due_date"])): ?>
                                        <span class="due-date"><?= e($task["due_date"]) ?></span>
                                        <?php if ($is_overdue): ?>
                                            <span class="overdue-label">! Overdue</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="muted">No due date</span>
                                    <?php endif; ?>
                                </td>

                                <td class="created-cell"><?= e($task["created_at"]) ?></td>

                                <td class="actions-cell">
                                    <a class="action-btn edit-action"
                                       href="edit.php?id=<?= (int)$task["id"] ?>">✎ Edit</a>

                                    <?php if ($task_status !== "Completed"): ?>
                                        <form method="POST" action="index.php#my-tasks" class="inline-form">
                                            <input type="hidden" name="id" value="<?= (int)$task["id"] ?>">
                                            <button type="submit" name="complete_task"
                                                    class="action-btn complete-action">
                                                ✓ Complete
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" action="delete.php" class="inline-form"
                                          onsubmit="return confirm('Are you sure you want to delete this task?');">
                                        <input type="hidden" name="id" value="<?= (int)$task["id"] ?>">
                                        <button type="submit" class="action-btn delete-action">✕ Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <span>☷</span>
                                <strong>No tasks found</strong>
                                <p>Add a new task or try a different search.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <span>Showing <?= $result->num_rows ?> matching task(s)</span>
                <a href="#dashboard" class="back-top">↑ Back to top</a>
            </div>
        </section>

        <footer class="page-footer">
            <span>Task and To-Do List Management System</span>
            <span class="footer-divider">|</span>
            <span>Built with PHP, MySQL, HTML &amp; CSS</span>
        </footer>

    </main>
</div>

<script>
function updateClock() {
    const clock = document.getElementById("clock");

    if (clock) {
        clock.textContent = new Date().toLocaleTimeString("en-PH", {
            timeZone: "Asia/Manila",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            hour12: true
        });
    }
}

updateClock();
setInterval(updateClock, 1000);

document.querySelectorAll(".sidebar-nav .nav-link").forEach(link => {
    link.addEventListener("click", function () {
        document.querySelectorAll(".sidebar-nav .nav-link")
            .forEach(item => item.classList.remove("active"));

        this.classList.add("active");
    });
});
</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
