<?php
session_start();

if(!isset($_SESSION['user_id'])){
    session_destroy();
    header('Location: login.php');
    exit;
}

require '../database/database.php';
$pdo = Database::connect();

$issueId = isset($_GET['issue_id']) ? (int)$_GET['issue_id'] : 0;
if ($issueId <= 0) {
    die("Invalid issue ID.");
}

// Fetch all issues for dropdown in Add and Edit modals
$issues = $pdo->query("SELECT id, short_description FROM iss_issues ORDER BY short_description")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all persons for dropdowns in Add and Edit modals
$people = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) AS full_name FROM iss_persons ORDER BY lname")->fetchAll(PDO::FETCH_ASSOC);

// Handle Add, Edit, and Delete Comment Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO iss_comments (per_id, iss_id, short_comment, long_comment, posted_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['per_id'], $_POST['iss_id'], $_POST['short_comment'], $_POST['long_comment'], date('Y-m-d')]);
        echo "success";
        exit;
    }

    if ($_POST['action'] === 'edit') {
        $stmt = $pdo->prepare("UPDATE iss_comments SET short_comment = ?, long_comment = ?, posted_date = ? WHERE id = ?");
        $stmt->execute([$_POST['short_comment'], $_POST['long_comment'], date('Y-m-d'), $_POST['id']]);
        echo "success";
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM iss_comments WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        echo "success";
        exit;
    }
}

// Fetch all comments
$stmt = $pdo->prepare("SELECT c.id, c.per_id, c.short_comment, c.long_comment, c.posted_date, p.fname, p.lname, i.short_description AS issue_desc 
                       FROM iss_comments c 
                       JOIN iss_persons p ON c.per_id = p.id 
                       JOIN iss_issues i ON c.iss_id = i.id 
                       WHERE c.iss_id = ? 
                       ORDER BY c.posted_date DESC");
$stmt->execute([$issueId]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);


Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Comments for Issue #<?= $issueId; ?></h1>
        <a href="logout.php" class="btn btn-warning mb-3">Logout</a>
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addCommentModal">+ Add Comment</button>
        <a href="issues_list.php" class="btn btn-secondary mb-3">Go Back to Issues</a>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Short Comment</th>
                    <th>Posted By</th>
                    <th>Issue</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment): ?>
                    <tr data-id="<?= $comment['id']; ?>">
                        <td><?= $comment['id']; ?></td>
                        <td class="short-comment"><?= htmlspecialchars($comment['short_comment']); ?></td>
                        <td><?= $comment['fname'] . ' ' . $comment['lname']; ?></td>
                        <td><?= $comment['issue_desc']; ?></td>
                        <td><?= $comment['posted_date']; ?></td>
                        <td>
                            <button class="btn btn-info btn-sm read-btn" data-comment='<?= json_encode($comment); ?>'>Read</button>
                            <?php if ($_SESSION['admin'] === "Y" || $_SESSION['user_id'] == $comment['per_id']) { ?>
                            <button class="btn btn-warning btn-sm edit-btn" data-comment='<?= json_encode($comment); ?>'>Edit</button>
                            <button class="btn btn-danger btn-sm delete-btn" data-comment='<?= json_encode($comment); ?>'>Delete</button>
                            <?php } ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Comment Modal -->
    <div class="modal fade" id="addCommentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Comment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCommentForm">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label>Short Comment</label>
                            <input type="text" class="form-control" name="short_comment" required>
                        </div>
                        <div class="mb-3">
                            <label>Long Comment</label>
                            <textarea class="form-control" name="long_comment" required></textarea>
                        </div>
                        <input type="hidden" name="iss_id" value="<?= $issueId; ?>">
                        <div class="mb-3">
                            <label>Person</label>
                            <select class="form-control" name="per_id" required>
                                <option value="">Select Person</option>
                                <?php foreach ($people as $person): ?>
                                    <option value="<?= $person['id']; ?>"><?= htmlspecialchars($person['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Comment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Read Comment Modal -->
    <div class="modal fade" id="readCommentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Read Comment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Short Comment:</strong> <span id="readShortComment"></span></p>
                    <p><strong>Long Comment:</strong> <span id="readLongComment"></span></p>
                    <p><strong>Posted By:</strong> <span id="readPersonName"></span></p>
                    <p><strong>Issue:</strong> <span id="readIssueDesc"></span></p>
                    <p><strong>Posted Date:</strong> <span id="readPostedDate"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Comment Modal -->
    <div class="modal fade" id="editCommentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Comment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editCommentForm">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id">
                        <div class="mb-3">
                            <label>Short Comment</label>
                            <input type="text" class="form-control" name="short_comment" required>
                        </div>
                        <div class="mb-3">
                            <label>Long Comment</label>
                            <textarea class="form-control" name="long_comment" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCommentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this comment?</p>
                    <p><strong>ID:</strong> <span id="deleteCommentIdText"></span></p>
                    <p><strong>Short Comment:</strong> <span id="deleteCommentShortText"></span></p>
                    <input type="hidden" id="deleteCommentId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteComment">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function () {
        // Add Comment
        $("#addCommentForm").submit(function (event) {
            event.preventDefault();
            $.post("comments_list.php", $(this).serialize(), function (response) {
                if (response.trim() === "success") location.reload();
            });
        });

        // Edit Comment
        $(document).on("click", ".edit-btn", function () {
            let comment = $(this).data("comment");
            $("#editCommentModal input[name='id']").val(comment.id);
            $("#editCommentModal input[name='short_comment']").val(comment.short_comment);
            $("#editCommentModal textarea[name='long_comment']").val(comment.long_comment);
            $("#editCommentModal").modal("show");
        });

        // Save Edit Comment
        $("#editCommentForm").submit(function (event) {
            event.preventDefault();
            $.post("comments_list.php", $(this).serialize(), function (response) {
                if (response.trim() === "success") location.reload();
            });
        });

        // Delete Comment
        $(document).on("click", ".delete-btn", function () {
            let comment = $(this).data("comment");
            $("#deleteCommentIdText").text(comment.id);
            $("#deleteCommentShortText").text(comment.short_comment);
            $("#deleteCommentId").val(comment.id);
            $("#deleteCommentModal").modal("show");
        });

        // Confirm Delete
        $("#confirmDeleteComment").click(function () {
            $.post("comments_list.php", { action: "delete", id: $("#deleteCommentId").val() }, function (response) {
                if (response.trim() === "success") location.reload();
            });
        });

        // Read Comment
        $(document).on("click", ".read-btn", function () {
            let comment = $(this).data("comment");
            $("#readShortComment").text(comment.short_comment);
            $("#readLongComment").text(comment.long_comment);
            $("#readPersonName").text(comment.fname + ' ' + comment.lname);
            $("#readIssueDesc").text(comment.issue_desc);
            $("#readPostedDate").text(comment.posted_date);
            $("#readCommentModal").modal("show");
        });
    });
    </script>
</body>
</html>
