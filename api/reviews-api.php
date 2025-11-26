<?php
/**
 * SERSOLTEC v2.4 - Product Reviews API
 * Sprint 2.3: Reviews System
 * 
 * REST API for managing product reviews
 * 
 * Endpoints:
 * - GET  ?action=list&product_id=X          - List reviews for product
 * - GET  ?action=stats&product_id=X         - Get review statistics
 * - POST action=add                         - Add new review (requires auth)
 * - POST action=helpful&review_id=X         - Mark review as helpful
 * - POST action=report&review_id=X          - Report review
 * - POST action=approve&review_id=X         - Approve review (admin only)
 * - POST action=delete&review_id=X          - Delete review (admin only)
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('Europe/Warsaw');

// CORS headers (if needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Get current user ID from session
 */
function getCurrentUserId() {
    // Check multiple possible user ID session variables
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    if (isset($_SESSION['id'])) {
        return $_SESSION['id'];
    }
    return null;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    // Check multiple possible admin session variables
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        return true;
    }
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        return true;
    }
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        return true;
    }
    if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
        return true;
    }
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
        return true;
    }
    // Check for admin_logged_in (YOUR SYSTEM)
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] == 1) {
        return true;
    }
    // Check for admin_role (YOUR SYSTEM)
    if (isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], ['admin', 'superadmin'])) {
        return true;
    }
    return false;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    // Check multiple possible login session variables
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    if (isset($_SESSION['id'])) {
        return true;
    }
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        return true;
    }
    return false;
}

/**
 * Send JSON response
 */
function sendResponse($success, $data = [], $message = '', $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send error response
 */
function sendError($message, $httpCode = 400) {
    sendResponse(false, [], $message, $httpCode);
}

/**
 * Get database connection
 */
function getDB() {
    global $pdo;
    if (!isset($pdo)) {
        try {
            $host = DB_HOST ?? 'localhost';
            $dbname = DB_NAME ?? 'sersoltec_db';
            $user = DB_USER ?? 'sersoltec';
            $pass = DB_PASS ?? '';
            
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            // Set MySQL timezone
            $pdo->exec("SET time_zone = '+01:00'");
        } catch (PDOException $e) {
            sendError('Database connection failed: ' . $e->getMessage(), 500);
        }
    }
    return $pdo;
}

// Get database
$pdo = getDB();

// Get action - handle both URL params and JSON body
$action = $_GET['action'] ?? '';

// If POST request, try to get action from JSON body or POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['action'])) {
        $action = $input['action'];
    } elseif (isset($_POST['action'])) {
        $action = $_POST['action'];
    }
}

// Route requests
try {
    switch ($action) {
        
        // =============================================
        // GET: List reviews for product
        // =============================================
        case 'list':
            $productId = (int)($_GET['product_id'] ?? 0);
            if (!$productId) {
                sendError('Product ID is required');
            }
            
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(50, max(5, (int)($_GET['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;
            $sort = $_GET['sort'] ?? 'newest'; // newest, oldest, highest, lowest, helpful
            
            // Determine ORDER BY
            switch($sort) {
                case 'oldest':
                    $orderBy = 'r.created_at ASC';
                    break;
                case 'highest':
                    $orderBy = 'r.rating DESC, r.created_at DESC';
                    break;
                case 'lowest':
                    $orderBy = 'r.rating ASC, r.created_at DESC';
                    break;
                case 'helpful':
                    $orderBy = 'r.helpful_count DESC, r.created_at DESC';
                    break;
                default:
                    $orderBy = 'r.created_at DESC'; // newest
            }
            
            // Get reviews (adapted to existing DB structure)
            $stmt = $pdo->prepare("
                SELECT 
                    r.id,
                    r.product_id,
                    r.user_id,
                    r.rating,
                    r.title,
                    r.comment as review_text,
                    r.helpful_count,
                    r.author_name,
                    r.verified_purchase,
                    r.status,
                    r.created_at,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    u.email as user_email
                FROM product_reviews r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.product_id = ? 
                  AND r.status = 'approved'
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$productId, $limit, $offset]);
            $reviews = $stmt->fetchAll();
            
            // Get total count
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM product_reviews
                WHERE product_id = ? 
                  AND status = 'approved'
            ");
            $stmt->execute([$productId]);
            $total = $stmt->fetch()['total'];
            
            // Format reviews
            $formattedReviews = [];
            foreach ($reviews as $review) {
                // Mask email for privacy
                $email = $review['user_email'];
                $emailParts = explode('@', $email);
                $maskedEmail = substr($emailParts[0], 0, 2) . '***@' . $emailParts[1];
                
                $formattedReviews[] = [
                    'id' => (int)$review['id'],
                    'rating' => (int)$review['rating'],
                    'title' => $review['title'],
                    'review_text' => $review['review_text'],
                    'helpful_count' => (int)$review['helpful_count'],
                    'created_at' => $review['created_at'],
                    'user_name' => $review['user_name'],
                    'user_email_masked' => $maskedEmail,
                    'is_helpful' => false // Will be updated if user is logged in
                ];
            }
            
            // Check if logged-in user marked reviews as helpful
            if (isLoggedIn()) {
                $userId = getCurrentUserId();
                $reviewIds = array_column($formattedReviews, 'id');
                
                if (!empty($reviewIds)) {
                    $placeholders = str_repeat('?,', count($reviewIds) - 1) . '?';
                    $stmt = $pdo->prepare("
                        SELECT review_id
                        FROM review_helpful
                        WHERE user_id = ? AND review_id IN ($placeholders)
                    ");
                    $stmt->execute(array_merge([$userId], $reviewIds));
                    $helpfulReviews = array_column($stmt->fetchAll(), 'review_id');
                    
                    foreach ($formattedReviews as &$review) {
                        $review['is_helpful'] = in_array($review['id'], $helpfulReviews);
                    }
                }
            }
            
            sendResponse(true, [
                'reviews' => $formattedReviews,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
        
        // =============================================
        // GET: Get review statistics
        // =============================================
        case 'stats':
            $productId = (int)($_GET['product_id'] ?? 0);
            if (!$productId) {
                sendError('Product ID is required');
            }
            
            // Get average rating and count
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1
                FROM product_reviews
                WHERE product_id = ? 
                  AND status = 'approved'
            ");
            $stmt->execute([$productId]);
            $stats = $stmt->fetch();
            
            $totalReviews = (int)$stats['total_reviews'];
            $avgRating = $totalReviews > 0 ? round((float)$stats['average_rating'], 1) : 0;
            
            // Calculate percentages
            $ratingDistribution = [];
            for ($i = 5; $i >= 1; $i--) {
                $count = (int)$stats["rating_{$i}"];
                $percentage = $totalReviews > 0 ? round(($count / $totalReviews) * 100, 1) : 0;
                
                $ratingDistribution[] = [
                    'rating' => $i,
                    'count' => $count,
                    'percentage' => $percentage
                ];
            }
            
            sendResponse(true, [
                'total_reviews' => $totalReviews,
                'average_rating' => $avgRating,
                'rating_distribution' => $ratingDistribution
            ]);
            break;
        
        // =============================================
        // POST: Add new review
        // =============================================
        case 'add':
            if (!isLoggedIn()) {
                sendError('You must be logged in to submit a review', 401);
            }
            
            // Get POST data
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            $productId = (int)($input['product_id'] ?? 0);
            $rating = (int)($input['rating'] ?? 0);
            $title = trim($input['title'] ?? '');
            $reviewText = trim($input['review_text'] ?? '');
            $userId = getCurrentUserId();
            
            // Validation
            if (!$productId) {
                sendError('Product ID is required');
            }
            if ($rating < 1 || $rating > 5) {
                sendError('Rating must be between 1 and 5');
            }
            if (empty($title) || strlen($title) < 3) {
                sendError('Title must be at least 3 characters long');
            }
            if (strlen($title) > 255) {
                sendError('Title is too long (max 255 characters)');
            }
            if (empty($reviewText) || strlen($reviewText) < 10) {
                sendError('Review must be at least 10 characters long');
            }
            if (strlen($reviewText) > 5000) {
                sendError('Review is too long (max 5000 characters)');
            }
            
            // Check if product exists
            $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            if (!$stmt->fetch()) {
                sendError('Product not found', 404);
            }
            
            // Check if user already reviewed this product
            $stmt = $pdo->prepare("
                SELECT id FROM product_reviews 
                WHERE product_id = ? AND user_id = ?
            ");
            $stmt->execute([$productId, $userId]);
            if ($stmt->fetch()) {
                sendError('You have already reviewed this product');
            }
            
            // Insert review (adapted to existing structure)
            $stmt = $pdo->prepare("
                INSERT INTO product_reviews 
                (product_id, user_id, rating, title, comment, author_name, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            // Get user name for author_name (first_name + last_name)
            $stmtUser = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE id = ?");
            $stmtUser->execute([$userId]);
            $authorName = $stmtUser->fetch()['full_name'] ?? 'Anonymous';
            
            $stmt->execute([$productId, $userId, $rating, $title, $reviewText, $authorName]);
            $reviewId = $pdo->lastInsertId();
            
            sendResponse(true, [
                'review_id' => $reviewId,
                'message' => 'Review submitted successfully. It will be visible after moderation.'
            ], 'Review submitted successfully', 201);
            break;
        
        // =============================================
        // POST: Mark review as helpful
        // =============================================
        case 'helpful':
            if (!isLoggedIn()) {
                sendError('You must be logged in to mark reviews as helpful', 401);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $reviewId = (int)($input['review_id'] ?? 0);
            $userId = getCurrentUserId();
            
            if (!$reviewId) {
                sendError('Review ID is required');
            }
            
            // Check if review exists
            $stmt = $pdo->prepare("SELECT id FROM product_reviews WHERE id = ?");
            $stmt->execute([$reviewId]);
            if (!$stmt->fetch()) {
                sendError('Review not found', 404);
            }
            
            // Check if already marked as helpful
            $stmt = $pdo->prepare("
                SELECT 1 FROM review_helpful 
                WHERE review_id = ? AND user_id = ?
            ");
            $stmt->execute([$reviewId, $userId]);
            
            if ($stmt->fetch()) {
                // Remove helpful mark
                $stmt = $pdo->prepare("
                    DELETE FROM review_helpful 
                    WHERE review_id = ? AND user_id = ?
                ");
                $stmt->execute([$reviewId, $userId]);
                
                // Decrement helpful_count
                $stmt = $pdo->prepare("
                    UPDATE product_reviews 
                    SET helpful_count = GREATEST(0, helpful_count - 1)
                    WHERE id = ?
                ");
                $stmt->execute([$reviewId]);
                
                $message = 'Helpful mark removed';
                $isHelpful = false;
            } else {
                // Add helpful mark
                $stmt = $pdo->prepare("
                    INSERT INTO review_helpful (review_id, user_id)
                    VALUES (?, ?)
                ");
                $stmt->execute([$reviewId, $userId]);
                
                // Increment helpful_count
                $stmt = $pdo->prepare("
                    UPDATE product_reviews 
                    SET helpful_count = helpful_count + 1
                    WHERE id = ?
                ");
                $stmt->execute([$reviewId]);
                
                $message = 'Review marked as helpful';
                $isHelpful = true;
            }
            
            // Get new helpful_count
            $stmt = $pdo->prepare("SELECT helpful_count FROM product_reviews WHERE id = ?");
            $stmt->execute([$reviewId]);
            $helpfulCount = (int)$stmt->fetch()['helpful_count'];
            
            sendResponse(true, [
                'review_id' => $reviewId,
                'is_helpful' => $isHelpful,
                'helpful_count' => $helpfulCount
            ], $message);
            break;
        
        // =============================================
        // POST: Report review
        // =============================================
        case 'report':
            if (!isLoggedIn()) {
                sendError('You must be logged in to report reviews', 401);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $reviewId = (int)($input['review_id'] ?? 0);
            $reason = trim($input['reason'] ?? '');
            $userId = getCurrentUserId();
            
            if (!$reviewId) {
                sendError('Review ID is required');
            }
            if (empty($reason)) {
                sendError('Reason is required');
            }
            
            // Check if review exists
            $stmt = $pdo->prepare("SELECT id FROM product_reviews WHERE id = ?");
            $stmt->execute([$reviewId]);
            if (!$stmt->fetch()) {
                sendError('Review not found', 404);
            }
            
            // Log report (you might want to create a review_reports table)
            // For now, we'll just log it
            error_log("Review #{$reviewId} reported by user #{$userId}: {$reason}");
            
            // TODO: Create review_reports table and insert record
            
            sendResponse(true, [], 'Review reported successfully. Our team will review it.');
            break;
        
        // =============================================
        // POST: Approve review (admin only)
        // =============================================
        case 'approve':
            if (!isAdmin()) {
                sendError('Admin access required', 403);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $reviewId = (int)($input['review_id'] ?? 0);
            
            if (!$reviewId) {
                sendError('Review ID is required');
            }
            
            $stmt = $pdo->prepare("
                UPDATE product_reviews 
                SET status = 'approved', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reviewId]);
            
            if ($stmt->rowCount() === 0) {
                sendError('Review not found', 404);
            }
            
            sendResponse(true, ['review_id' => $reviewId], 'Review approved');
            break;
        
        // =============================================
        // POST: Delete review (admin only) - marks as rejected
        // =============================================
        case 'delete':
            if (!isAdmin()) {
                sendError('Admin access required', 403);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $reviewId = (int)($input['review_id'] ?? 0);
            
            if (!$reviewId) {
                sendError('Review ID is required');
            }
            
            $stmt = $pdo->prepare("
                UPDATE product_reviews 
                SET status = 'rejected', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reviewId]);
            
            if ($stmt->rowCount() === 0) {
                sendError('Review not found', 404);
            }
            
            sendResponse(true, ['review_id' => $reviewId], 'Review deleted');
            break;
        
        // =============================================
        // POST: Get unapproved reviews (admin only)
        // =============================================
        case 'pending':
            if (!isAdmin()) {
                sendError('Admin access required', 403);
            }
            
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(50, max(5, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            
            $stmt = $pdo->prepare("
                SELECT 
                    r.id,
                    r.product_id,
                    r.user_id,
                    r.rating,
                    r.title,
                    r.comment as review_text,
                    r.author_name,
                    r.verified_purchase,
                    r.status,
                    r.created_at,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    u.email as user_email,
                    p.name_pl as product_name
                FROM product_reviews r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN products p ON r.product_id = p.id
                WHERE r.status = 'pending'
                ORDER BY r.created_at ASC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $reviews = $stmt->fetchAll();
            
            // Get total count
            $stmt = $pdo->query("
                SELECT COUNT(*) as total
                FROM product_reviews
                WHERE status = 'pending'
            ");
            $total = $stmt->fetch()['total'];
            
            sendResponse(true, [
                'reviews' => $reviews,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
        
        default:
            sendError('Invalid action', 400);
    }
    
} catch (PDOException $e) {
    error_log('Reviews API Error: ' . $e->getMessage());
    sendError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('Reviews API Error: ' . $e->getMessage());
    sendError('Server error: ' . $e->getMessage(), 500);
}