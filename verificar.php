<?php
require 'config/db.php';

$email = 'joao@email.com';
$senhaDigitada = '123456';

$stmt = $pdo->prepare("SELECT * FROM clientes WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    echo "<h3>Usuário encontrado:</h3>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Hash no banco: " . $user['senha'] . "<br>";

    if (password_verify($senhaDigitada, $user['senha'])) {
        echo "<p style='color:green;'>✅ Senha correta!</p>";
    } else {
        echo "<p style='color:red;'>❌ Senha incorreta!</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Usuário não encontrado.</p>";
}
?>