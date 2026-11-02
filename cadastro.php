<?php
require __DIR__ . '/includes/conexao.php';
require __DIR__ . '/includes/funcoes.php';
if (!empty($_SESSION['usuario_id'])) redirecionar(pagina_inicial());
$erros = []; $v = ['tipo_pessoa'=>'pf', 'nome'=>'', 'email'=>'', 'cpf_cnpj'=>'', 'data_nascimento'=>'', 'razao_social'=>'', 'nome_fantasia'=>'', 'responsavel'=>'', 'telefone'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($v as $campo => $valor) $v[$campo] = trim($_POST[$campo] ?? '');
  $v['email'] = strtolower($v['email']);
  $senha = $_POST['senha'] ?? ''; $confirmar = $_POST['confirmar'] ?? '';
  if (!in_array($v['tipo_pessoa'], ['pf','pj'], true)) $erros[] = 'Selecione o tipo de cadastro.';
  if ($v['nome'] === '') $erros[] = 'Informe seu nome.';
  if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'Informe um e-mail válido.';
  $documento = preg_replace('/\D/', '', $v['cpf_cnpj']);
  if (($v['tipo_pessoa'] === 'pf' && strlen($documento) !== 11) || ($v['tipo_pessoa'] === 'pj' && strlen($documento) !== 14)) $erros[] = $v['tipo_pessoa'] === 'pj' ? 'Informe um CNPJ com 14 dígitos.' : 'Informe um CPF com 11 dígitos.';
  if ($v['telefone'] === '') $erros[] = 'Informe um telefone para contato.';
  if ($v['tipo_pessoa'] === 'pf' && $v['data_nascimento'] === '') $erros[] = 'Informe sua data de nascimento.';
  if ($v['tipo_pessoa'] === 'pj' && ($v['razao_social'] === '' || $v['responsavel'] === '')) $erros[] = 'Informe a razão social e o responsável pela empresa.';
  foreach ($dados['usuarios'] as $u) {
    if (strcasecmp($u['email'], $v['email']) === 0) $erros[] = 'Este e-mail já possui uma conta.';
    if ($documento !== '' && preg_replace('/\D/', '', $u['cpf_cnpj'] ?? '') === $documento) $erros[] = 'Este CPF/CNPJ já possui uma conta.';
  }
  if (strlen($senha) < 6) $erros[] = 'A senha deve ter ao menos 6 caracteres.';
  if ($senha !== $confirmar) $erros[] = 'As senhas não coincidem.';
  if (!$erros) {
    $id = max(array_column($dados['usuarios'], 'id')) + 1;
    $dados['usuarios'][] = ['id'=>$id, 'nome'=>$v['nome'], 'email'=>$v['email'], 'senha'=>password_hash($senha, PASSWORD_DEFAULT), 'perfil'=>'cliente',
      'tipo_pessoa'=>$v['tipo_pessoa'], 'cpf_cnpj'=>$documento, 'data_nascimento'=>$v['tipo_pessoa'] === 'pf' ? $v['data_nascimento'] : '',
      'razao_social'=>$v['tipo_pessoa'] === 'pj' ? $v['razao_social'] : '', 'nome_fantasia'=>$v['tipo_pessoa'] === 'pj' ? $v['nome_fantasia'] : '',
      'responsavel'=>$v['tipo_pessoa'] === 'pj' ? $v['responsavel'] : '', 'telefone'=>$v['telefone']];
    flash('Conta criada. Agora entre para começar a comprar.'); redirecionar('index.php');
  }
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Criar conta — Gestão 360</title><link rel="stylesheet" href="css/style.css"></head>
<body class="login-body" data-limpar-credenciais><div class="login-topo"><img class="logo logo-grande" src="img/logo.png" alt="Logo Gestão 360"><h1>Gestão 360</h1><p>Crie sua conta de cliente</p></div>
<form class="login-card login-card-cadastro" method="post" autocomplete="off"><h2>Cadastro</h2><p class="sub">Use seus dados para comprar e acompanhar pedidos.</p><?php foreach ($erros as $er): ?><div class="aviso aviso-erro"><?= e($er) ?></div><?php endforeach; ?>
<label for="tipo_pessoa">Tipo de cadastro</label><select id="tipo_pessoa" name="tipo_pessoa"><option value="pf" <?= $v['tipo_pessoa']==='pf'?'selected':'' ?>>Pessoa Física (PF)</option><option value="pj" <?= $v['tipo_pessoa']==='pj'?'selected':'' ?>>Pessoa Jurídica (PJ)</option></select>
<label for="nome">Nome completo *</label><input id="nome" name="nome" value="<?= e($v['nome']) ?>" required>
<label for="cpf_cnpj" id="rotulo-documento">CPF *</label><input id="cpf_cnpj" name="cpf_cnpj" inputmode="numeric" value="<?= e($v['cpf_cnpj']) ?>" required>
<div id="campos-pf"><label for="data_nascimento">Data de nascimento *</label><input type="date" id="data_nascimento" name="data_nascimento" value="<?= e($v['data_nascimento']) ?>"></div>
<div id="campos-pj" hidden><label for="razao_social">Razão social *</label><input id="razao_social" name="razao_social" value="<?= e($v['razao_social']) ?>"><label for="nome_fantasia">Nome</label><input id="nome_fantasia" name="nome_fantasia" value="<?= e($v['nome_fantasia']) ?>"><label for="responsavel">Responsável pela empresa *</label><input id="responsavel" name="responsavel" value="<?= e($v['responsavel']) ?>"></div>
<label for="telefone">Telefone *</label><input id="telefone" name="telefone" inputmode="tel" value="<?= e($v['telefone']) ?>" required>
<label for="email">E-mail</label><input type="email" id="email" name="email" value="" autocomplete="off" required>
<label for="senha">Senha</label><input type="password" id="senha" name="senha" autocomplete="new-password" minlength="6" required>
<label for="confirmar">Confirmar senha</label><input type="password" id="confirmar" name="confirmar" autocomplete="new-password" required>
<button class="btn btn-primario btn-bloco">Criar conta</button><p class="ajuda">Já possui uma conta? <a href="index.php">Entrar</a></p></form><script src="js/script.js"></script></body></html>
