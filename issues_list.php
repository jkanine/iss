<?php
require '../database/database.php';
$pdo = Database::connect();

// Fetch all persons (ID, Full Name)
$people = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) AS full_name FROM iss_persons ORDER BY lname ASC")->fetchAll(PDO::FETCH_ASSOC);

Database::disconnect();

// Handle Add, Edit, and Delete Issue Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO iss_issues (short_description, long_description, open_date, close_date, priority, org, project, per_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['short_description'], $_POST['long_description'], $_POST['open_date'], $_POST['close_date'], $_POST['priority'], $_POST['org'], $_POST['project'], $_POST['per_id']]);
        echo "success";
        exit;
    }
    
    if ($_POST['action'] === 'edit') {
        $stmt = $pdo->prepare("UPDATE iss_issues SET short_description = ?, long_description = ?, open_date = ?, close_date = ?, priority = ?, org = ?, project = ?, per_id = ? WHERE id = ?");
        $stmt->execute([$_POST['short_description'], $_POST['long_description'], $_POST['open_date'], $_POST['close_date'], $_POST['priority'], $_POST['org'], $_POST['project'], $_POST['per_id'], $_POST['id']]);
        echo "success";
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM iss_issues WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        echo "success";
        exit;
    }
}

// Fetch all issues
$issues = $pdo->query("SELECT * FROM iss_issues ORDER BY open_date DESC")->fetchAll(PDO::FETCH_ASSOC);
Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issues List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Issue List</h1>
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addIssueModal">+ Add Issue</button>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Short Description</th>
                    <th>Open Date</th>
                    <th>Close Date</th>
                    <th>Priority</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="issueTable">
                <?php foreach ($issues as $issue): ?>
                    <tr data-id="<?= $issue['id']; ?>">
                        <td><?= $issue['id']; ?></td>
                        <td class="short-description"><?= htmlspecialchars($issue['short_description']); ?></td>
                        <td><?= $issue['open_date']; ?></td>
                        <td><?= $issue['close_date']; ?></td>
                        <td><?= $issue['priority']; ?></td>
                        <td>
                            <button class="btn btn-info btn-sm read-btn" data-issue='<?= json_encode($issue); ?>'>Read</button>
                            <button class="btn btn-warning btn-sm edit-btn">Edit</button>
                            <button class="btn btn-danger btn-sm delete-btn">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Issue Modal -->
    <div class="modal fade" id="addIssueModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addIssueForm">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3"><label>Short Description</label><input type="text" class="form-control" name="short_description" required></div>
                        <div class="mb-3"><label>Long Description</label><textarea class="form-control" name="long_description" required></textarea></div>
                        <div class="mb-3"><label>Open Date</label><input type="date" class="form-control" name="open_date" required></div>
                        <div class="mb-3"><label>Close Date</label><input type="date" class="form-control" name="close_date"></div>
                        <div class="mb-3"><label>Priority</label><input type="text" class="form-control" name="priority" required></div>
                        <div class="mb-3"><label>Organization</label><input type="text" class="form-control" name="org" required></div>
                        <div class="mb-3"><label>Project</label><input type="text" class="form-control" name="project" required></div>
                        <div class="mb-3">
    <label>Person</label>
    <select class="form-control" name="per_id" required>
        <option value="">Select Person</option>
        <?php foreach ($people as $person): ?>
            <option value="<?= $person['id']; ?>"><?= htmlspecialchars($person['full_name']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
                        <button type="submit" class="btn btn-primary">Add Issue</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Read Issue Modal -->
    <div class="modal fade" id="readIssueModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Issue Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="readIssueContent"></div>
            </div>
        </div>
    </div>

        <!-- Edit Issue Modal -->
    <div class="modal fade" id="editIssueModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editIssueForm">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id">
                        <div class="mb-3"><label>Short Description</label><input type="text" class="form-control" name="short_description" required></div>
                        <div class="mb-3"><label>Long Description</label><textarea class="form-control" name="long_description" required></textarea></div>
                        <div class="mb-3"><label>Open Date</label><input type="date" class="form-control" name="open_date" required></div>
                        <div class="mb-3"><label>Close Date</label><input type="date" class="form-control" name="close_date"></div>
                        <div class="mb-3"><label>Priority</label><input type="text" class="form-control" name="priority" required></div>
                        <div class="mb-3"><label>Organization</label><input type="text" class="form-control" name="org" required></div>
                        <div class="mb-3"><label>Project</label><input type="text" class="form-control" name="project" required></div>
                        <div class="mb-3">
    <label>Person</label>
    <select class="form-control" name="per_id" required>
        <option value="">Select Person</option>
        <?php foreach ($people as $person): ?>
            <option value="<?= $person['id']; ?>"><?= htmlspecialchars($person['full_name']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

        <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteIssueModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this issue?</p>
                    <input type="hidden" id="deleteIssueId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>




    <script>
    $(document).ready(function () {
        // Add Issue
        $("#addIssueForm").submit(function (event) {
            event.preventDefault();
            $.post("issues_list.php", $(this).serialize(), function (response) {
                if (response.trim() === "success") location.reload();
            });
        });

        // Read Issue
        $(document).on("click", ".read-btn", function () {
            let issue = $(this).data("issue");
            $("#readIssueContent").html(`
                <p><strong>ID:</strong> ${issue.id}</p>
                <p><strong>Short Description:</strong> ${issue.short_description}</p>
                <p><strong>Long Description:</strong> ${issue.long_description}</p>
                <p><strong>Open Date:</strong> ${issue.open_date}</p>
                <p><strong>Close Date:</strong> ${issue.close_date}</p>
                <p><strong>Priority:</strong> ${issue.priority}</p>
                <p><strong>Organization:</strong> ${issue.org}</p>
                <p><strong>Project:</strong> ${issue.project}</p>
                <p><strong>Person ID:</strong> ${issue.per_id}</p>
            `);
            $("#readIssueModal").modal("show");
        });

        // Edit Issue
        $(document).on("click", ".edit-btn", function () {
    let row = $(this).closest("tr");
    let issue = JSON.parse(row.find(".read-btn").attr("data-issue"));

    $("#editIssueForm input[name='id']").val(issue.id);
    $("#editIssueForm input[name='short_description']").val(issue.short_description);
    $("#editIssueForm textarea[name='long_description']").val(issue.long_description);
    $("#editIssueForm input[name='open_date']").val(issue.open_date);
    $("#editIssueForm input[name='close_date']").val(issue.close_date);
    $("#editIssueForm input[name='priority']").val(issue.priority);
    $("#editIssueForm input[name='org']").val(issue.org);
    $("#editIssueForm input[name='project']").val(issue.project);
    
    // Select the correct person in the dropdown
    $("#editIssueForm select[name='per_id']").val(issue.per_id);

    $("#editIssueModal").modal("show");
});

        $("#editIssueForm").submit(function (event) {
            event.preventDefault();
            $.post("issues_list.php", $(this).serialize(), function (response) {
                if (response.trim() === "success") location.reload();
            });
        });

        // Delete Issue
        $(document).on("click", ".delete-btn", function () {
            if (!confirm("Are you sure you want to delete this issue?")) return;
            let issueId = $(this).closest("tr").data("id");
            $.post("issues_list.php", { action: "delete", id: issueId }, function (response) {
                if (response.trim() === "success") location.reload();
            });
        });
    });
</script>
</body>
</html>
