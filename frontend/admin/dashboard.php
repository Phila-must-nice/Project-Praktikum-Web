<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin | TalentTrack</title>
</head>
<body data-page="admin">
  <div class="app-shell">
    <aside class="sidebar" id="sidebar">
      <a class="brand" href="admin.html"><span class="brand-mark">T</span><span>Talent<span class="brand-light">Track</span></span></a>
      <div class="sidebar-label">MENU UTAMA</div>
      <nav class="side-nav">
        <a class="nav-link active" href="admin.html"><span class="nav-icon"></span>Dashboard</a>
        <a class="nav-link" href="#lowongan"><span class="nav-icon"></span>Lowongan</a>
        <a class="nav-link" href="#pelamar"><span class="nav-icon"></span>Data Pelamar</a>
      </nav>
      <div class="sidebar-bottom">
        <div class="help-card">
          <div class="help-icon">?</div>
          <strong>Butuh bantuan?</strong>
          <p>Hubungi tim support jika mengalami kendala.</p>
          <a href="mailto: support@talenttrack.test">Hubungi Support</a>
        </div>
        <a class="nav-link logout-link" href="login.html" id="logoutLink"><span class="nav-icon">↪</span>Keluar</a>
      </div>
    </aside>

    <main class="main-content">
      <header class="topbar">
        <button class="menu-toggle" id="menuToggle" aria-label="Buka menu">☰</button>
        <div class="breadcrumb">Halaman <span>/</span> <strong>Dashboard</strong></div>
        <div class="topbar-right">
          <button class="icon-button notification-button" aria-label="Notifikasi">♧<i></i></button>
          <div class="user-menu">
            <div class="avatar avatar-blue">AD</div>
            <div class="user-meta"><strong>Admin HR</strong><span>Administrator</span></div>
            <span class="chevron">⌄</span>
          </div>
        </div>
      </header>

      <section class="page-content">
        <div class="welcome-row">
          <div>
            <p class="eyebrow">SENIN, 9 NOVEMBER 2026</p>
            <h1>Selamat datang, Admin!</h1>
            <p class="muted">Berikut ringkasan aktivitas rekrutmen perusahaan hari ini.</p>
          </div>
          <a class="primary-button" href="#lowongan">＋ Tambah Lowongan</a>
        </div>

        <div class="stats-grid">
          <article class="stat-card">
            <div class="stat-top"><span>Total Lowongan</span><span class="stat-icon blue-soft">▤</span></div>
            <div class="stat-value">12</div><div class="stat-note"><span class="positive">+2</span> dibanding bulan lalu</div>
          </article>
          <article class="stat-card">
            <div class="stat-top"><span>Total Pelamar</span><span class="stat-icon purple-soft">♙</span></div>
            <div class="stat-value">148</div><div class="stat-note"><span class="positive">+18%</span> dibanding bulan lalu</div>
          </article>
          <article class="stat-card">
            <div class="stat-top"><span>Perlu Ditinjau</span><span class="stat-icon orange-soft">◷</span></div>
            <div class="stat-value">24</div><div class="stat-note">Lamaran menunggu pemeriksaan</div>
          </article>
          <article class="stat-card">
            <div class="stat-top"><span>Pelamar Diterima</span><span class="stat-icon green-soft">✓</span></div>
            <div class="stat-value">8</div><div class="stat-note">Pada periode ini</div>
          </article>
        </div>

        <div class="content-grid">
          <section class="panel" id="pelamar">
            <div class="panel-heading">
              <div><h2>Pelamar Terbaru</h2><p class="muted">Pelamar yang baru mengirimkan lamaran.</p></div>
              <a class="text-link" href="#semua-pelamar">Lihat semua <span>→</span></a>
            </div>
            <div class="table-wrap">
              <table>
                <thead><tr><th>NAMA PELAMAR</th><th>POSISI</th><th>TANGGAL</th><th>STATUS</th></tr></thead>
                <tbody>
                  <tr><td><div class="person-cell"><div class="avatar avatar-pink">NA</div><div><strong>Nadia Ananda</strong><small>nadia@email.com</small></div></div></td><td>UI/UX Designer</td><td>09 Nov 2026</td><td><span class="status status-review">Ditinjau</span></td></tr>
                  <tr><td><div class="person-cell"><div class="avatar avatar-yellow">RF</div><div><strong>Rizky Firmansyah</strong><small>rizky@email.com</small></div></div></td><td>Frontend Developer</td><td>08 Nov 2026</td><td><span class="status status-pending">Menunggu</span></td></tr>
                  <tr><td><div class="person-cell"><div class="avatar avatar-green">SP</div><div><strong>Siti Putri</strong><small>siti@email.com</small></div></div></td><td>HR Staff</td><td>08 Nov 2026</td><td><span class="status status-accepted">Diterima</span></td></tr>
                  <tr><td><div class="person-cell"><div class="avatar avatar-purple">DA</div><div><strong>Dimas Aditya</strong><small>dimas@email.com</small></div></div></td><td>UI/UX Designer</td><td>07 Nov 2026</td><td><span class="status status-rejected">Ditolak</span></td></tr>
                </tbody>
              </table>
            </div>
          </section>

          <section class="panel jobs-panel" id="lowongan">
            <div class="panel-heading"><div><h2>Lowongan Aktif</h2><p class="muted">Posisi yang sedang dibuka.</p></div><a class="text-link" href="#semua-lowongan">Lihat semua <span>→</span></a></div>
            <div class="job-mini"><div class="job-symbol symbol-blue">✳</div><div class="job-info"><strong>UI/UX Designer</strong><span>Desain Produk · Full Time</span></div><b>18 <small>pelamar</small></b></div>
            <div class="job-mini"><div class="job-symbol symbol-purple">⌘</div><div class="job-info"><strong>Frontend Developer</strong><span>Teknologi · Full Time</span></div><b>24 <small>pelamar</small></b></div>
            <div class="job-mini"><div class="job-symbol symbol-orange">▣</div><div class="job-info"><strong>HR Staff</strong><span>Human Resource · Kontrak</span></div><b>12 <small>pelamar</small></b></div>
            <a href="#lowongan" class="outline-button full-button">Kelola Lowongan</a>
          </section>
        </div>
        <footer class="footer">© 2026 TalentTrack. All rights reserved.</footer>
      </section>
    </main>
  </div>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <script src="js/script.js"></script>
</body>
</html>
