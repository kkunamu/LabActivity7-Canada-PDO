<?php
// index.php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$error = '';

// Handle new post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'], $_POST['title'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    
    if (empty($title) || empty($content)) {
        $error = "Title and content cannot be empty.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
        $stmt->execute([$current_user_id, $title, $content]);
        header("Location: index.php"); 
        exit;
    }
}

// Fetch all posts with author emails, sorted by newest first
$stmt = $pdo->query("
    SELECT p.id, p.title, p.content, p.is_edited, p.created_at, p.user_id, u.display_name,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC
");
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>News Feed - Blog Site</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <h2>News Feed</h2>
            <span>Welcome, <?= htmlspecialchars($_SESSION['display_name']) ?> | <a href="logout.php">Logout</a></span>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Create Post Form -->
        <div style="padding: 0 20px;">
            <h3>Create a Post</h3>
            <form method="POST" action="index.php">
                <label>Title</label>
                <input type="text" name="title" required>
                
                <label>Content</label>
                <textarea name="content" rows="4" required></textarea>
                
                <button type="submit">Post</button>
            </form>
        </div>

        <!-- Display Posts -->
        <div class="post-grid">
            <?php foreach ($posts as $post): ?>
                <?php
                    // Truncation logic
                    $limit = 200;
                    $raw_content = $post['content'];
                    $is_long = strlen($raw_content) > $limit;
                    
                    // Slice the string if it's too long
                    $display_content = $is_long ? substr($raw_content, 0, $limit) . '...' : $raw_content;
                ?>
                <div class="post-blurb">
                    <div>
                        <div class="post-header">
                            <h3><?= htmlspecialchars($post['title']) ?></h3>
                            <div class="post-meta">
                                By <strong><?= htmlspecialchars($post['display_name']) ?></strong> on <?= $post['created_at'] ?>
                                <?php if ($post['is_edited']): ?>
                                    <em>(Edited)</em>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="post-content">
                            <?= nl2br(htmlspecialchars($display_content)) ?>
                            
                            <?php if ($is_long): ?>
                                <br><br>
                                <a href="post.php?id=<?= $post['id'] ?>" style="font-weight: bold; font-size: 0.9rem;">[View full post]</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="post-actions">
                        <a href="post.php?id=<?= $post['id'] ?>">Comments (<?= $post['comment_count'] ?>)</a>
                        <?php if ($post['user_id'] == $current_user_id): ?>
                            | <a href="edit_post.php?id=<?= $post['id'] ?>">Edit</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
</body>
</html>