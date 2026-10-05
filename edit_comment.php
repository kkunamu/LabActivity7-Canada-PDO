<?php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$comment_id = $_GET['id'] ?? null;
if (!$comment_id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ? AND user_id = ?");
$stmt->execute([$comment_id, $_SESSION['user_id']]);
$comment = $stmt->fetch();

if (!$comment) {
    die("You do not have permission to modify this comment.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the user clicked the Delete button
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $deleteStmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $deleteStmt->execute([$comment_id]);
        header("Location: post.php?id=" . $comment['post_id']);
        exit;
    } 
    // Otherwise, process the standard Edit form
    elseif (isset($_POST['content'])) {
        $content = trim($_POST['content']);
        if (!empty($content)) {
            $update = $pdo->prepare("UPDATE comments SET content = ?, is_edited = TRUE WHERE id = ?");
            $update->execute([$content, $comment_id]);
            header("Location: post.php?id=" . $comment['post_id']);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Comment</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <h2>Edit Comment</h2>
            <a href="post.php?id=<?= $comment['post_id'] ?>">Cancel</a>
        </div>

        <div style="padding: 20px;">
            <!-- Edit Form -->
            <form method="POST" action="edit_comment.php?id=<?= $comment['id'] ?>">
                <label>Comment Content:</label>
                <textarea name="content" rows="6" required><?= htmlspecialchars($comment['content']) ?></textarea>
                
                <button type="submit">Save Changes</button>
            </form>

            <!-- Delete Form -->
            <form method="POST" action="edit_comment.php?id=<?= $comment['id'] ?>" onsubmit="return confirm('Are you sure you want to delete this comment? This action cannot be undone.');" style="background: none; border: none; padding: 0; margin-top: 40px;">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="delete-btn">Delete Comment</button>
            </form>
        </div>
    </div>
</body>
</html>