<?php
// Funções auxiliares gerais: texto, datas, mensagens e permissões.
// As regras do estoque ficam em includes/estoque.php.

date_default_timezone_set('America/Sao_Paulo');

// Escapa texto antes de mostrar no HTML (evita injeção de código)
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function redirecionar(string $url): void {
    header('Location: ' . $url);
    exit;
}

// ---- Datas ----
function data_br($d): string { return date('d/m/Y', strtotime($d)); }

function data_extenso(): string {
    $dias = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
    $meses = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    return $dias[date('w')] . ', ' . date('j') . ' de ' . $meses[(int)date('n')] . ' de ' . date('Y');
}

// ---- Mensagens que sobrevivem a um redirecionamento ----
function flash(string $msg, string $tipo = 'ok'): void { $_SESSION['flash'] = [$msg, $tipo]; }

function mostrar_flash(): void {
    if (empty($_SESSION['flash'])) return;
    [$msg, $tipo] = $_SESSION['flash'];
    echo '<div class="aviso aviso-' . e($tipo) . '" role="status">' . e($msg) . '</div>';
    unset($_SESSION['flash']);
}

// ---- Permissões ----
function eh_admin(): bool { return ($_SESSION['usuario_perfil'] ?? '') === 'admin'; }
function eh_cliente(): bool { return ($_SESSION['usuario_perfil'] ?? '') === 'cliente'; }
function eh_caixa(): bool { return ($_SESSION['usuario_perfil'] ?? '') === 'caixa'; }

function pagina_inicial(): string { return eh_cliente() ? 'loja.php' : (eh_caixa() ? 'caixa.php' : 'dashboard.php'); }

function exigir_admin(): void {
    if (!eh_admin()) {
        flash('Só administradores podem acessar a tela de usuários.', 'erro');
        redirecionar(pagina_inicial());
    }
}

function exigir_operacao(): void {
    if (eh_cliente()) {
        flash('Esta área é exclusiva para a equipe da loja.', 'erro');
        redirecionar('loja.php');
    }
}

function exigir_estoque(): void {
    if (eh_caixa()) {
        flash('O perfil Caixa possui acesso somente às vendas no balcão.', 'erro');
        redirecionar('saida.php');
    }
}

function exigir_caixa(): void {
    if (!eh_caixa() && !eh_admin()) {
        flash('Esta área é exclusiva para o perfil Caixa.', 'erro');
        redirecionar(pagina_inicial());
    }
}
