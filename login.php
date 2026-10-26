<?php
session_start();
include "service/database.php";

if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? "");
    $password = $_POST['password'] ?? "";
    $statement = $db->prepare("SELECT username, password FROM users WHERE username = ? LIMIT 1");

    if ($statement) {
        $statement->bind_param("s", $username);
        $statement->execute();
        $result = $statement->get_result();
        $user = $result->fetch_assoc();
        $passwordMatches = $user && (
            password_verify($password, $user['password']) ||
            hash_equals((string) $user['password'], $password)
        );

        if ($passwordMatches) {
            session_regenerate_id(true);
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.php");
            exit;
        }

        $error = "Username atau password tidak sesuai.";
        $statement->close();
    } else {
        $error = "Akun belum tersedia. Silakan daftar terlebih dahulu.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | Ruang Siswa</title>
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
        .error { padding: 10px 12px; background: #fff0ed; color: #a13d2f; font-size: 14px; }
        .register { margin-top: 18px; text-align: center; font-size: 14px; }
        a { color: #176b56; }
    </style>
</head>
<body>
    <main class="login">
        <div class="mark">Ruang Siswa</div>
        <h1>Selamat datang</h1>
        <p>Masuk untuk mengelola data siswa.</p>
        <?php if ($error !== ""): ?>
            <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <label for="username">Username</label>
            <input id="username" type="text" name="username" autocomplete="username" required>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
            <button type="submit" name="login">Masuk ke dashboard</button>
        </form>
        <div class="register">Belum punya akun? <a href="register.php">Daftar</a></div>
    </main>
</body>
</html>