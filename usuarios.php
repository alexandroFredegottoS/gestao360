<?php
require __DIR__ . '/includes/auth.php';
exigir_operacao();
exigir_estoque();
exigir_admin();

$erros = [];
$v = ['nome' => '', 'email' => '', 'perfil' => 'operador', 'tipo_pessoa'=>'pf', 'cpf_cnpj'=>'', 'data_nascimento'=>'', 'razao_social'=>'', 'nome_fantasia'=>'', 'responsavel'=>'', 'telefone'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        foreach ($v as $campo => $valor) $v[$campo] = trim($_POST[$campo] ?? '');
        $v['email'] = strtolower($v['email']);
        $senha = $_POST['senha'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        if ($v['nome'] === '') $erros[] = 'Informe o nome do usuário.';
        if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'Informe um e-mail válido.';
        else {
            foreach ($dados['usuarios'] as $usuario) {
                if (strcasecmp($usuario['email'], $v['email']) === 0) { $erros[] = 'Já existe um usuário com esse e-mail.'; break; }
            }
        }
        if (!in_array($v['perfil'], ['admin', 'operador', 'caixa', 'cliente'], true)) $erros[] = 'Perfil inválido.';
        $documento = preg_replace('/\D/', '', $v['cpf_cnpj']);
        if ($v['perfil'] === 'cliente') {
            if (!in_array($v['tipo_pessoa'], ['pf', 'pj'], true)) $erros[] = 'Selecione PF ou PJ para o cliente.';
            if (($v['tipo_pessoa'] === 'pf' && strlen($documento) !== 11) || ($v['tipo_pessoa'] === 'pj' && strlen($documento) !== 14)) $erros[] = $v['tipo_pessoa'] === 'pj' ? 'Informe um CNPJ com 14 dígitos.' : 'Informe um CPF com 11 dígitos.';
            if ($v['telefone'] === '') $erros[] = 'Informe o telefone do cliente.';
            if ($v['tipo_pessoa'] === 'pf' && $v['data_nascimento'] === '') $erros[] = 'Informe a data de nascimento do cliente.';
            if ($v['tipo_pessoa'] === 'pj' && ($v['razao_social'] === '' || $v['responsavel'] === '')) $erros[] = 'Informe a razão social e o responsável pela empresa.';
            foreach ($dados['usuarios'] as $usuario) if ($documento !== '' && preg_replace('/\D/', '', $usuario['cpf_cnpj'] ?? '') === $documento) { $erros[] = 'Este CPF/CNPJ já possui uma conta.'; break; }
        }
        if (strlen($senha) < 6) $erros[] = 'A senha precisa ter pelo menos 6 caracteres.';
        if ($senha !== $confirmar) $erros[] = 'As senhas não são iguais.';

        if (!$erros) {
            $novoId = count($dados['usuarios']) + 1;
            $dados['usuarios'][] = ['id'=>$novoId, 'nome'=>$v['nome'], 'email'=>$v['email'],
                'senha'=>password_hash($senha, PASSWORD_DEFAULT), 'perfil'=>$v['perfil'],
                'tipo_pessoa'=>$v['perfil'] === 'cliente' ? $v['tipo_pessoa'] : '', 'cpf_cnpj'=>$v['perfil'] === 'cliente' ? $documento : '',
                'data_nascimento'=>$v['perfil'] === 'cliente' && $v['tipo_pessoa'] === 'pf' ? $v['data_nascimento'] : '',
                'razao_social'=>$v['perfil'] === 'cliente' && $v['tipo_pessoa'] === 'pj' ? $v['razao_social'] : '', 'nome_fantasia'=>$v['perfil'] === 'cliente' && $v['tipo_pessoa'] === 'pj' ? $v['nome_fantasia'] : '',
                'responsavel'=>$v['perfil'] === 'cliente' && $v['tipo_pessoa'] === 'pj' ? $v['responsavel'] : '', 'telefone'=>$v['perfil'] === 'cliente' ? $v['telefone'] : ''];
            flash('Usuário ' . $v['nome'] . ' criado. Ele já pode entrar no sistema.');
            redirecionar('usuarios.php');
        }
    }

    if ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['usuario_id']) {
            flash('Você não pode excluir o próprio usuário.', 'erro');
        } else {
            $dados['usuarios'] = array_values(array_filter($dados['usuarios'], fn($usuario) => $usuario['id'] !== $id));
            flash('Usuário excluído.');
        }
        redirecionar('usuarios.php');
    }
}

$usuarios = array_values($dados['usuarios']);
usort($usuarios, fn($a, $b) => strcasecmp($a['nome'], $b['nome']));

$pagina = 'usuarios'; $titulo = 'Usuários';
include __DIR__ . '/includes/header.php';
?>
<h1 class="titulo">Usuários</h1>
<p class="subtitulo">Cadastre quem pode acessar o sistema de estoque</p>

<section class="duas-colunas">
  <div class="painel">
    <h2>Usuários cadastrados (<?= count($usuarios) ?>)</h2>
    <?php foreach ($usuarios as $u): ?>
      <div class="linha-entrada">
        <div class="nome-usuario">
          <span class="mini-avatar"><?= e(strtoupper(substr($u['nome'], 0, 2))) ?></span>
          <div>
            <b><?= e($u['nome']) ?><?php if ($u['id'] == $_SESSION['usuario_id']): ?><span class="voce">você</span><?php endif; ?></b>
            <small><?= e($u['email']) ?> · <?= $u['perfil'] === 'admin' ? 'Administrador' : ($u['perfil'] === 'cliente' ? 'Cliente · ' . strtoupper($u['tipo_pessoa'] ?? 'não informado') : ($u['perfil'] === 'caixa' ? 'Caixa (vendas presenciais)' : 'Operador')) ?></small>
          </div>
        </div>
        <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
          <form method="post" class="form-excluir">
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <button class="btn-perigo" type="submit">Excluir</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <form method="post" class="painel form" id="form-usuario" novalidate autocomplete="off">
    <input type="hidden" name="acao" value="criar">
    <h2>Novo usuário</h2>
    <?php foreach ($erros as $er): ?><div class="aviso aviso-erro"><?= e($er) ?></div><?php endforeach; ?>
    <label for="nome">Nome *</label>
    <input id="nome" name="nome" value="<?= e($v['nome']) ?>" required>
    <label for="email">E-mail *</label>
    <input type="email" id="email" name="email" placeholder="exemplo@xxxxx.com.br" value="" autocomplete="off" required>
    <label for="perfil">Perfil *</label>
    <select id="perfil" name="perfil">
      <option value="operador" <?= $v['perfil'] === 'operador' ? 'selected' : '' ?>>Operador (usa o estoque)</option>
      <option value="caixa" <?= $v['perfil'] === 'caixa' ? 'selected' : '' ?>>Caixa (registra vendas no balcão)</option>
      <option value="admin" <?= $v['perfil'] === 'admin' ? 'selected' : '' ?>>Administrador (também gerencia usuários)</option>
      <option value="cliente" <?= $v['perfil'] === 'cliente' ? 'selected' : '' ?>>Cliente (acessa a loja virtual)</option>
    </select>
    <div id="campos-cliente-admin" hidden>
      <label for="tipo_pessoa">Tipo de cadastro *</label>
      <select id="tipo_pessoa" name="tipo_pessoa"><option value="pf" <?= $v['tipo_pessoa'] === 'pf' ? 'selected' : '' ?>>Pessoa Física (PF)</option><option value="pj" <?= $v['tipo_pessoa'] === 'pj' ? 'selected' : '' ?>>Pessoa Jurídica (PJ)</option></select>
      <label for="cpf_cnpj" id="rotulo-documento">CPF *</label>
      <input id="cpf_cnpj" name="cpf_cnpj" inputmode="numeric" value="<?= e($v['cpf_cnpj']) ?>">
      <div id="campos-pf"><label for="data_nascimento">Data de nascimento *</label><input type="date" id="data_nascimento" name="data_nascimento" value="<?= e($v['data_nascimento']) ?>"></div>
      <div id="campos-pj" hidden><label for="razao_social">Razão social *</label><input id="razao_social" name="razao_social" value="<?= e($v['razao_social']) ?>"><label for="nome_fantasia">Nome</label><input id="nome_fantasia" name="nome_fantasia" value="<?= e($v['nome_fantasia']) ?>"><label for="responsavel">Responsável pela empresa *</label><input id="responsavel" name="responsavel" value="<?= e($v['responsavel']) ?>"></div>
      <label for="telefone">Telefone *</label><input id="telefone" name="telefone" inputmode="tel" value="<?= e($v['telefone']) ?>">
    </div>
    <label for="senha">Senha * (mínimo 6 caracteres)</label>
    <input type="password" id="senha" name="senha" placeholder="••••••••" autocomplete="new-password" minlength="6" required>
    <label for="confirmar">Confirmar senha *</label>
    <input type="password" id="confirmar" name="confirmar" placeholder="••••••••" autocomplete="new-password" required>
    <button class="btn btn-primario btn-bloco" type="submit">Criar usuário</button>
  </form>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
