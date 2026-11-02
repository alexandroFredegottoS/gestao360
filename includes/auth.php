<?php
// Incluído no início de toda página interna: exige login e carrega o necessário.
session_start();
require __DIR__ . '/funcoes.php';

if (empty($_SESSION['usuario_id'])) {
    redirecionar('index.php');
}

require __DIR__ . '/conexao.php';
require __DIR__ . '/estoque.php';
require __DIR__ . '/loja.php';
