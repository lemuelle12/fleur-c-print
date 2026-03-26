<?php
// ── includes/header.php ──────────────────────────────────────
// Call: render_header('Page Title', 'nav-key');

function render_header(string $title, string $active_nav): void {
    $flash = get_flash();
    $badge = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','in-progress','ready')")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> — Fleur C Print</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Outfit:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{--white:#ffffff;--bg:#f7f6f4;--surface:#ffffff;--surface2:#f2f1ef;--surface3:#eceae7;--border:#e4e2de;--border2:#d5d2cc;--text:#1a1917;--text-2:#5a574f;--text-3:#9b9790;--accent:#c07b54;--accent-lt:#f5ece6;--accent-dk:#9a5e3a;--green:#4a7c59;--green-lt:#e8f2eb;--red:#b94040;--red-lt:#faeaea;--blue:#3a6690;--blue-lt:#e8eff6;--radius:4px;--radius-lg:8px;--sidebar:232px;--tr:.18s ease}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:14px;-webkit-font-smoothing:antialiased}
input,select,textarea,button{font-family:inherit}
::-webkit-scrollbar{width:5px;height:5px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}
a{color:inherit;text-decoration:none}

/* APP */
.app{display:flex;height:100vh;overflow:hidden}
#sidebar{width:var(--sidebar);min-width:var(--sidebar);background:var(--white);border-right:1px solid var(--border);display:flex;flex-direction:column}
.sb-head{padding:28px 24px 24px;border-bottom:1px solid var(--border)}
.sb-wordmark{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:400;letter-spacing:2px;text-transform:uppercase;color:var(--text);line-height:1}
.sb-wordmark em{font-style:italic;color:var(--accent)}
.sb-sub{font-size:9px;font-weight:500;letter-spacing:2.5px;text-transform:uppercase;color:var(--text-3);margin-top:4px}
.sb-nav{flex:1;padding:12px 0;overflow-y:auto}
.sb-section{font-size:9px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--text-3);padding:16px 24px 6px}
.nav-item{display:flex;align-items:center;gap:11px;padding:10px 24px;cursor:pointer;font-size:13px;font-weight:400;color:var(--text-2);transition:all var(--tr);position:relative;user-select:none}
.nav-item::before{content:'';position:absolute;left:0;top:0;bottom:0;width:2px;background:var(--accent);opacity:0;transition:opacity var(--tr)}
.nav-item:hover{color:var(--text);background:var(--bg)}
.nav-item.active{color:var(--text);background:var(--accent-lt);font-weight:500}
.nav-item.active::before{opacity:1}
.nav-dot{width:5px;height:5px;border-radius:50%;background:var(--text-3);flex-shrink:0;transition:background var(--tr)}
.nav-item.active .nav-dot{background:var(--accent)}
.nav-badge{margin-left:auto;background:var(--accent);color:var(--white);font-size:10px;font-weight:600;padding:1px 6px;border-radius:10px;min-width:20px;text-align:center}
.sb-foot{padding:20px 24px;border-top:1px solid var(--border)}
.sb-shop{font-size:11px;color:var(--text-3);margin-bottom:10px;line-height:1.6}
.sb-shop strong{display:block;color:var(--text-2);font-weight:500}
.btn-logout{width:100%;padding:8px;background:transparent;border:1px solid var(--border2);color:var(--text-3);border-radius:var(--radius);cursor:pointer;font-size:11px;font-weight:500;letter-spacing:.5px;transition:all var(--tr)}
.btn-logout:hover{border-color:var(--red);color:var(--red);background:var(--red-lt)}

/* MAIN */
#main{flex:1;overflow-y:auto;background:var(--bg)}
.page-wrap{padding:36px 40px}
.page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:32px}
.page-title{font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:300;letter-spacing:1px;line-height:1.1}
.page-title em{font-style:italic;color:var(--accent)}
.page-subtitle{font-size:12px;color:var(--text-3);margin-top:4px;font-weight:400;letter-spacing:.3px}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:var(--radius);cursor:pointer;font-size:12px;font-weight:500;letter-spacing:.5px;border:none;transition:all var(--tr);white-space:nowrap;text-decoration:none}
.btn-primary{background:var(--text);color:var(--white)}
.btn-primary:hover{background:var(--accent)}
.btn-outline{background:transparent;border:1px solid var(--border2);color:var(--text-2)}
.btn-outline:hover{border-color:var(--text-2);color:var(--text);background:var(--surface2)}
.btn-success{background:var(--green-lt);color:var(--green);border:1px solid var(--green)}
.btn-success:hover{background:var(--green);color:var(--white)}
.btn-danger{background:var(--red-lt);color:var(--red);border:1px solid var(--red)}
.btn-danger:hover{background:var(--red);color:var(--white)}
.btn-sm{padding:6px 13px;font-size:11px}
.btn-xs{padding:4px 9px;font-size:10px}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:var(--border);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:32px}
.stat-card{background:var(--white);padding:22px 24px}
.stat-label{font-size:9px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--text-3);margin-bottom:10px}
.stat-value{font-family:'Cormorant Garamond',serif;font-size:40px;font-weight:300;color:var(--text);line-height:1}
.stat-value.accent{color:var(--accent)}.stat-value.red{color:var(--red)}.stat-value.green{color:var(--green)}.stat-value.blue{color:var(--blue)}
.stat-sub{font-size:11px;color:var(--text-3);margin-top:5px}

/* TABLE */
.table-wrap{background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.tbl{width:100%;border-collapse:collapse}
.tbl th{font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--text-3);padding:13px 18px;text-align:left;background:var(--bg);border-bottom:1px solid var(--border)}
.tbl td{padding:14px 18px;border-bottom:1px solid var(--border);font-size:13px;vertical-align:middle}
.tbl tr:last-child td{border-bottom:none}
.tbl tbody tr{transition:background var(--tr)}
.tbl tbody tr:hover td{background:var(--accent-lt)}
.tbl-link{cursor:pointer}

/* BADGES */
.ref-code{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--accent);font-weight:500}
.badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:2px;font-size:10px;font-weight:600;letter-spacing:.5px;text-transform:uppercase}
.badge-pending{background:var(--blue-lt);color:var(--blue)}
.badge-in-progress{background:var(--accent-lt);color:var(--accent-dk)}
.badge-ready{background:var(--green-lt);color:var(--green)}
.badge-completed{background:var(--surface3);color:var(--text-3)}
.badge-cancelled{background:var(--red-lt);color:var(--red)}
.badge-unpaid{background:var(--red-lt);color:var(--red)}
.badge-partial{background:var(--accent-lt);color:var(--accent-dk)}
.badge-paid{background:var(--green-lt);color:var(--green)}

/* FILTER */
.filter-bar{display:flex;align-items:center;gap:6px;margin-bottom:20px;flex-wrap:wrap}
.ftab{padding:6px 14px;border-radius:2px;border:1px solid var(--border);background:var(--white);color:var(--text-3);font-size:11px;font-weight:500;letter-spacing:.5px;cursor:pointer;transition:all var(--tr);text-transform:uppercase;text-decoration:none;display:inline-block}
.ftab:hover{border-color:var(--border2);color:var(--text-2)}
.ftab.active{background:var(--text);color:var(--white);border-color:var(--text)}
.search-box{margin-left:auto;background:var(--white);border:1px solid var(--border);color:var(--text);padding:7px 14px;border-radius:var(--radius);font-size:12px;outline:none;width:220px;transition:border var(--tr)}
.search-box:focus{border-color:var(--accent)}

/* FORMS */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.form-group{display:flex;flex-direction:column;gap:7px}
.form-group.full{grid-column:1/-1}
.form-label{font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--text-3)}
.form-control{background:transparent;border:none;border-bottom:1.5px solid var(--border2);color:var(--text);padding:8px 0;font-size:13px;outline:none;transition:border-color var(--tr);width:100%;border-radius:0}
.form-control:focus{border-color:var(--accent)}
textarea.form-control{resize:vertical;min-height:72px;border:1px solid var(--border2);border-radius:var(--radius);padding:8px 10px}
textarea.form-control:focus{border-color:var(--accent)}
select.form-control{cursor:pointer;appearance:none;background:transparent}
.form-hint{font-size:11px;color:var(--text-3)}

/* CARD */
.card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px}
.card-title{font-size:9px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--text-3);margin-bottom:16px;display:flex;align-items:center;gap:10px}
.card-title::after{content:'';flex:1;height:1px;background:var(--border)}

/* DETAIL ROWS */
.d-row{display:flex;justify-content:space-between;align-items:baseline;padding:7px 0;border-bottom:1px solid var(--border)}
.d-row:last-child{border-bottom:none}
.d-key{font-size:11px;color:var(--text-3)}
.d-val{font-size:13px;font-weight:500;text-align:right;max-width:60%}

/* NOTIFY */
.notify-card{border:1px solid var(--border);border-radius:var(--radius);padding:16px;position:relative;background:var(--bg)}
.notify-label{font-size:9px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;color:var(--text-3);margin-bottom:8px}
.notify-msg{font-size:12px;color:var(--text-2);line-height:1.7;padding-right:80px}

/* FILE */
.file-drop{border:1.5px dashed var(--border2);border-radius:var(--radius);padding:22px;text-align:center;cursor:pointer;transition:all var(--tr);color:var(--text-3);font-size:12px;letter-spacing:.3px}
.file-drop:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-lt)}
.file-list{margin-top:10px;display:flex;flex-direction:column;gap:6px}
.file-item{display:flex;align-items:center;gap:10px;padding:9px 12px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);font-size:12px}
.file-nm{flex:1;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-2)}
.file-sz{font-size:11px;color:var(--text-3);min-width:56px;text-align:right}
.file-rm{background:none;border:none;color:var(--text-3);cursor:pointer;padding:2px 6px;border-radius:2px}
.file-rm:hover{color:var(--red);background:var(--red-lt)}

/* FLASH */
.flash{padding:12px 18px;border-radius:var(--radius);margin-bottom:24px;font-size:13px;font-weight:500}
.flash-success{background:var(--green-lt);color:var(--green);border:1px solid var(--green)}
.flash-error{background:var(--red-lt);color:var(--red);border:1px solid var(--red)}
.flash-info{background:var(--accent-lt);color:var(--accent-dk);border:1px solid var(--accent)}

/* MISC */
.section-head{font-size:9px;font-weight:600;letter-spacing:2.5px;text-transform:uppercase;color:var(--text-3);margin-bottom:16px;display:flex;align-items:center;gap:10px}
.section-head::after{content:'';flex:1;height:1px;background:var(--border)}
.empty{text-align:center;padding:48px 20px;color:var(--text-3);font-size:13px}
.mono{font-family:'JetBrains Mono',monospace}
.toggle-switch{position:relative;display:inline-block;width:36px;height:20px;cursor:pointer}
.toggle-switch input{opacity:0;width:0;height:0}
.toggle-track{position:absolute;inset:0;background:var(--border2);border-radius:10px;transition:.2s}
.toggle-track::before{content:'';position:absolute;width:12px;height:12px;left:4px;bottom:4px;background:var(--white);border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.15)}
.toggle-switch input:checked+.toggle-track{background:var(--accent)}
.toggle-switch input:checked+.toggle-track::before{transform:translateX(16px)}
.cust-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}
.cust-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);padding:22px;transition:all var(--tr)}
.cust-card:hover{border-color:var(--accent);box-shadow:0 2px 16px rgba(192,123,84,.08)}
.cust-initials{width:38px;height:38px;border-radius:50%;background:var(--accent-lt);border:1px solid var(--accent);display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:17px;font-weight:500;color:var(--accent);margin-bottom:14px}
.cust-name{font-size:15px;font-weight:500;margin-bottom:3px}
.cust-phone{font-size:11px;color:var(--text-3);font-family:'JetBrains Mono',monospace}
.cust-stats{display:flex;gap:20px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border)}
.cust-stat .val{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:400;display:block;color:var(--text)}
.cust-stat .lbl{font-size:9px;text-transform:uppercase;letter-spacing:1px;color:var(--text-3)}
.sum-hero{font-family:'Cormorant Garamond',serif;font-size:64px;font-weight:300;color:var(--accent);line-height:1}
.sum-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--border);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:32px}
.sum-card{background:var(--white);padding:24px 28px}
.sum-card .lbl{font-size:9px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--text-3);margin-bottom:8px}
.od-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
</style>
</head>
<body>
<div class="app">
<nav id="sidebar">
  <div class="sb-head">
    <div class="sb-wordmark">Fleur <em>C</em> Print</div>
    <div class="sb-sub">Admin Panel</div>
  </div>
 <div class="sb-nav">
    <a href="/fleur-c-print/admin/index.php" class="nav-item <?= $active_nav==='dashboard'?'active':'' ?>"><span class="nav-dot"></span> Dashboard</a>
    <a href="/fleur-c-print/admin/queue.php" class="nav-item <?= $active_nav==='queue'?'active':'' ?>"><span class="nav-dot"></span> Order Queue <span class="nav-badge"><?= $badge ?></span></a>
    <a href="/fleur-c-print/admin/new-order.php" class="nav-item <?= $active_nav==='new-order'?'active':'' ?>"><span class="nav-dot"></span> New Order</a>
    <a href="/fleur-c-print/admin/customers.php" class="nav-item <?= $active_nav==='customers'?'active':'' ?>"><span class="nav-dot"></span> Customers</a>
    <div class="sb-section">Admin</div>
    <a href="/fleur-c-print/admin/services.php" class="nav-item <?= $active_nav==='services'?'active':'' ?>"><span class="nav-dot"></span> Services &amp; Prices</a>
    <a href="/fleur-c-print/admin/daily-summary.php" class="nav-item <?= $active_nav==='summary'?'active':'' ?>"><span class="nav-dot"></span> Daily Summary</a>
</div>
  <div class="sb-foot">
    <div class="sb-shop"><strong><?= e(SHOP_NAME) ?></strong>v2.0 · Single Operator</div>
    <form method="POST" action="<?= BASE_URL ?>logout.php">
    <button type="submit" class="btn-logout">Sign Out</button>
</form>
</nav>
<main id="main"><div class="page-wrap">
<?php if ($flash): ?>
<div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
<?php endif; ?>
<?php } // end render_header ?>
