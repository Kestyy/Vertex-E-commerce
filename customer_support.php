<?php
session_start();
require_once 'assets/php/db.php';

// Check customer auth
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$user_stmt = mysqli_prepare($conn, "SELECT full_name, email FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));
mysqli_stmt_close($user_stmt);

// Fetch customer's tickets
$tickets_query = mysqli_prepare($conn, "
    SELECT t.id, t.subject, t.description, t.status, t.priority, t.created_at, t.updated_at
    FROM tickets t
    WHERE t.user_id = ?
    ORDER BY t.updated_at DESC
");
mysqli_stmt_bind_param($tickets_query, 'i', $user_id);
mysqli_stmt_execute($tickets_query);
$tickets_result = mysqli_stmt_get_result($tickets_query);
$tickets = [];
while($row = mysqli_fetch_assoc($tickets_result)) {
    $tickets[] = $row;
}
mysqli_stmt_close($tickets_query);

// Handle new ticket submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject'])) {
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['message'] ?? '');
    $priority = $_POST['priority'] ?? 'low';
    
    if (empty($subject) || empty($description)) {
        $error_message = 'Please fill in all fields.';
    } else {
        $insert = mysqli_prepare($conn, "
            INSERT INTO tickets (user_id, subject, description, priority, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, 'open', NOW(), NOW())
        ");
        mysqli_stmt_bind_param($insert, 'isss', $user_id, $subject, $description, $priority);
        
        if (mysqli_stmt_execute($insert)) {
            $success_message = 'Your support ticket has been created successfully!';
            // Refresh page to show new ticket
            header('Refresh: 2');
        } else {
            $error_message = 'Failed to create ticket. Please try again.';
        }
        mysqli_stmt_close($insert);
    }
}

// Helper function for time display
function time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'just now';
    $diff = round($diff / 60);
    if ($diff < 60) return $diff . 'm ago';
    $diff = round($diff / 60);
    if ($diff < 24) return $diff . 'h ago';
    $diff = round($diff / 24);
    if ($diff < 7) return $diff . 'd ago';
    $diff = round($diff / 7);
    if ($diff < 4) return $diff . 'w ago';
    return date('M j, Y', $time);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Vertex — Customer Support</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link rel="stylesheet" href="assets/css/style.css"/>
    <style>
        .support-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .support-header {
            margin-bottom: 40px;
            text-align: center;
        }

        .support-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .support-header p {
            font-size: 1rem;
            color: #64748b;
        }

        .support-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 32px;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .support-grid {
                grid-template-columns: 1fr;
            }
        }

        .support-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
        }

        .panel-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-title i {
            color: #3b82f6;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            color: #0f172a;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-submit:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #dcfce7;
        }

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fee2e2;
        }

        .tickets-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .ticket-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .ticket-card:hover {
            border-color: #3b82f6;
            background: #f0f9fe;
        }

        .ticket-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .ticket-subject {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
        }

        .ticket-id {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .ticket-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.85rem;
            margin-bottom: 8px;
        }

        .ticket-priority {
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .priority-high {
            background: #fef2f2;
            color: #dc2626;
        }

        .priority-medium {
            background: #fef9c3;
            color: #ca8a04;
        }

        .priority-low {
            background: #f0fdf4;
            color: #16a34a;
        }

        .ticket-status {
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .status-open {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-responded {
            background: #fef9c3;
            color: #ca8a04;
        }

        .status-closed {
            background: #f1f5f9;
            color: #64748b;
        }

        .ticket-preview {
            font-size: 0.85rem;
            color: #64748b;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .ticket-time {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="support-container">
        <div class="support-header">
            <h1>Customer Support</h1>
            <p>Submit a ticket or view your existing support requests</p>
        </div>

        <div class="support-grid">
            <!-- New Ticket Form -->
            <div class="support-panel">
                <h2 class="panel-title">
                    <i class="fas fa-ticket-alt"></i> Create Ticket
                </h2>

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= $success_message ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" placeholder="e.g., Problem with order #123" required/>
                    </div>

                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" placeholder="Please describe your issue in detail..." required></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Submit Ticket
                    </button>
                </form>
            </div>

            <!-- Your Tickets -->
            <div class="support-panel">
                <h2 class="panel-title">
                    <i class="fas fa-list"></i> Your Tickets (<?= count($tickets) ?>)
                </h2>

                <?php if (empty($tickets)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Tickets Yet</h3>
                        <p>You haven't created any support tickets. Create one to get started!</p>
                    </div>
                <?php else: ?>
                    <div class="tickets-list">
                        <?php foreach ($tickets as $ticket): ?>
                            <div class="ticket-card">
                                <div class="ticket-header">
                                    <span class="ticket-subject"><?= htmlspecialchars($ticket['subject']) ?></span>
                                    <span class="ticket-id">#<?= $ticket['id'] ?></span>
                                </div>

                                <div class="ticket-meta">
                                    <span class="ticket-priority priority-<?= $ticket['priority'] ?>">
                                        <?= ucfirst($ticket['priority']) ?>
                                    </span>
                                    <span class="ticket-status status-<?= $ticket['status'] ?>">
                                        <?= ucfirst($ticket['status']) ?>
                                    </span>
                                    <span class="ticket-time"><?= time_ago($ticket['updated_at']) ?></span>
                                </div>

                                <div class="ticket-preview"><?= htmlspecialchars(substr($ticket['description'], 0, 100)) ?>...</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>