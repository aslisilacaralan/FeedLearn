<?php
// config/db.php
// NOT: Şu an XAMPP çalışmasa da raporda "DB katmanı burada" diye gösteriyoruz.
// Gerçek ortamda MySQL bağlantısı yapılacak.

function db_connect() {
    // MOCK: Ders projesinde çalıştırmasanız bile mimariyi göstermek için.
    // Gerçek kullanım:
    // $conn = new mysqli("localhost", "root", "", "feedlearn_db");
    // return $conn;

    return null; // mock
}

// Kullanıcıyı "veritabanına kaydetme" (mock)
function db_create_user($name, $email, $passwordHash, $role = 'student') {
    // Gerçekte INSERT yapılır.
    return true;
}

// Kullanıcıyı "bulma" (mock)
// Demo amaçlı: test için tek kullanıcı kabul edelim.
function db_find_user_by_email($email) {
    if ($email === 'student@test.com') {
        return [
            'id' => 1,
            'name' => 'Test Student',
            'email' => 'student@test.com',
            // şifre: 123456
            'password_hash' => password_hash('123456', PASSWORD_DEFAULT),
            'role' => 'student'
        ];
    }
    return null;
}
?>
