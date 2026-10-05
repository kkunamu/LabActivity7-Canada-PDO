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

// Handle new comment submission from the feed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'], $_POST['post_id'])) {
    $comment_content = trim($_POST['comment_content']);
    $comment_post_id = $_POST['post_id'];
    
    if (!empty($comment_content)) {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$comment_post_id, $current_user_id, $comment_content]);
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
    <title>Ugh Thoughts - Blog Site</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <h2>Tho(ugh)ts</h2>
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
                    $limit = 350;
                    $raw_content = $post['content'];
                    $is_long = strlen($raw_content) > $limit;
                    $display_content = $is_long ? substr($raw_content, 0, $limit) . '...' : $raw_content;
                ?>
                <div class="post-blurb">
                    <!-- Post Content Area (Grows to push footer down) -->
                    <div style="flex-grow: 1;">
                        <div class="post-header">
                            <h3 style="margin-top: 0;"><?= htmlspecialchars($post['title']) ?></h3>
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

                    <!-- Card Footer: Actions + Compact Comment Box -->
                    <div style="margin-top: 15px;">
                        <div class="post-actions" style="margin-top: 0; border-top: 1px dashed #dddddd; padding-top: 10px; padding-bottom: 10px;">
                            <a href="post.php?id=<?= $post['id'] ?>">Comments (<?= $post['comment_count'] ?>)</a>
                            <?php if ($post['user_id'] == $current_user_id): ?>
                                | <a href="edit_post.php?id=<?= $post['id'] ?>">Edit</a>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" action="index.php" style="padding: 0; margin: 0; border: none; background: transparent; display: flex; gap: 5px;">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <input type="text" name="comment_content" placeholder="Add a comment..." required style="margin-bottom: 0; flex-grow: 1; padding: 6px; font-size: 0.85rem;">
                            <button type="submit" style="font-size: 0.8rem; padding: 4px 10px; margin: 0;">Reply</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
</body>
</html>