<?php
require '../../config/db.php';
$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM pedidos_suporte WHERE id = ?");
$stmt->execute([$id]);
$pedido = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Pedido</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-5">
    <h4>Editar Pedido #<?= $pedido['id'] ?></h4>
    <form action="../../controllers/atualizar.php" method="POST">
      <input type="hidden" name="id" value="<?= $pedido['id'] ?>">
      <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-select">
          <option <?= $pedido['status'] == 'Pendente' ? 'selected' : '' ?>>Pendente</option>
          <option <?= $pedido['status'] == 'Em andamento' ? 'selected' : '' ?>>Em andamento</option>
          <option <?= $pedido['status'] == 'Concluído' ? 'selected' : '' ?>>Concluído</option>
        </select>
      </div>
      <div class="mb-3">
        <label>Resposta</label>
        <textarea name="resposta" class="form-control"><?= $pedido['resposta'] ?></textarea>
      </div>
      <div class="mb-3">
        <label>Valor (R$)</label>
        <input type="number" step="0.01" name="valor" class="form-control" value="<?= $pedido['valor'] ?>">
      </div>
      <button type="submit" class="btn btn-success">Salvar</button>
    </form>
  </div>
</body>
</html>