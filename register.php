<?php
include "service/database.php";
if (isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? "");
    $password = $_POST['password'] ?? "";
    $statement = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    if ($statement) {
        $statement->bind_param("ss", $username, $hashedPassword);
        if ($statement->execute()) {
            $message = "Akun berhasil dibuat. Silakan masuk.";
            $messageType = "success";
        } else {
            $message = "Gagal membuat akun. Username mungkin sudah digunakan.";
            $messageType = "error";
        }
        $statement->close();
    } else {
        $message = "Akun belum dapat dibuat.";
        $messageType = "error";
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar | Ruang Siswa</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #f1f5f2; color: #18352d; font-family: "Segoe UI", sans-serif; }
        .login { width: min(100%, 420px); padding: 36px; background: #fff; border-top: 5px solid #23836a; box-shadow: 0 18px 50px #18352d12; }
        .mark { color: #23836a; font-size: 13px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; }
        h1 { margin: 10px 0 6px; font-size: 28px; }
        p { margin: 0 0 24px; color: #687b74; }
        label { display: block; margin: 16px 0 7px; font-size: 13px; font-weight: 650; }
        input { width: 100%; padding: 12px; border: 1px solid #d7e1db; border-radius: 4px; font: inherit; }
        input:focus { outline: 2px solid #a4d9c4; border-color: #23836a; }
        button { width: 100%; margin-top: 22px; padding: 12px; border: 0; border-radius: 4px; background: #176b56; color: white; font: inherit; font-weight: 700; cursor: pointer; }
        .notice { margin-bottom: 18px; padding: 10px 12px; background: #e5f4ea; color: #246345; font-size: 14px; }
        .notice.error { background: #fff0ed; color: #a13d2f; }
        .register { margin-top: 18px; text-align: center; font-size: 14px; }
        a { color: #176b56; }
    </style>
</head>
<body>
    <main class="login">
        <div class="mark">Ruang Siswa</div>
        <h1>Buat akun</h1>
        <p>Daftar untuk mulai mengelola data siswa.</p>
    <?php if (isset($message)): ?>
        <div class="notice <?= $messageType === "error" ? "error" : "" ?>"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>
    <form action="register.php" method="POST">
        <label for="username">Username</label>
        <input id="username" type="text" name="username" autocomplete="username" required>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" autocomplete="new-password" required>
        <button type="submit" name="register">Buat akun</button>
    </form>
        <div class="register">Sudah punya akun? <a href="login.php">Masuk</a></div>
    </main>
</body>
</html>