<?php
session_start();

if(!isset($_SESSION['user_id'])){
    session_destroy();
    header('Location: login.php');
    exit;
}

require '../database/database.php';
$pdo = Database::connect();

// Fetch all persons
$persons = $pdo->query("SELECT * FROM iss_persons ORDER BY lname ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle Add, Edit, Delete, and Read Person Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $pdo = Database::connect();
    
    if ($_POST['action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO iss_persons (fname, lname, mobile, email, pwd_hash, pwd_salt, admin) VALUES (?, ?, ?, ?, '', '', ?)");
        $stmt->execute([$_POST['fname'], $_POST['lname'], $_POST['mobile'], $_POST['email'], $_POST['admin']]);
        echo "success";
        exit;
    }
    
    if ($_POST['action'] === 'edit') {
        $stmt = $pdo->prepare("UPDATE iss_persons SET fname = ?, lname = ?, mobile = ?, email = ?, admin = ? WHERE id = ?");
        $stmt->execute([$_POST['fname'], $_POST['lname'], $_POST['mobile'], $_POST['email'], $_POST['admin'], $_POST['id']]);
        echo "success";
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM iss_persons WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        echo "success";
        exit;
    }
    
    if ($_POST['action'] === 'read') {
        $stmt = $pdo->prepare("SELECT * FROM iss_persons WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        exit;
    }
    
    Database::disconnect();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persons List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Persons List</h1>
        <a href="logout.php" class="btn btn-warning mb-3">Logout</a>
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addPersonModal">+ Add New Person</button>
        <a href="issues_list.php" class="btn btn-secondary mb-3">Go To Issues List</a>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Admin</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="personTable">
                <?php foreach ($persons as $person): ?>
                    <tr data-id="<?= $person['id']; ?>">
                        <td><?= $person['id']; ?></td>
                        <td><?= htmlspecialchars($person['fname']); ?></td>
                        <td><?= htmlspecialchars($person['lname']); ?></td>
                        <td><?= $person['admin'] ? 'Yes' : 'No'; ?></td>
                        <td>
                            <button class="btn btn-info btn-sm read-btn">Read</button>
                            <?php if($_SESSION['user_id'] == $person['id'] || $_SESSION['admin'] == "Y"){?>
                            <button class="btn btn-warning btn-sm edit-btn">Edit</button>
                            <button class="btn btn-danger btn-sm delete-btn">Delete</button>
                            <?php } ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Read Person Modal -->
    <div class="modal fade" id="readPersonModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Person Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="personDetails"></div>
            </div>
        </div>
    </div>

    <!-- Edit Person Modal -->
    <div class="modal fade" id="editPersonModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Person</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editPersonForm">
                        <input type="hidden" id="editId" name="id">
                        <div class="mb-3">
                            <label for="editFname" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="editFname" name="fname">
                        </div>
                        <div class="mb-3">
                            <label for="editLname" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="editLname" name="lname">
                        </div>
                        <div class="mb-3">
                            <label for="editMobile" class="form-label">Mobile</label>
                            <input type="text" class="form-control" id="editMobile" name="mobile">
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="editAdmin" class="form-label">Admin</label>
                            <select class="form-select" id="editAdmin" name="admin">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deletePersonModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this person?</p>
                    <p><strong>ID:</strong> <span id="deletePersonIdText"></span></p>
                    <p><strong>Full Name:</strong> <span id="deletePersonName"></span></p>
                    <input type="hidden" id="deletePersonId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeletePerson">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Person Modal -->
<div class="modal fade" id="addPersonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Person</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPersonForm">
                    <div class="mb-3">
                        <label for="addFname" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="addFname" name="fname" required>
                    </div>
                    <div class="mb-3">
                        <label for="addLname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="addLname" name="lname" required>
                    </div>
                    <div class="mb-3">
                        <label for="addMobile" class="form-label">Mobile</label>
                        <input type="text" class="form-control" id="addMobile" name="mobile">
                    </div>
                    <div class="mb-3">
                        <label for="addEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="addEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="addAdmin" class="form-label">Admin</label>
                        <select class="form-select" id="addAdmin" name="admin">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Person</button>
                </form>
            </div>
        </div>
    </div>
</div>




    
    <script>
    $(document).ready(function () {
        // Read Person
        $(document).on("click", ".read-btn", function () {
            let id = $(this).closest("tr").data("id");
            $.post("persons_list.php", { action: "read", id: id }, function (response) {
                let person = JSON.parse(response);
                let details = `<p><strong>First Name:</strong> ${person.fname}</p>
                               <p><strong>Last Name:</strong> ${person.lname}</p>
                               <p><strong>Mobile:</strong> ${person.mobile}</p>
                               <p><strong>Email:</strong> ${person.email}</p>
                               <p><strong>Admin:</strong> ${person.admin ? 'Yes' : 'No'}</p>`;
                $("#personDetails").html(details);
                $("#readPersonModal").modal("show");
            });
        });
        
        // Delete Person
        $(document).on("click", ".delete-btn", function () {
    let row = $(this).closest("tr");
    let id = row.data("id");
    let fullName = row.find("td:nth-child(2)").text() + " " + row.find("td:nth-child(3)").text(); // First and Last Name
    $("#deletePersonId").val(id);
    $("#deletePersonIdText").text(id);
    $("#deletePersonName").text(fullName);
    $("#deletePersonModal").modal("show");
});

$("#confirmDeletePerson").on("click", function () {
    let id = $("#deletePersonId").val();
    $.post("persons_list.php", { action: "delete", id: id }, function (response) {
        if (response.trim() === "success") {
            location.reload(); // Reload page after successful deletion
        } else {
            alert("Failed to delete the person.");
        }
    });
});

        
        // Edit Person
        $(document).on("click", ".edit-btn", function () {
            let id = $(this).closest("tr").data("id");
            $.post("persons_list.php", { action: "read", id: id }, function (response) {
                let person = JSON.parse(response);
                $("#editId").val(person.id);
                $("#editFname").val(person.fname);
                $("#editLname").val(person.lname);
                $("#editMobile").val(person.mobile);
                $("#editEmail").val(person.email);
                $("#editAdmin").val(person.admin);
                $("#editPersonModal").modal("show");
            });
        });
    });

    // Add Person
$("#addPersonForm").on("submit", function (e) {
    e.preventDefault();
    let formData = $(this).serialize() + '&action=add';
    $.post("persons_list.php", formData, function (response) {
        if (response.trim() === "success") {
            $("#addPersonModal").modal("hide");
            location.reload();
        } else {
            alert("Failed to add person.");
        }
    });
});

    </script>
</body>
</html>
