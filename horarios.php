<?php
require_once 'classes/db.class.php';

// Função para obter todos os revezamentos
function getRevezamentos() {
    $db = DB::connect();
    $query = "SELECT he.*, o.nome_otario, p.nome_proj 
              FROM horarios_estande he
              JOIN otarios o ON he.id_otario = o.id_otario
              JOIN projetos p ON he.id_estande = p.id_estande
              ORDER BY he.horario_inicio";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Processar o formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = DB::connect();

    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $id_estande = $_POST['id_estande'];
            $id_otario = $_POST['id_otario'];
            $horario_inicio = $_POST['horario_inicio'];
            $horario_fim = $_POST['horario_fim'];

            if ($_POST['action'] == 'add') {
                // Verificar se já existe um revezamento com os mesmos dados
                $queryCheck = "SELECT COUNT(*) FROM horarios_estande WHERE id_estande = ? AND id_otario = ? AND horario_inicio = ? AND horario_fim = ?";
                $stmtCheck = $db->prepare($queryCheck);
                $stmtCheck->execute([$id_estande, $id_otario, $horario_inicio, $horario_fim]);
                $count = $stmtCheck->fetchColumn();

                if ($count > 0) {
                    $mensagem = "Já existe um revezamento cadastrado para este horário.";
                } else {
                    $query = "INSERT INTO horarios_estande (id_estande, id_otario, horario_inicio, horario_fim) VALUES (?, ?, ?, ?)";
                    $message = "Revezamento cadastrado com sucesso!";
                    $params = [$id_estande, $id_otario, $horario_inicio, $horario_fim];

                    $stmt = $db->prepare($query);
                    if ($stmt->execute($params)) {
                        $mensagem = $message;
                    } else {
                        $mensagem = "Erro ao processar o revezamento.";
                    }
                }
            } else {
                // Atualização de um revezamento existente
                $id = $_POST['id'];
                $query = "UPDATE horarios_estande SET id_estande = ?, id_otario = ?, horario_inicio = ?, horario_fim = ? WHERE id_horario = ?";
                $message = "Revezamento atualizado com sucesso!";
                $params = [$id_estande, $id_otario, $horario_inicio, $horario_fim, $id];

                $stmt = $db->prepare($query);
                if ($stmt->execute($params)) {
                    $mensagem = $message;
                } else {
                    $mensagem = "Erro ao processar o revezamento.";
                }
            }
        } elseif ($_POST['action'] == 'delete') {
            $id = $_POST['id'];
            $query = "DELETE FROM horarios_estande WHERE id_horario = ?";
            $stmt = $db->prepare($query);
            if ($stmt->execute([$id])) {
                $mensagem = "Revezamento excluído com sucesso!";
            } else {
                $mensagem = "Erro ao excluir o revezamento.";
            }
        }
    }
}

// Buscar todos os revezamentos
$revezamentos = getRevezamentos();

// Buscar todos os estandes e otários para o formulário
$db = DB::connect();
$estandes = $db->query("SELECT id_estande, nome_proj FROM projetos")->fetchAll(PDO::FETCH_ASSOC);
$otarios = $db->query("SELECT id_otario, nome_otario FROM otarios")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revezamento de Horários</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .container {
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        h1, h2 {
            color: #343a40;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .modal-header {
            background-color: #007bff;
            color: white;
        }
        @media (max-width: 768px) {
            .form-group {
                margin-bottom: 15px;
            }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Revezamento de Horários</h1>

        <?php if (isset($mensagem)): ?>
            <div class="alert alert-info"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <form method="POST" class="mb-4">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="id_estande">Estande:</label>
                    <select name="id_estande" class="form-control" required>
                        <?php foreach ($estandes as $estande): ?>
                            <option value="<?php echo $estande['id_estande']; ?>"><?php echo $estande['nome_proj']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="id_otario">Usuário:</label>
                    <select name="id_otario" class="form-control" required>
                        <?php foreach ($otarios as $otario): ?>
                            <option value="<?php echo $otario['id_otario']; ?>"><?php echo $otario['nome_otario']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="horario_inicio">Início:</label>
                    <input type="time" name="horario_inicio" class="form-control" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="horario_fim">Fim:</label>
                    <input type="time" name="horario_fim" class="form-control" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Cadastrar Revezamento</button>
        </form>

        <h2 class="mb-3">Revezamentos Cadastrados</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Estande</th>
                    <th>Usuário</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revezamentos as $revezamento): ?>
                    <tr>
                        <td><?php echo $revezamento['nome_proj']; ?></td>
                        <td><?php echo $revezamento['nome_otario']; ?></td>
                        <td><?php echo $revezamento['horario_inicio']; ?></td>
                        <td><?php echo $revezamento['horario_fim']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning edit-btn" data-id="<?php echo $revezamento['id_horario']; ?>" 
                                    data-estande="<?php echo $revezamento['id_estande']; ?>" 
                                    data-otario="<?php echo $revezamento['id_otario']; ?>" 
                                    data-inicio="<?php echo $revezamento['horario_inicio']; ?>" 
                                    data-fim="<?php echo $revezamento['horario_fim']; ?>">
                                Editar
                            </button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $revezamento['id_horario']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este revezamento?')">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal de Edição -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Editar Revezamento</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="form-group">
                            <label for="edit-id_estande">Estande:</label>
                            <select name="id_estande" id="edit-id_estande" class="form-control" required>
                                <?php foreach ($estandes as $estande): ?>
                                    <option value="<?php echo $estande['id_estande']; ?>"><?php echo $estande['nome_proj']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit-id_otario">Usuário:</label>
                            <select name="id_otario" id="edit-id_otario" class="form-control" required>
                                <?php foreach ($otarios as $otario): ?>
                                    <option value="<?php echo $otario['id_otario']; ?>"><?php echo $otario['nome_otario']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit-horario_inicio">Horário de Início:</label>
                            <input type="time" name="horario_inicio" id="edit-horario_inicio" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-horario_fim">Horário de Fim:</label>
                            <input type="time" name="horario_fim" id="edit-horario_fim" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('.edit-btn').click(function() {
                var id = $(this).data('id');
                var estande = $(this).data('estande');
                var otario = $(this).data('otario');
                var inicio = $(this).data('inicio');
                var fim = $(this).data('fim');

                $('#edit-id').val(id);
                $('#edit-id_estande').val(estande);
                $('#edit-id_otario').val(otario);
                $('#edit-horario_inicio').val(inicio);
                $('#edit-horario_fim').val(fim);

                $('#editModal').modal('show');
            });
        });
    </script>
</body>
</html>
