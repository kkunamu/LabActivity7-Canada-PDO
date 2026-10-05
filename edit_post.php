<?php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$post_id = $_GET['id'] ?? null;
if (!$post_id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
$stmt->execute([$post_id, $_SESSION['user_id']]);
$post = $stmt->fetch();

if (!$post) {
    die("You do not have permission to modify this post.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $deleteStmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $deleteStmt->execute([$post_id]);
        header("Location: index.php");
        exit;
    } elseif (isset($_POST['content'], $_POST['title'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        if (!empty($title) && !empty($content)) {
            $update = $pdo->prepare("UPDATE posts SET title = ?, content = ?, is_edited = TRUE WHERE id = ?");
            $update->execute([$title, $content, $post_id]);
            header("Location: index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Post</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <h2>Edit Post</h2>
            <a href="index.php">Cancel</a>
        </div>
        
        <div style="padding: 20px;">
            <form method="POST" action="edit_post.php?id=<?= $post['id'] ?>">
                <label>Title:</label>
                <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" required>
                
                <label>Content:</label>
                <textarea name="content" rows="8" required><?= htmlspecialchars($post['content']) ?></textarea>
                
                <button type="submit">Save Changes</button>
            </form>

            <form method="POST" action="edit_post.php?id=<?= $post['id'] ?>" onsubmit="return confirm('Are you sure you want to delete this post? This action cannot be undone.');" style="background: none; border: none; padding: 0; margin-top: 40px;">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="delete-btn">Delete Post</button>
            </form>
        </div>
    </div>
</body>
</html>