<?php
require_once 'auth_check.php';
require_once 'assets/php/db.php';

// Handle ticket response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id']) && isset($_POST['message'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    $message = trim($_POST['message']);
    $admin_id = $_SESSION['admin_id'];
    if (!empty($message)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message, created_at) VALUES (?, 'admin', ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, 'iis', $ticket_id, $admin_id, $message);
        mysqli_stmt_execute($stmt);
        mysqli_query($conn, "UPDATE tickets SET status = 'responded', updated_at = NOW() WHERE id = $ticket_id AND status = 'open'");
        mysqli_query($conn, "INSERT INTO activity_logs (admin_id, action, details, created_at) VALUES ($admin_id, 'responded_to_ticket', 'Responded to ticket #$ticket_id', NOW())");
    }
    header('Location: support.php?id=' . $ticket_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ticket_id']) && isset($_POST['status'])) {
    $ticket_id = (int)$_POST['update_ticket_id'];
    $status = $_POST['status'];
    $admin_id = $_SESSION['admin_id'];
    $allowed_statuses = ['open', 'responded', 'closed'];
    if (in_array($status, $allowed_statuses)) {
        $stmt = mysqli_prepare($conn, "UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $status, $ticket_id);
        mysqli_stmt_execute($stmt);
        mysqli_query($conn, "INSERT INTO activity_logs (admin_id, action, details, created_at) VALUES ($admin_id, 'updated_ticket_status', 'Updated ticket #$ticket_id status to $status', NOW())");
    }
    header('Location: support.php' . (isset($_GET['id']) ? '?id=' . $_GET['id'] : ''));
    exit;
}

$ticket = null;
$messages = [];
if (isset($_GET['id'])) {
    $ticket_id = (int)$_GET['id'];
    $ticket_result = mysqli_query($conn, "SELECT t.*, u.full_name, u.email FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = $ticket_id");
    $ticket = mysqli_fetch_assoc($ticket_result);
    if ($ticket) {
        $messages_result = mysqli_query($conn, "SELECT tm.*, u.full_name, a.username as admin_name FROM ticket_messages tm LEFT JOIN users u ON tm.sender_id = u.id AND tm.sender_type = 'user' LEFT JOIN admins a ON tm.sender_id = a.id AND tm.sender_type = 'admin' WHERE tm.ticket_id = $ticket_id ORDER BY tm.created_at ASC");
        while ($row = mysqli_fetch_assoc($messages_result)) $messages[] = $row;
    }
}

$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;
$status_filter = $_GET['status'] ?? '';
$where = $status_filter ? "WHERE t.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'" : '';

$result = mysqli_query($conn, "SELECT t.*, u.full_name, u.email, (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = t.id) as message_count FROM tickets t JOIN users u ON t.user_id = u.id $where ORDER BY t.updated_at DESC LIMIT $per_page OFFSET $offset");
$tickets = [];
while ($row = mysqli_fetch_assoc($result)) $tickets[] = $row;

$count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tickets t $where");
$total_tickets = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_tickets / $per_page);

$stats_result = mysqli_query($conn, "SELECT COUNT(*) as total_tickets, SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open, SUM(CASE WHEN status = 'responded' THEN 1 ELSE 0 END) as responded, SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed FROM tickets");
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Vertex Admin — Support</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --navy:        #0f172a;
      --border-dk:   rgba(255,255,255,0.07);
      --accent:      #3b82f6;
      --accent-dark: #2563eb;
      --accent-glow: rgba(59,130,246,0.15);
      --red:         #ef4444;
      --border:      #e2e8f0;
      --bg:          #f8fafc;
      --card:        #ffffff;
      --text:        #0f172a;
      --text-2:      #64748b;
      --text-muted:  #94a3b8;
      --sidebar-w:   240px;
      --danger:      #ef4444;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: #f1f5f9;
      color: var(--text);
      display: flex;
      min-height: 100vh;
    }

    /* ══════════════════════════════
       SIDEBAR
    ══════════════════════════════ */
    .sidebar {
      width: var(--sidebar-w);
      background: var(--navy);
      min-height: 100vh;
      position: fixed;
      left: 0; top: 0; bottom: 0;
      display: flex;
      flex-direction: column;
      border-right: 1px solid var(--border-dk);
      z-index: 100;
    }

    .sidebar-logo {
      padding: 24px 20px 20px;
      border-bottom: 1px solid var(--border-dk);
    }

    .sidebar-logo .logo-name {
      font-size: 1.4rem;
      font-weight: 700;
      color: #f1f5f9;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    .sidebar-logo .logo-sub {
      font-size: 0.68rem;
      color: #475569;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      margin-top: 3px;
    }

    .sidebar-section { padding: 22px 12px 6px; }

    .sidebar-label {
      font-size: 0.70rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #475569;
      padding: 0 8px;
      margin-bottom: 9px;
    }

    .sidebar-nav { list-style: none; }

    .sidebar-nav li a {
      display: flex;
      align-items: center;
      gap: 11px;
      padding: 12px 13px;
      border-radius: 8px;
      text-decoration: none;
      color: #94a3b8;
      font-size: 0.95rem;
      transition: all 0.15s;
      font-weight: 500;
    }

    .sidebar-nav li a i {
      width: 17px;
      text-align: center;
      font-size: 0.88rem;
      flex-shrink: 0;
    }

    .sidebar-nav li a:hover {
      background: rgba(255,255,255,0.05);
      color: #f1f5f9;
    }

    .sidebar-nav li a.active {
      background: var(--accent);
      color: #fff;
      font-weight: 500;
    }

    .sidebar-bottom {
      margin-top: auto;
      padding: 20px 16px;
      border-top: 1px solid var(--border-dk);
    }

    .sidebar-logout-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      padding: 12px;
      border-radius: 8px;
      text-decoration: none;
      color: #94a3b8;
      font-size: 0.88rem;
      font-weight: 500;
      border: 1px solid rgba(255,255,255,0.08);
      transition: all 0.15s;
    }

    .sidebar-logout-btn:hover {
      background: rgba(239,68,68,0.12);
      border-color: rgba(239,68,68,0.3);
      color: #f87171;
    }

    /* ══════════════════════════════
       MAIN
    ══════════════════════════════ */
    .main {
      margin-left: var(--sidebar-w);
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .topbar {
      background: var(--card);
      border-bottom: 1px solid var(--border);
      padding: 0 36px;
      height: 85px;
      display: flex;
      align-items: center;
      gap: 16px;
      position: sticky;
      top: 0;
      z-index: 50;
    }

    .topbar-title { font-size: 1.4rem; font-weight: 700; color: var(--text); }
    .topbar-sub { font-size: 1rem; color: var(--text-muted); margin-top: 2px; font-weight: 500; }
    .topbar-left { display: flex; flex-direction: column; }

    .topbar-search {
      display: flex;
      align-items: center;
      gap: 8px;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 8px 14px;
      width: 260px;
      margin-left: auto;
    }

    .topbar-search input {
      border: none; outline: none; background: transparent;
      font-family: 'Poppins', sans-serif;
      font-size: 12.5px; color: var(--text); flex: 1;
    }

    .topbar-search input::placeholder { color: var(--text-muted); }

    .topbar-actions { display: flex; align-items: center; gap: 8px; }

    .topbar-btn {
      width: 36px; height: 36px;
      border: 1px solid var(--border);
      background: var(--card);
      border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: var(--text-2);
      transition: all 0.15s;
      position: relative;
    }

    .topbar-btn:hover { border-color: var(--accent); color: var(--accent); }

    .notif-dot {
      position: absolute; top: 6px; right: 6px;
      width: 7px; height: 7px;
      background: var(--danger); border-radius: 50%;
      border: 1.5px solid var(--card);
    }

    .content { padding: 28px; flex: 1; }

    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
    }

    .page-header h1 { font-size: 1.2rem; font-weight: 700; }
    .page-header p  { font-size: 12.5px; color: var(--text-muted); margin-top: 2px; }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 9px 18px;
      border-radius: 10px;
      font-family: 'Poppins', sans-serif;
      font-size: 12.5px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
      border: none;
      text-decoration: none;
    }

    .btn-primary { background: var(--accent); color: #fff; }
    .btn-primary:hover { background: var(--accent-dark); }
    .btn-outline { background: transparent; color: var(--text-2); border: 1px solid var(--border); }
    .btn-outline:hover { border-color: var(--accent); color: var(--accent); }
    .btn-secondary { background: var(--bg); color: var(--text-2); border: 1px solid var(--border); }
    .btn-secondary:hover { border-color: var(--accent); color: var(--accent); }
    .btn-sm { padding: 6px 12px; font-size: 11.5px; }

    .summary-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }

    .summary-mini {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 16px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .s-icon {
      width: 36px; height: 36px;
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }

    .s-icon i { font-size: 0.9rem; }
    .s-val { font-size: 1.1rem; font-weight: 700; color: var(--text); }
    .s-lbl { font-size: 11px; color: var(--text-muted); }

    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      overflow: hidden;
      margin-bottom: 20px;
    }

    .card-header {
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
    }

    .card-header h3 { font-size: 0.95rem; font-weight: 600; color: var(--text); margin-bottom: 4px; }
    .card-header h4 { font-size: 0.85rem; font-weight: 600; color: var(--text); }

    .card-body { padding: 20px; }

    .filter-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 14px 20px;
      border-bottom: 1px solid var(--border);
    }

    .filter-select {
      border: 1px solid var(--border);
      background: var(--bg);
      border-radius: 9px;
      padding: 8px 12px;
      font-family: 'Poppins', sans-serif;
      font-size: 12.5px;
      color: var(--text-2);
      cursor: pointer;
      outline: none;
    }

    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }

    thead th {
      padding: 12px 16px;
      font-size: 11px;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      text-align: left;
      background: var(--bg);
      border-bottom: 1px solid var(--border);
    }

    tbody td {
      padding: 13px 16px;
      font-size: 13px;
      border-bottom: 1px solid var(--border);
      color: var(--text-2);
    }

    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover { background: #f8fafc; }

    .text-muted { font-size: 11px; color: var(--text-muted); display: block; }
    .fw-600 { font-weight: 600; color: var(--text); }

    .badge {
      display: inline-flex;
      align-items: center;
      padding: 3px 10px;
      border-radius: 99px;
      font-size: 11px;
      font-weight: 600;
    }

    .status-open       { background: #fee2e2; color: #dc2626; }
    .status-responded  { background: #dbeafe; color: #2563eb; }
    .status-closed     { background: #dcfce7; color: #16a34a; }

    .pagination {
      display: flex;
      align-items: center;
      gap: 4px;
      padding: 16px 20px;
      border-top: 1px solid var(--border);
      justify-content: flex-end;
    }

    /* ── Ticket detail ── */
    .ticket-detail { max-width: 800px; }

    .ticket-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 6px;
    }

    .ticket-meta span { font-size: 12.5px; color: var(--text-2); }

    .message-thread { display: flex; flex-direction: column; gap: 14px; padding: 20px; }

    .message {
      padding: 14px 16px;
      border-radius: 12px;
      font-size: 13px;
      line-height: 1.6;
    }

    .message.user {
      background: var(--bg);
      border: 1px solid var(--border);
      margin-right: 40px;
    }

    .message.admin {
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      margin-left: 40px;
    }

    .message-meta {
      font-size: 11px;
      color: var(--text-muted);
      margin-bottom: 6px;
      font-weight: 500;
    }

    .response-form { padding: 20px; }

    .form-field { margin-bottom: 14px; }

    .form-label {
      display: block;
      font-size: 11.5px;
      font-weight: 500;
      color: var(--text-2);
      margin-bottom: 6px;
    }

    .form-input, textarea, select {
      width: 100%;
      padding: 9px 12px;
      border: 1px solid var(--border);
      border-radius: 9px;
      font-family: 'Poppins', sans-serif;
      font-size: 12.5px;
      color: var(--text);
      background: var(--bg);
      outline: none;
      transition: border-color 0.2s;
    }

    .form-input:focus, textarea:focus, select:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px var(--accent-glow);
    }

    textarea { resize: vertical; }

    .form-actions {
      display: flex;
      gap: 10px;
      margin-top: 14px;
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-name">Vertex</div>
    <div class="logo-sub">Admin Panel</div>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-label">Main Menu</div>
    <ul class="sidebar-nav">
      <li><a href="dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
      <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
      <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a></li>
      <li><a href="customers.php"><i class="fas fa-users"></i> Customers</a></li>
      <li><a href="support.php" class="active"><i class="fas fa-envelope"></i> Support</a></li>
      <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
    </ul>
  </div>

  <div class="sidebar-bottom">
    <a href="logout.php" class="sidebar-logout-btn">
      <i class="fas fa-sign-out-alt"></i> Log Out
    </a>
  </div>
</aside>

<!-- MAIN -->
<div class="main">

  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-title">Support</div>
      <div class="topbar-sub">Manage customer inquiries and tickets</div>
    </div>
  </div>

  <div class="content">

    <?php if (isset($ticket)): ?>

      <!-- Ticket Detail -->
      <div class="ticket-detail">

        <div class="page-header">
          <div>
            <h1><?php echo htmlspecialchars($ticket['subject']); ?></h1>
            <p>From: <?php echo htmlspecialchars($ticket['full_name']); ?> — <?php echo htmlspecialchars($ticket['email']); ?></p>
          </div>
          <span class="badge status-<?php echo $ticket['status']; ?>"><?php echo ucfirst($ticket['status']); ?></span>
        </div>

        <div class="card">
          <div class="card-header">
            <h4>Conversation</h4>
            <span class="text-muted" style="margin-top:4px;">Started <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?></span>
          </div>
          <div class="message-thread">
            <div class="message user">
              <div class="message-meta"><?php echo htmlspecialchars($ticket['full_name']); ?> · <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?></div>
              <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
            </div>
            <?php foreach ($messages as $msg): ?>
            <div class="message <?php echo $msg['sender_type']; ?>">
              <div class="message-meta">
                <?php if ($msg['sender_type'] === 'user'): ?>
                  <?php echo htmlspecialchars($msg['full_name'] ?? $msg['admin_name'] ?? 'Unknown'); ?>
                <?php else: ?>
                  <i class="fas fa-shield-alt" style="color:var(--accent);font-size:0.7rem;"></i> Admin (<?php echo htmlspecialchars($msg['admin_name']); ?>)
                <?php endif; ?>
                · <?php echo date('M j, Y g:i A', strtotime($msg['created_at'])); ?>
              </div>
              <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if ($ticket['status'] !== 'closed'): ?>

        <div class="card">
          <div class="card-header"><h4>Reply to Customer</h4></div>
          <div class="response-form">
            <form method="POST">
              <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
              <div class="form-field">
                <label class="form-label">Your Response</label>
                <textarea name="message" rows="4" placeholder="Type your response…" required></textarea>
              </div>
              <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Reply</button>
                <a href="support.php" class="btn btn-outline">Back to Tickets</a>
              </div>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><h4>Update Status</h4></div>
          <div class="response-form">
            <form method="POST">
              <input type="hidden" name="update_ticket_id" value="<?php echo $ticket['id']; ?>">
              <div class="form-field">
                <label class="form-label">Status</label>
                <select name="status">
                  <option value="open"      <?php echo $ticket['status'] === 'open'      ? 'selected' : ''; ?>>Open</option>
                  <option value="responded" <?php echo $ticket['status'] === 'responded' ? 'selected' : ''; ?>>Responded</option>
                  <option value="closed">Closed</option>
                </select>
              </div>
              <div class="form-actions">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-sync-alt"></i> Update Status</button>
              </div>
            </form>
          </div>
        </div>

        <?php else: ?>
        <div class="form-actions">
          <a href="support.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Tickets</a>
        </div>
        <?php endif; ?>

      </div>

    <?php else: ?>

      <!-- Tickets List -->
      <div class="page-header">
        <div>
          <h1>Support</h1>
          <p>Manage customer support tickets</p>
        </div>
      </div>

      <div class="summary-row">
        <div class="summary-mini">
          <div class="s-icon" style="background:#dbeafe;">
            <i class="fas fa-envelope" style="color:#2563eb;"></i>
          </div>
          <div><div class="s-val"><?php echo $stats['total_tickets']; ?></div><div class="s-lbl">Total Tickets</div></div>
        </div>
        <div class="summary-mini">
          <div class="s-icon" style="background:#fee2e2;">
            <i class="fas fa-exclamation-circle" style="color:#dc2626;"></i>
          </div>
          <div><div class="s-val"><?php echo $stats['open']; ?></div><div class="s-lbl">Open</div></div>
        </div>
        <div class="summary-mini">
          <div class="s-icon" style="background:#dbeafe;">
            <i class="fas fa-reply" style="color:#2563eb;"></i>
          </div>
          <div><div class="s-val"><?php echo $stats['responded']; ?></div><div class="s-lbl">Responded</div></div>
        </div>
        <div class="summary-mini">
          <div class="s-icon" style="background:#dcfce7;">
            <i class="fas fa-check-circle" style="color:#16a34a;"></i>
          </div>
          <div><div class="s-val"><?php echo $stats['closed']; ?></div><div class="s-lbl">Closed</div></div>
        </div>
      </div>

      <div class="card">
        <div class="filter-bar">
          <select class="filter-select" id="statusFilter">
            <option value="">All Statuses</option>
            <option value="open"      <?php echo $status_filter === 'open'      ? 'selected' : ''; ?>>Open</option>
            <option value="responded" <?php echo $status_filter === 'responded' ? 'selected' : ''; ?>>Responded</option>
            <option value="closed"    <?php echo $status_filter === 'closed'    ? 'selected' : ''; ?>>Closed</option>
          </select>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Ticket ID</th>
                <th>Customer</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Messages</th>
                <th>Last Update</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tickets as $t): ?>
              <tr>
                <td><span style="font-weight:600;color:var(--accent);">#<?php echo str_pad($t['id'], 4, '0', STR_PAD_LEFT); ?></span></td>
                <td>
                  <div style="font-weight:500;color:var(--text);"><?php echo htmlspecialchars($t['full_name']); ?></div>
                  <span class="text-muted"><?php echo htmlspecialchars($t['email']); ?></span>
                </td>
                <td><?php echo htmlspecialchars($t['subject']); ?></td>
                <td><span class="badge status-<?php echo $t['status']; ?>"><?php echo ucfirst($t['status']); ?></span></td>
                <td><?php echo $t['message_count']; ?></td>
                <td><?php echo date('M j, Y', strtotime($t['updated_at'])); ?></td>
                <td>
                  <a href="support.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-eye"></i> View
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-sm btn-outline">Previous</a>
          <?php endif; ?>
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
          <?php endfor; ?>
          <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-sm btn-outline">Next</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

    <?php endif; ?>

  </div>
</div>

<script>
document.getElementById('statusFilter') && document.getElementById('statusFilter').addEventListener('change', function() {
  const url = new URL(window.location);
  this.value ? url.searchParams.set('status', this.value) : url.searchParams.delete('status');
  url.searchParams.delete('page');
  window.location.href = url.toString();
});
</script>

</body>
</html>