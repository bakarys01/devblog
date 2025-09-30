<?php
require_once 'db-config.php';

$action = $_GET['action'] ?? 'home';
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$message = '';

function calculateReadTime($text) {
    $word_count = str_word_count(strip_tags($text));
    $reading_time = ceil($word_count / 200); // Average reading speed 200 words per minute
    return max(1, $reading_time);
}

function createExcerpt($text, $length = 200) {
    $text = strip_tags($text);
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, strrpos(substr($text, 0, $length), ' ')) . '...';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $author = $_POST['author'] ?? '';
        $category = $_POST['category'] ?? 'General';
        $tags = $_POST['tags'] ?? '';
        $featured_image = $_POST['featured_image'] ?? '';
        
        if ($title && $content && $author) {
            $excerpt = createExcerpt($content, 200);
            $read_time = calculateReadTime($content);
            
            $stmt = $pdo->prepare("INSERT INTO articles (title, content, excerpt, author, category, tags, read_time, featured_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $content, $excerpt, $author, $category, $tags, $read_time, $featured_image])) {
                $message = "Article published successfully!";
                $action = 'home';
            } else {
                $message = "Error creating article.";
            }
        } else {
            $message = "Please fill in all required fields.";
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'] ?? '';
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $author = $_POST['author'] ?? '';
        $category = $_POST['category'] ?? 'General';
        $tags = $_POST['tags'] ?? '';
        $featured_image = $_POST['featured_image'] ?? '';
        
        if ($id && $title && $content && $author) {
            $excerpt = createExcerpt($content, 200);
            $read_time = calculateReadTime($content);
            
            $stmt = $pdo->prepare("UPDATE articles SET title = ?, content = ?, excerpt = ?, author = ?, category = ?, tags = ?, read_time = ?, featured_image = ? WHERE id = ?");
            if ($stmt->execute([$title, $content, $excerpt, $author, $category, $tags, $read_time, $featured_image, $id])) {
                $message = "Article updated successfully!";
                $action = 'home';
            } else {
                $message = "Error updating article.";
            }
        } else {
            $message = "Please fill in all required fields.";
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = "Article deleted successfully!";
            } else {
                $message = "Error deleting article.";
            }
        }
        $action = 'home';
    } elseif ($action === 'like') {
        $id = $_POST['id'] ?? '';
        if ($id) {
            $stmt = $pdo->prepare("UPDATE articles SET likes = likes + 1 WHERE id = ?");
            $stmt->execute([$id]);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
    }
}

if ($action === 'read') {
    $id = $_GET['id'] ?? '';
    if ($id) {
        // Increment view count
        $stmt = $pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        // Get article
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        $article = $stmt->fetch();
    }
} elseif ($action === 'edit') {
    $id = $_GET['id'] ?? '';
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        $article = $stmt->fetch();
    }
}

// Get categories for filter
$categories_stmt = $pdo->query("SELECT DISTINCT category FROM articles ORDER BY category");
$categories = $categories_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevBlog - Stories for Developers</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            background: #ffffff;
        }
        
        .header {
            border-bottom: 1px solid #e6e6e6;
            padding: 16px 0;
            position: sticky;
            top: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 100;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .logo i {
            color: #059669;
        }
        
        .nav-buttons {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .btn {
            padding: 8px 16px;
            border: 1px solid #e6e6e6;
            background: #ffffff;
            color: #1a1a1a;
            text-decoration: none;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }
        
        .btn-primary {
            background: #059669;
            color: white;
            border-color: #059669;
        }
        
        .btn-primary:hover {
            background: #047857;
            border-color: #047857;
        }
        
        .search-bar {
            display: flex;
            align-items: center;
            background: #f3f4f6;
            border-radius: 20px;
            padding: 8px 16px;
            margin: 0 20px;
            flex: 1;
            max-width: 400px;
        }
        
        .search-bar input {
            border: none;
            background: transparent;
            outline: none;
            font-size: 14px;
            width: 100%;
            padding: 4px;
        }
        
        .search-bar i {
            color: #6b7280;
            margin-right: 8px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .hero {
            text-align: center;
            padding: 60px 0;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        
        .hero h1 {
            font-size: 48px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 16px;
        }
        
        .hero p {
            font-size: 20px;
            color: #6b7280;
            margin-bottom: 32px;
        }
        
        .filters {
            padding: 32px 0;
            border-bottom: 1px solid #e6e6e6;
        }
        
        .filter-tabs {
            display: flex;
            gap: 24px;
            overflow-x: auto;
            padding-bottom: 8px;
        }
        
        .filter-tab {
            padding: 8px 16px;
            background: transparent;
            border: none;
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            transition: color 0.2s ease;
            text-decoration: none;
        }
        
        .filter-tab.active, .filter-tab:hover {
            color: #059669;
            border-bottom: 2px solid #059669;
        }
        
        .articles-grid {
            padding: 32px 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 32px;
        }
        
        .article-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
            border: 1px solid #f3f4f6;
        }
        
        .article-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .article-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        
        .article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .article-content {
            padding: 24px;
        }
        
        .article-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 12px;
            color: #6b7280;
        }
        
        .category-tag {
            background: #059669;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .article-title {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .article-excerpt {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 16px;
        }
        
        .article-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid #f3f4f6;
        }
        
        .author-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .author-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
        }
        
        .article-stats {
            display: flex;
            gap: 16px;
            font-size: 14px;
            color: #6b7280;
        }
        
        .stat {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .like-btn {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
            transition: color 0.2s ease;
        }
        
        .like-btn:hover {
            color: #ef4444;
        }
        
        .like-btn.liked {
            color: #ef4444;
        }
        
        .article-view {
            max-width: 800px;
            margin: 0 auto;
            padding: 32px 20px;
        }
        
        .article-header {
            text-align: center;
            margin-bottom: 48px;
        }
        
        .article-header h1 {
            font-size: 42px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 24px;
            color: #1a1a1a;
        }
        
        .article-meta-detailed {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 24px;
            margin-bottom: 32px;
            font-size: 14px;
            color: #6b7280;
        }
        
        .article-body {
            font-size: 18px;
            line-height: 1.8;
            color: #374151;
        }
        
        .article-body p {
            margin-bottom: 24px;
        }
        
        .share-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin: 48px 0;
            padding: 24px 0;
            border-top: 1px solid #e6e6e6;
            border-bottom: 1px solid #e6e6e6;
        }
        
        .share-btn {
            padding: 12px 16px;
            border: 1px solid #e6e6e6;
            background: white;
            color: #6b7280;
            text-decoration: none;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .share-btn:hover {
            background: #f3f4f6;
            color: #1a1a1a;
        }
        
        .form-container {
            max-width: 800px;
            margin: 32px auto;
            padding: 0 20px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }
        
        .form-group textarea {
            height: 300px;
            resize: vertical;
            font-family: inherit;
        }
        
        .tags-input {
            font-size: 14px;
        }
        
        .tags-help {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .message {
            padding: 16px;
            margin: 24px 0;
            border-radius: 8px;
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #059669;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 24px;
        }
        
        .back-link:hover {
            color: #047857;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 16px;
            }
            
            .search-bar {
                margin: 0;
                max-width: none;
            }
            
            .hero h1 {
                font-size: 32px;
            }
            
            .hero p {
                font-size: 16px;
            }
            
            .articles-grid {
                grid-template-columns: 1fr;
            }
            
            .article-header h1 {
                font-size: 28px;
            }
            
            .article-meta-detailed {
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="?action=home" class="logo">
                <i class="fas fa-code"></i>
                DevBlog
            </a>
            
            <form method="GET" class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search articles..." value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="action" value="home">
            </form>
            
            <div class="nav-buttons">
                <a href="?action=home" class="btn">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="?action=create" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Write
                </a>
            </div>
        </div>
    </header>

    <?php if ($message): ?>
        <div class="container">
            <div class="message"><?= htmlspecialchars($message) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($action === 'home'): ?>
        <section class="hero">
            <div class="container">
                <h1>Stories for Developers</h1>
                <p>Discover insights, tutorials, and thoughts from the developer community</p>
            </div>
        </section>

        <div class="container">
            <div class="filters">
                <div class="filter-tabs">
                    <a href="?action=home" class="filter-tab <?= empty($category) ? 'active' : '' ?>">All Stories</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="?action=home&category=<?= urlencode($cat['category']) ?>" 
                           class="filter-tab <?= $category === $cat['category'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat['category']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="articles-grid">
                <?php
                $where_clauses = [];
                $params = [];
                
                if ($search) {
                    $where_clauses[] = "(title LIKE ? OR content LIKE ? OR author LIKE ?)";
                    $search_param = "%$search%";
                    $params = array_merge($params, [$search_param, $search_param, $search_param]);
                }
                
                if ($category) {
                    $where_clauses[] = "category = ?";
                    $params[] = $category;
                }
                
                $where_sql = empty($where_clauses) ? "" : "WHERE " . implode(" AND ", $where_clauses);
                
                $stmt = $pdo->prepare("SELECT * FROM articles $where_sql ORDER BY created_at DESC");
                $stmt->execute($params);
                $articles = $stmt->fetchAll();
                
                if (empty($articles)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 0; color: #6b7280;">
                        <i class="fas fa-search" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                        <h3>No articles found</h3>
                        <p>Try adjusting your search or browse all categories</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($articles as $article): ?>
                        <article class="article-card" onclick="location.href='?action=read&id=<?= $article['id'] ?>'">
                            <div class="article-image">
                                <?php if ($article['featured_image']): ?>
                                    <img src="<?= htmlspecialchars($article['featured_image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                                <?php endif; ?>
                            </div>
                            <div class="article-content">
                                <div class="article-meta">
                                    <span class="category-tag"><?= htmlspecialchars($article['category']) ?></span>
                                    <span><?= $article['read_time'] ?> min read</span>
                                    <span><?= date('M j, Y', strtotime($article['created_at'])) ?></span>
                                </div>
                                <h2 class="article-title"><?= htmlspecialchars($article['title']) ?></h2>
                                <p class="article-excerpt"><?= htmlspecialchars($article['excerpt']) ?></p>
                                <div class="article-footer">
                                    <div class="author-info">
                                        <div class="author-avatar">
                                            <?= strtoupper(substr($article['author'], 0, 1)) ?>
                                        </div>
                                        <span><?= htmlspecialchars($article['author']) ?></span>
                                    </div>
                                    <div class="article-stats">
                                        <span class="stat">
                                            <i class="fas fa-eye"></i>
                                            <?= number_format($article['views']) ?>
                                        </span>
                                        <button class="like-btn" onclick="event.stopPropagation(); likeArticle(<?= $article['id'] ?>, this)">
                                            <i class="fas fa-heart"></i>
                                            <?= number_format($article['likes']) ?>
                                        </button>
                                        <a href="?action=edit&id=<?= $article['id'] ?>" onclick="event.stopPropagation()" class="btn" style="padding: 4px 8px; font-size: 12px;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($action === 'read' && isset($article)): ?>
        <div class="article-view">
            <a href="?action=home" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to stories
            </a>
            
            <div class="article-header">
                <h1><?= htmlspecialchars($article['title']) ?></h1>
                <div class="article-meta-detailed">
                    <span class="category-tag"><?= htmlspecialchars($article['category']) ?></span>
                    <span>By <?= htmlspecialchars($article['author']) ?></span>
                    <span><?= date('M j, Y', strtotime($article['created_at'])) ?></span>
                    <span><?= $article['read_time'] ?> min read</span>
                    <span><i class="fas fa-eye"></i> <?= number_format($article['views']) ?></span>
                </div>
            </div>
            
            <?php if ($article['featured_image']): ?>
                <img src="<?= htmlspecialchars($article['featured_image']) ?>" 
                     alt="<?= htmlspecialchars($article['title']) ?>" 
                     style="width: 100%; height: 400px; object-fit: cover; border-radius: 12px; margin-bottom: 32px;">
            <?php endif; ?>
            
            <div class="article-body">
                <?= nl2br(htmlspecialchars($article['content'])) ?>
            </div>
            
            <div class="share-buttons">
                <a href="#" class="share-btn" onclick="shareOnTwitter('<?= addslashes($article['title']) ?>')">
                    <i class="fab fa-twitter"></i>
                    Tweet
                </a>
                <a href="#" class="share-btn" onclick="shareOnLinkedIn('<?= addslashes($article['title']) ?>')">
                    <i class="fab fa-linkedin"></i>
                    Share
                </a>
                <a href="#" class="share-btn" onclick="copyLink()">
                    <i class="fas fa-link"></i>
                    Copy Link
                </a>
                <button class="share-btn like-btn" onclick="likeArticle(<?= $article['id'] ?>, this)">
                    <i class="fas fa-heart"></i>
                    <?= number_format($article['likes']) ?>
                </button>
            </div>
            
            <?php if ($article['tags']): ?>
                <div style="margin-top: 32px;">
                    <h4 style="margin-bottom: 12px; color: #374151;">Tags:</h4>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <?php foreach (explode(',', $article['tags']) as $tag): ?>
                            <span style="background: #f3f4f6; padding: 4px 12px; border-radius: 12px; font-size: 14px; color: #6b7280;">
                                #<?= trim(htmlspecialchars($tag)) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($action === 'create'): ?>
        <div class="form-container">
            <a href="?action=home" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to stories
            </a>
            <h1 style="margin-bottom: 32px; font-size: 32px; font-weight: 700;">Write a new story</h1>
            
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required placeholder="Write a compelling title...">
                </div>
                <div class="form-group">
                    <label for="author">Author *</label>
                    <input type="text" id="author" name="author" required placeholder="Your name">
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="General">General</option>
                        <option value="Technology">Technology</option>
                        <option value="DevOps">DevOps</option>
                        <option value="Backend">Backend</option>
                        <option value="Frontend">Frontend</option>
                        <option value="Design">Design</option>
                        <option value="AI/ML">AI/ML</option>
                        <option value="Mobile">Mobile</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tags">Tags</label>
                    <input type="text" id="tags" name="tags" class="tags-input" placeholder="programming, web development, tutorial">
                    <div class="tags-help">Separate tags with commas</div>
                </div>
                <div class="form-group">
                    <label for="featured_image">Featured Image URL</label>
                    <input type="url" id="featured_image" name="featured_image" placeholder="https://example.com/image.jpg">
                </div>
                <div class="form-group">
                    <label for="content">Content *</label>
                    <textarea id="content" name="content" required placeholder="Tell your story..."></textarea>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">
                        <i class="fas fa-paper-plane"></i>
                        Publish Story
                    </button>
                    <a href="?action=home" class="btn" style="padding: 12px 24px;">Cancel</a>
                </div>
            </form>
        </div>

    <?php elseif ($action === 'edit' && isset($article)): ?>
        <div class="form-container">
            <a href="?action=home" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to stories
            </a>
            <h1 style="margin-bottom: 32px; font-size: 32px; font-weight: 700;">Edit story</h1>
            
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= $article['id'] ?>">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($article['title']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="author">Author *</label>
                    <input type="text" id="author" name="author" value="<?= htmlspecialchars($article['author']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="General" <?= $article['category'] === 'General' ? 'selected' : '' ?>>General</option>
                        <option value="Technology" <?= $article['category'] === 'Technology' ? 'selected' : '' ?>>Technology</option>
                        <option value="DevOps" <?= $article['category'] === 'DevOps' ? 'selected' : '' ?>>DevOps</option>
                        <option value="Backend" <?= $article['category'] === 'Backend' ? 'selected' : '' ?>>Backend</option>
                        <option value="Frontend" <?= $article['category'] === 'Frontend' ? 'selected' : '' ?>>Frontend</option>
                        <option value="Design" <?= $article['category'] === 'Design' ? 'selected' : '' ?>>Design</option>
                        <option value="AI/ML" <?= $article['category'] === 'AI/ML' ? 'selected' : '' ?>>AI/ML</option>
                        <option value="Mobile" <?= $article['category'] === 'Mobile' ? 'selected' : '' ?>>Mobile</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tags">Tags</label>
                    <input type="text" id="tags" name="tags" value="<?= htmlspecialchars($article['tags']) ?>" class="tags-input">
                    <div class="tags-help">Separate tags with commas</div>
                </div>
                <div class="form-group">
                    <label for="featured_image">Featured Image URL</label>
                    <input type="url" id="featured_image" name="featured_image" value="<?= htmlspecialchars($article['featured_image']) ?>">
                </div>
                <div class="form-group">
                    <label for="content">Content *</label>
                    <textarea id="content" name="content" required><?= htmlspecialchars($article['content']) ?></textarea>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">
                        <i class="fas fa-save"></i>
                        Update Story
                    </button>
                    <a href="?action=home" class="btn" style="padding: 12px 24px;">Cancel</a>
                    <form style="display: inline;" method="POST" onsubmit="return confirm('Are you sure you want to delete this story?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $article['id'] ?>">
                        <button type="submit" class="btn" style="padding: 12px 24px; background: #ef4444; color: white; border-color: #ef4444;">
                            <i class="fas fa-trash"></i>
                            Delete
                        </button>
                    </form>
                </div>
            </form>
        </div>

    <?php else: ?>
        <div class="container" style="text-align: center; padding: 60px 0;">
            <h1>Story not found</h1>
            <p style="margin: 16px 0; color: #6b7280;">The story you're looking for doesn't exist.</p>
            <a href="?action=home" class="btn btn-primary">Return to stories</a>
        </div>
    <?php endif; ?>

    <script>
        function likeArticle(articleId, button) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=like&id=${articleId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const likeCount = button.querySelector('i').nextSibling;
                    const currentLikes = parseInt(likeCount.textContent.trim());
                    likeCount.textContent = ` ${(currentLikes + 1).toLocaleString()}`;
                    button.classList.add('liked');
                    button.disabled = true;
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function shareOnTwitter(title) {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent(`Check out: ${title}`);
            window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank');
        }

        function shareOnLinkedIn(title) {
            const url = encodeURIComponent(window.location.href);
            window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank');
        }

        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('Link copied to clipboard!');
            });
        }
    </script>
</body>
</html>