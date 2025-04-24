<?php
session_start();

// Inicializa estruturas de dados se não existirem
if (!isset($_SESSION['alunos'])) {
    $_SESSION['alunos'] = [];
    $_SESSION['disciplinas'] = [];
    $_SESSION['matriculas'] = [];
}

// Funções de validação
function validarSerie($serie) {
    return $serie >= 5 && $serie <= 9;
}

function alunoExiste($codigo) {
    return isset($_SESSION['alunos'][$codigo]);
}

function disciplinaExiste($codigo) {
    return isset($_SESSION['disciplinas'][$codigo]);
}

function alunoPodeMatricular($codigoAluno) {
    $count = 0;
    foreach ($_SESSION['matriculas'] as $matricula) {
        if ($matricula['codigo_aluno'] == $codigoAluno && empty($matricula['nota_final'])) {
            $count++;
        }
    }
    return $count < 3;
}

function alunoJaCursouDisciplina($codigoAluno, $codigoDisciplina) {
    foreach ($_SESSION['matriculas'] as $matricula) {
        if ($matricula['codigo_aluno'] == $codigoAluno && 
            $matricula['codigo_disciplina'] == $codigoDisciplina) {
            return true;
        }
    }
    return false;
}

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'cadastrar_disciplina':
            $codigo = $_POST['codigo_disciplina'];
            $descricao = $_POST['descricao'];
            $carga_horaria = (int)$_POST['carga_horaria'];
            
            if (disciplinaExiste($codigo)) {
                $mensagem = "Disciplina com este código já existe!";
            } else {
                $_SESSION['disciplinas'][$codigo] = [
                    'descricao' => $descricao,
                    'carga_horaria' => $carga_horaria
                ];
                $mensagem = "Disciplina cadastrada com sucesso!";
            }
            break;
            
        case 'cadastrar_aluno':
            $codigo = $_POST['codigo_aluno'];
            $nome = $_POST['nome_aluno'];
            $serie = (int)$_POST['serie'];
            
            if (alunoExiste($codigo)) {
                $mensagem = "Aluno com este código já existe!";
            } elseif (!validarSerie($serie)) {
                $mensagem = "Série inválida (deve ser entre 5 e 9)!";
            } else {
                $_SESSION['alunos'][$codigo] = [
                    'nome' => $nome,
                    'serie' => $serie
                ];
                $mensagem = "Aluno cadastrado com sucesso!";
            }
            break;
            
        case 'matricular_aluno':
            $codigoAluno = $_POST['codigo_aluno_matricula'];
            $codigoDisciplina = $_POST['codigo_disciplina_matricula'];
            
            if (!alunoExiste($codigoAluno)) {
                $mensagem = "Aluno não encontrado!";
            } elseif (!disciplinaExiste($codigoDisciplina)) {
                $mensagem = "Disciplina não encontrada!";
            } elseif (!alunoPodeMatricular($codigoAluno)) {
                $mensagem = "Aluno já atingiu o limite de 3 disciplinas!";
            } elseif (alunoJaCursouDisciplina($codigoAluno, $codigoDisciplina)) {
                $mensagem = "Aluno já está matriculado nesta disciplina!";
            } else {
                $_SESSION['matriculas'][] = [
                    'codigo_aluno' => $codigoAluno,
                    'codigo_disciplina' => $codigoDisciplina,
                    'total_faltas' => null,
                    'nota_final' => null
                ];
                $mensagem = "Matrícula realizada com sucesso!";
            }
            break;
            
        case 'lancar_resultados':
            $codigoAluno = $_POST['codigo_aluno_resultado'];
            $codigoDisciplina = $_POST['codigo_disciplina_resultado'];
            $faltas = (int)$_POST['faltas'];
            $nota = (float)$_POST['nota'];
            
            $encontrou = false;
            foreach ($_SESSION['matriculas'] as &$matricula) {
                if ($matricula['codigo_aluno'] == $codigoAluno && 
                    $matricula['codigo_disciplina'] == $codigoDisciplina &&
                    $matricula['nota_final'] === null) {
                    
                    $matricula['total_faltas'] = $faltas;
                    $matricula['nota_final'] = $nota;
                    $encontrou = true;
                    break;
                }
            }
            
            $mensagem = $encontrou ? "Resultados lançados com sucesso!" : "Matrícula não encontrada ou resultados já lançados!";
            break;
    }
}

// Função para obter alunos reprovados
function getAlunosReprovados() {
    $reprovados = [];
    
    foreach ($_SESSION['matriculas'] as $matricula) {
        if ($matricula['nota_final'] === null) continue;
        
        $disciplina = $_SESSION['disciplinas'][$matricula['codigo_disciplina']];
        $limiteFaltas = $disciplina['carga_horaria'] * 0.25;
        
        if ($matricula['nota_final'] < 10 || $matricula['total_faltas'] > $limiteFaltas) {
            $aluno = $_SESSION['alunos'][$matricula['codigo_aluno']];
            $reprovados[] = [
                'aluno' => $aluno['nome'],
                'disciplina' => $disciplina['descricao'],
                'nota' => $matricula['nota_final'],
                'faltas' => $matricula['total_faltas'],
                'limite_faltas' => $limiteFaltas
            ];
        }
    }
    
    return $reprovados;
}

// Função para obter disciplinas de um aluno
function getDisciplinasAluno($codigoAluno) {
    $resultado = [];
    
    foreach ($_SESSION['matriculas'] as $matricula) {
        if ($matricula['codigo_aluno'] == $codigoAluno) {
            $disciplina = $_SESSION['disciplinas'][$matricula['codigo_disciplina']];
            $limiteFaltas = $disciplina['carga_horaria'] * 0.25;
            $situacao = "Cursando";
            
            if ($matricula['nota_final'] !== null) {
                $situacao = ($matricula['nota_final'] >= 10 && $matricula['total_faltas'] <= $limiteFaltas) ? 
                    "Aprovado" : "Reprovado";
            }
            
            $resultado[] = [
                'disciplina' => $disciplina['descricao'],
                'faltas' => $matricula['total_faltas'],
                'nota' => $matricula['nota_final'],
                'situacao' => $situacao
            ];
        }
    }
    
    return $resultado;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Matrículas</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card { background: #f5f5f5; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: inline-block; width: 200px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .message { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background-color: #dff0d8; color: #3c763d; }
        .error { background-color: #f2dede; color: #a94442; }
        .info { background-color: #d9edf7; color: #31708f; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .tab-links { margin-bottom: 20px; }
        .tab-link { padding: 10px 15px; background: #eee; border: none; cursor: pointer; }
        .tab-link.active { background: #ddd; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Controle de Matrículas Escolares</h1>
    
    <?php if (!empty($mensagem)): ?>
        <div class="message <?= strpos($mensagem, 'sucesso') !== false ? 'success' : 'error' ?>">
            <?= $mensagem ?>
        </div>
    <?php endif; ?>
    
    <div class="tab-links">
        <button class="tab-link active" onclick="openTab(event, 'cadastros')">Cadastros</button>
        <button class="tab-link" onclick="openTab(event, 'matriculas')">Matrículas</button>
        <button class="tab-link" onclick="openTab(event, 'resultados')">Resultados</button>
        <button class="tab-link" onclick="openTab(event, 'consultas')">Consultas</button>
    </div>
    
    <div id="cadastros" class="tab-content active">
        <div class="card">
            <h2>Cadastrar Disciplina</h2>
            <form method="post">
                <input type="hidden" name="acao" value="cadastrar_disciplina">
                <div class="form-group">
                    <label for="codigo_disciplina">Código da Disciplina:</label>
                    <input type="text" id="codigo_disciplina" name="codigo_disciplina" required>
                </div>
                <div class="form-group">
                    <label for="descricao">Descrição:</label>
                    <input type="text" id="descricao" name="descricao" required>
                </div>
                <div class="form-group">
                    <label for="carga_horaria">Carga Horária:</label>
                    <input type="number" id="carga_horaria" name="carga_horaria" min="1" required>
                </div>
                <button type="submit">Cadastrar</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Cadastrar Aluno</h2>
            <form method="post">
                <input type="hidden" name="acao" value="cadastrar_aluno">
                <div class="form-group">
                    <label for="codigo_aluno">Código do Aluno:</label>
                    <input type="text" id="codigo_aluno" name="codigo_aluno" required>
                </div>
                <div class="form-group">
                    <label for="nome_aluno">Nome do Aluno:</label>
                    <input type="text" id="nome_aluno" name="nome_aluno" required>
                </div>
                <div class="form-group">
                    <label for="serie">Série (5-9):</label>
                    <input type="number" id="serie" name="serie" min="5" max="9" required>
                </div>
                <button type="submit">Cadastrar</button>
            </form>
        </div>
    </div>
    
    <div id="matriculas" class="tab-content">
        <div class="card">
            <h2>Realizar Matrícula</h2>
            <form method="post">
                <input type="hidden" name="acao" value="matricular_aluno">
                <div class="form-group">
                    <label for="codigo_aluno_matricula">Código do Aluno:</label>
                    <select id="codigo_aluno_matricula" name="codigo_aluno_matricula" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($_SESSION['alunos'] as $codigo => $aluno): ?>
                            <option value="<?= $codigo ?>"><?= $codigo ?> - <?= $aluno['nome'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="codigo_disciplina_matricula">Disciplina:</label>
                    <select id="codigo_disciplina_matricula" name="codigo_disciplina_matricula" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($_SESSION['disciplinas'] as $codigo => $disciplina): ?>
                            <option value="<?= $codigo ?>"><?= $codigo ?> - <?= $disciplina['descricao'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Matricular</button>
            </form>
        </div>
    </div>
    
    <div id="resultados" class="tab-content">
        <div class="card">
            <h2>Lançar Resultados Finais</h2>
            <form method="post">
                <input type="hidden" name="acao" value="lancar_resultados">
                <div class="form-group">
                    <label for="codigo_aluno_resultado">Código do Aluno:</label>
                    <select id="codigo_aluno_resultado" name="codigo_aluno_resultado" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($_SESSION['alunos'] as $codigo => $aluno): ?>
                            <option value="<?= $codigo ?>"><?= $codigo ?> - <?= $aluno['nome'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="codigo_disciplina_resultado">Disciplina:</label>
                    <select id="codigo_disciplina_resultado" name="codigo_disciplina_resultado" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($_SESSION['disciplinas'] as $codigo => $disciplina): ?>
                            <option value="<?= $codigo ?>"><?= $codigo ?> - <?= $disciplina['descricao'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="faltas">Total de Faltas:</label>
                    <input type="number" id="faltas" name="faltas" min="0" required>
                </div>
                <div class="form-group">
                    <label for="nota">Nota Final (0-10):</label>
                    <input type="number" id="nota" name="nota" min="0" max="10" step="0.1" required>
                </div>
                <button type="submit">Lançar Resultados</button>
            </form>
        </div>
    </div>
    
    <div id="consultas" class="tab-content">
        <div class="card">
            <h2>Alunos Reprovados</h2>
            <?php $reprovados = getAlunosReprovados(); ?>
            <?php if (!empty($reprovados)): ?>
                <table>
                    <tr>
                        <th>Aluno</th>
                        <th>Disciplina</th>
                        <th>Nota</th>
                        <th>Faltas</th>
                        <th>Limite Faltas</th>
                        <th>Motivo</th>
                    </tr>
                    <?php foreach ($reprovados as $item): ?>
                        <tr>
                            <td><?= $item['aluno'] ?></td>
                            <td><?= $item['disciplina'] ?></td>
                            <td><?= $item['nota'] ?></td>
                            <td><?= $item['faltas'] ?></td>
                            <td><?= $item['limite_faltas'] ?></td>
                            <td>
                                <?= $item['nota'] < 10 ? 'Nota insuficiente' : '' ?>
                                <?= $item['nota'] < 10 && $item['faltas'] > $item['limite_faltas'] ? ' e ' : '' ?>
                                <?= $item['faltas'] > $item['limite_faltas'] ? 'Excesso de faltas' : '' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>Nenhum aluno reprovado encontrado.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Disciplinas por Aluno</h2>
            <form method="post" onsubmit="return false;">
                <div class="form-group">
                    <label for="codigo_aluno_consulta">Aluno:</label>
                    <select id="codigo_aluno_consulta" name="codigo_aluno_consulta" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($_SESSION['alunos'] as $codigo => $aluno): ?>
                            <option value="<?= $codigo ?>"><?= $codigo ?> - <?= $aluno['nome'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" onclick="consultarDisciplinasAluno()">Consultar</button>
            </form>
            
            <div id="resultado-consulta" style="margin-top: 20px;"></div>
        </div>
    </div>
    
    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            
            tablinks = document.getElementsByClassName("tab-link");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
        
        function consultarDisciplinasAluno() {
            var codigoAluno = document.getElementById("codigo_aluno_consulta").value;
            if (!codigoAluno) return;
            
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById("resultado-consulta").innerHTML = this.responseText;
                }
            };
            xhr.open("POST", "consultar_disciplinas.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.send("codigo_aluno=" + codigoAluno);
        }
    </script>
</body>
</html>