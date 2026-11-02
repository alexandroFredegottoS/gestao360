<?php
require __DIR__ . '/includes/conexao.php';
require __DIR__ . '/includes/funcoes.php';

if (!empty($_SESSION['usuario_id'])) { redirecionar(pagina_inicial()); }

$erro = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $u = null;
    foreach ($dados['usuarios'] as $usuario) {
        if (strcasecmp($usuario['email'], $email) === 0) { $u = $usuario; break; }
    }
    if ($u && password_verify($senha, $u['senha'])) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $u['id'];
        $_SESSION['usuario_nome'] = $u['nome'];
        $_SESSION['usuario_perfil'] = $u['perfil'];
        redirecionar(pagina_inicial());
    }
    $erro = 'E-mail ou senha incorretos. Confira os dados e tente de novo.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Entrar — Gestão 360</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="icon" href="img/logo.png">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-body" data-limpar-credenciais>
  <div class="login-topo">
    <img class="logo logo-grande" src="img/logo.png" alt="Logo Gestão 360" width="96" height="96">
    <h1>Gestão 360</h1>
    <p>Gestão de estoque para o seu negócio</p>
  </div>
  <form class="login-card" method="post" novalidate autocomplete="off">
    <h2>Entrar no sistema</h2>
    <p class="sub">Acesse com suas credenciais de operação</p>
    <?php mostrar_flash(); ?>
    <?php if ($erro): ?><div class="aviso aviso-erro"><?= e($erro) ?></div><?php endif; ?>
    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" value="" placeholder="exemplo@xxxxx.com.br" autocomplete="on" required>
    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" placeholder="••••••••" autocomplete="new-password" required>
    <button class="btn btn-primario btn-bloco" type="submit">Entrar</button>
    <p class="ajuda">Ainda não tem conta? <a href="cadastro.php">Criar conta de cliente</a></p>
  </form>
  <p class="login-rodape">Gestão 360 — Controle simples para o seu comércio</p>
<script src="js/script.js"></script>
</body>
</html>
