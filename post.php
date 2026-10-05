<?php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$post_id = $_GET['id'] ?? null;

if (!$post_id) {
    header("Location: index.php");
    exit;
}

// Handle new comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = trim($_POST['content']);
    if (!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $current_user_id, $content]);
        header("Location: post.php?id=" . $post_id);
        exit;
    }
}

// Fetch the parent post
$postStmt = $pdo->prepare("
    SELECT p.*, u.display_name 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ?
");
$postStmt->execute([$post_id]);
$post = $postStmt->fetch();

if (!$post) {
    die("Post not found.");
}

// Fetch comments
$commentStmt = $pdo->prepare("
    SELECT c.*, u.display_name 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.post_id = ? 
    ORDER BY c.created_at ASC
");
$commentStmt->execute([$post_id]);
$comments = $commentStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['title']) ?> - Blog Site</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <h2>Thread</h2>
            <a href="index.php">Back to Feed</a>
        </div>

        <!-- Main Post -->
        <div class="post-blurb" style="border-width: 2px;">
            <div class="post-header">
                <h3 style="font-size: 1.5rem;"><?= htmlspecialchars($post['title']) ?></h3>
                <div class="post-meta">
                    Posted by <strong><?= htmlspecialchars($post['display_name']) ?></strong> on <?= $post['created_at'] ?>
                    <?php if ($post['is_edited']): ?>
                        <em>(Edited)</em>
                    <?php endif; ?>
                </div>
            </div>
            <div class="post-content">
                <?= nl2br(htmlspecialchars($post['content'])) ?>
            </div>
        </div>

        <h3>Comments</h3>
        
        <!-- Comments List -->
        <?php foreach ($comments as $comment): ?>
            <div class="post-blurb" style="margin-left: 40px;">
                <div class="post-header">
                    <div class="post-meta">
                        <strong><?= htmlspecialchars($comment['display_name']) ?></strong> replied on <?= $comment['created_at'] ?>
                        <?php if ($comment['is_edited']): ?>
                            <em>(Edited)</em>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="post-content">
                    <?= nl2br(htmlspecialchars($comment['content'])) ?>
                </div>
                
                <?php if ($comment['user_id'] == $current_user_id): ?>
                <div class="post-actions">
                    <a href="edit_comment.php?id=<?= $comment['id'] ?>">Edit Comment</a>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Add Comment Form -->
        <div style="padding: 0 20px; margin-top: 30px;">
            <h4>Add a Comment</h4>
            <form method="POST" action="post.php?id=<?= $post['id'] ?>">
                <textarea name="content" rows="4" required></textarea>
                <button type="submit">Submit Comment</button>
            </form>
        </div>
    </div>
</body>
</html>