<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header("Location: login.php");
    exit;
}

include "service/database.php";
$db->query("CREATE TABLE IF NOT EXISTS siswa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nis VARCHAR(30) NOT NULL UNIQUE,
    nama VARCHAR(120) NOT NULL,
    kelas VARCHAR(30) NOT NULL,
    jurusan VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$message = "";
$messageType = "success";
$form = ["id" => "", "nis" => "", "nama" => "", "kelas" => "", "jurusan" => ""];

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $action = $_POST['action'] ?? "";
    if ($action === "delete") {
        $statement = $db->prepare("DELETE FROM siswa WHERE id = ?");
        $statement->bind_param("i", $_POST['id']);
        $statement->execute();
        $statement->close();
        header("Location: dashboard.php?status=deleted");
        exit;
    }

    if ($action === "save") {
        $form = [
            "id" => trim($_POST['id'] ?? ""),
            "nis" => trim($_POST['nis'] ?? ""),
            "nama" => trim($_POST['nama'] ?? ""),
            "kelas" => trim($_POST['kelas'] ?? ""),
            "jurusan" => trim($_POST['jurusan'] ?? "")
        ];

        if (in_array("", [$form['nis'], $form['nama'], $form['kelas'], $form['jurusan']], true)) {
            $message = "Semua data siswa wajib diisi.";
            $messageType = "error";
        } elseif ($form['id'] !== "") {
            $statement = $db->prepare("UPDATE siswa SET nis = ?, nama = ?, kelas = ?, jurusan = ? WHERE id = ?");
            $statement->bind_param("ssssi", $form['nis'], $form['nama'], $form['kelas'], $form['jurusan'], $form['id']);
            if ($statement->execute()) {
                header("Location: dashboard.php?status=updated");
                exit;
            }
            $message = "NIS sudah digunakan siswa lain.";
            $messageType = "error";
            $statement->close();
        } else {
            $statement = $db->prepare("INSERT INTO siswa (nis, nama, kelas, jurusan) VALUES (?, ?, ?, ?)");
            $statement->bind_param("ssss", $form['nis'], $form['nama'], $form['kelas'], $form['jurusan']);
            if ($statement->execute()) {
                header("Location: dashboard.php?status=added");
                exit;
            }
            $message = "NIS sudah digunakan siswa lain.";
            $messageType = "error";
            $statement->close();
        }
    }
}

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $statement = $db->prepare("SELECT id, nis, nama, kelas, jurusan FROM siswa WHERE id = ?");
    $statement->bind_param("i", $id);
    $statement->execute();
    $result = $statement->get_result();
    $form = $result->fetch_assoc() ?: $form;
    $statement->close();
}

if (isset($_GET['status'])) {
    $statusMessages = ["added" => "Data siswa berhasil ditambahkan.", "updated" => "Data siswa berhasil diperbarui.", "deleted" => "Data siswa berhasil dihapus."];
    $message = $statusMessages[$_GET['status']] ?? "";
}

$students = $db->query("SELECT id, nis, nama, kelas, jurusan FROM siswa ORDER BY nama ASC");
$studentCount = $students->num_rows;
function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa | Ruang Siswa</title>
    <style>
        * { box-sizing: border-box; }
        :root { --green: #176b56; --green-dark: #124e40; --ink: #18352d; --muted: #75857e; --line: #e2e9e4; --paper: #fff; --canvas: #f3f6f3; }
        body { margin: 0; min-height: 100vh; background: var(--canvas); color: var(--ink); font-family: "Segoe UI", sans-serif; }
        .topbar { height: 68px; display: flex; align-items: center; justify-content: space-between; padding: 0 5vw; background: var(--green-dark); color: white; }
        .brand { display: flex; align-items: center; gap: 12px; font-size: 17px; font-weight: 700; }
        .crest { width: 34px; height: 34px; display: grid; place-items: center; border: 1px solid #ffffff70; border-radius: 50%; color: #c9ebd8; font-size: 17px; }
        .account { display: flex; align-items: center; gap: 18px; font-size: 14px; }
        .account a { color: #d8eee2; text-decoration: none; }
        main { width: min(1180px, 90%); margin: 40px auto; }
        .heading { display: flex; justify-content: space-between; align-items: end; margin-bottom: 25px; }
        .eyebrow { color: var(--green); font-size: 12px; font-weight: 750; letter-spacing: 1px; text-transform: uppercase; }
        h1 { margin: 6px 0 0; font-size: 30px; }
        .heading p { margin: 7px 0 0; color: var(--muted); }
        .count { min-width: 145px; padding: 14px 18px; border-left: 3px solid #d49b3d; background: white; }
        .count strong { display: block; font-size: 24px; }
        .count span { color: var(--muted); font-size: 12px; }
        .columns { display: grid; grid-template-columns: 300px minmax(0, 1fr); gap: 22px; align-items: start; }
        .panel { background: var(--paper); border: 1px solid var(--line); }
        .panel-head { padding: 17px 20px; border-bottom: 1px solid var(--line); }
        .panel-head h2 { margin: 0; font-size: 16px; }
        .form-body { padding: 18px 20px 20px; }
        label { display: block; margin: 13px 0 6px; font-size: 12px; font-weight: 700; }
        label:first-child { margin-top: 0; }
        input { width: 100%; padding: 10px 11px; border: 1px solid #d6dfd9; border-radius: 3px; color: var(--ink); font: inherit; font-size: 14px; }
        input:focus { outline: 2px solid #b9ddca; border-color: var(--green); }
        .button { display: inline-flex; min-height: 38px; align-items: center; justify-content: center; padding: 0 14px; border: 0; border-radius: 3px; background: var(--green); color: white; font: inherit; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .button:hover { background: var(--green-dark); }
        .button.secondary { background: #edf2ee; color: #405b50; }
        .form-actions { display: flex; gap: 8px; margin-top: 18px; }
        .notice { margin-bottom: 18px; padding: 11px 14px; background: #e5f4ea; color: #246345; font-size: 14px; }
        .notice.error { background: #fff0ed; color: #a13d2f; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
        th { padding: 12px 14px; background: #f7f9f7; color: #64766d; font-size: 11px; letter-spacing: .4px; text-transform: uppercase; }
        td { padding: 14px; border-top: 1px solid var(--line); vertical-align: middle; }
        .student { display: flex; align-items: center; gap: 10px; min-width: 160px; }
        .avatar { width: 32px; height: 32px; display: grid; place-items: center; flex: 0 0 32px; border-radius: 50%; background: #e5f2e9; color: var(--green); font-weight: 700; }
        .student strong { display: block; font-size: 13px; }
        .actions { display: flex; gap: 8px; align-items: center; white-space: nowrap; }
        .text-action { padding: 4px 0; border: 0; background: none; color: var(--green); font: inherit; font-size: 12px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .text-action.delete { color: #a13d2f; }
        .empty { padding: 42px 20px; text-align: center; color: var(--muted); }
        .empty strong { display: block; margin-bottom: 5px; color: var(--ink); }
        footer { padding: 20px 5vw; color: #839189; font-size: 12px; }
        @media (max-width: 800px) { .columns { grid-template-columns: 1fr; } main { margin: 28px auto; } }
        @media (max-width: 520px) { .topbar { padding: 0 5%; } .account span { display: none; } .heading { align-items: start; gap: 14px; flex-direction: column; } h1 { font-size: 26px; } .count { width: 100%; } }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand"><span class="crest">R</span> Ruang Siswa</div>
        <div class="account"><span><?= escape($_SESSION['username']) ?></span><a href="dashboard.php?logout=1">Keluar</a></div>
    </header>
    <main>
        <div class="heading">
            <div><div class="eyebrow">Administrasi sekolah</div><h1>Data Siswa</h1><p>Kelola informasi siswa dalam satu tempat.</p></div>
            <div class="count"><strong><?= $studentCount ?></strong><span>Siswa terdaftar</span></div>
        </div>
        <?php if ($message !== ""): ?>
            <div class="notice <?= $messageType === "error" ? "error" : "" ?>"><?= escape($message) ?></div>
        <?php endif; ?>
        <div class="columns">
            <section class="panel">
                <div class="panel-head"><h2><?= $form['id'] !== "" ? "Edit Siswa" : "Tambah Siswa" ?></h2></div>
                <form class="form-body" method="POST" action="dashboard.php">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= escape($form['id']) ?>">
                    <label for="nis">NIS</label><input id="nis" name="nis" value="<?= escape($form['nis']) ?>" required>
                    <label for="nama">Nama lengkap</label><input id="nama" name="nama" value="<?= escape($form['nama']) ?>" required>
                    <label for="kelas">Kelas</label><input id="kelas" name="kelas" placeholder="Contoh: XII IPA 1" value="<?= escape($form['kelas']) ?>" required>
                    <label for="jurusan">Jurusan</label><input id="jurusan" name="jurusan" placeholder="Contoh: IPA" value="<?= escape($form['jurusan']) ?>" required>
                    <div class="form-actions"><button class="button" type="submit"><?= $form['id'] !== "" ? "Simpan perubahan" : "Tambah siswa" ?></button>
                        <?php if ($form['id'] !== ""): ?><a class="button secondary" href="dashboard.php">Batal</a><?php endif; ?>
                    </div>
                </form>
            </section>
            <section class="panel">
                <div class="panel-head"><h2>Daftar Siswa</h2></div>
                <?php if ($studentCount === 0): ?>
                    <div class="empty"><strong>Belum ada data siswa</strong>Tambahkan siswa pertama melalui formulir.</div>
                <?php else: ?>
                    <div class="table-wrap"><table>
                        <thead><tr><th>Siswa</th><th>NIS</th><th>Kelas</th><th>Jurusan</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php while ($student = $students->fetch_assoc()): ?>
                                <tr>
                                    <td><div class="student"><span class="avatar"><?= escape(strtoupper(substr($student['nama'], 0, 1))) ?></span><strong><?= escape($student['nama']) ?></strong></div></td>
                                    <td><?= escape($student['nis']) ?></td><td><?= escape($student['kelas']) ?></td><td><?= escape($student['jurusan']) ?></td>
                                    <td><div class="actions"><a class="text-action" href="dashboard.php?edit=<?= (int) $student['id'] ?>">Edit</a>
                                        <form method="POST" action="dashboard.php" onsubmit="return confirm('Hapus data siswa ini?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $student['id'] ?>"><button class="text-action delete" type="submit">Hapus</button></form>
                                    </div></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
            </section>
        </div>
    </main>
    <footer>Ruang Siswa · Panel administrasi</footer>
</body>
</html>