<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $year = $_POST['year'];
    $file = $_FILES['file']['tmp_name'];
    $filename = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);

    if (!$file || !$year) {
        echo "Por favor, insira o arquivo e o ano.";
        exit;
    }

    function formatDate($date) {
        return substr($date, 6, 2) . '/' . substr($date, 4, 2) . '/' . substr($date, 0, 4);
    }

    function formatCNPJ($cnpj) {
        return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
    }

    function formatPeriodo($periodo) {
        return substr($periodo, 4, 2) . '/' . substr($periodo, 0, 4);
    }

    function formatValor($valor) {
        return 'R$ ' . number_format((float)$valor / 100, 2, ',', '.');
    }

    $inputFile = fopen($file, 'r');
    $outputFile = fopen($filename . '_corrigido.ret', 'w');
    $txtFile = fopen($filename . '.txt', 'w');

    $lines = [];
    while (($line = fgets($inputFile)) !== false) {
        $lines[] = $line;
    }

    $numLines = count($lines);

    for ($i = 0; $i < $numLines; $i++) {
        if ($i == 0 || $i == $numLines - 1) {
            fwrite($outputFile, $lines[$i]);
            continue;
        }

        $line = $lines[$i];
        $lDataArreca = substr($line, 9, 8);
        $lDataVencto = substr($line, 17, 8);
        $lCNPJ = substr($line, 74, 14);
        $lPeriodo = substr($line, 100, 6);
        $lValorPrinc = substr($line, 106, 17);
        $lValorMulta = substr($line, 123, 17);
        $lValorJuros = substr($line, 140, 17);

        $anoPeriodo = substr($lPeriodo, 0, 4);

        if ($anoPeriodo <= $year) {
            fwrite($outputFile, $line);
        } else {
            $formattedLine = "Data Pagamento: " . formatDate($lDataArreca) . "\n";
            $formattedLine .= "Data Vencimento: " . formatDate($lDataVencto) . "\n";
            $formattedLine .= "CNPJ: " . formatCNPJ($lCNPJ) . "\n";
            $formattedLine .= "Periodo: " . formatPeriodo($lPeriodo) . "\n";
            $formattedLine .= "Valor: " . formatValor(trim($lValorPrinc)) . "\n";
            $formattedLine .= "Juros: " . formatValor(trim($lValorJuros)) . "\n";
            $formattedLine .= "Multa: " . formatValor(trim($lValorMulta)) . "\n";
            $formattedLine .= "\n";

            fwrite($txtFile, $formattedLine);
        }
    }

    fclose($inputFile);
    fclose($outputFile);
    fclose($txtFile);

    $outputFilename = $filename . '_corrigido.ret';
    $txtFilename = $filename . '.txt';

    echo json_encode([
        'outputFile' => $outputFilename,
        'txtFile' => $txtFilename
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>DAF607 | Bauhaus Sistemas</title>
    <link rel="icon" type="image/x-icon" href="https://i.imgur.com/o6zfZZr.png">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body::before {
            content: "";
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background-image: url('https://i.imgur.com/wHLvvt3.jpeg');
            background-repeat: no-repeat;
            transform: scaleX(-1);
            z-index: -1;
        }
        .container {
            background-color: #1d6fbc;
            color: white;
            border-radius: 5px;
            padding: 30px;
            margin-top: 40px;
        }
        .upload-area {
            border-radius: 5px;
            color: #999e9d;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            background-color: #fff;
        }
        .upload-area.dragover {
            background-color: #f1f1f1;
        }
        .modal {
            color: black;
        }
        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #f8f9fa;
            padding: 10px 0;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h4 class="my-4 text-center title">Informe o exercicio e envie o arquivo DAF607 (.ret)</h4>
    <form id="uploadForm" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="year">Ano:</label>
            <input type="text" name="year" id="year" class="form-control" placeholder="Digite o exercicio desejado" required>
        </div>
        <div class="form-group">
            <label for="file">Arquivo .ret:</label>
            <div class="upload-area" id="uploadArea">
                Arraste o arquivo para cá ou clique para selecionar.
            </div>
            <input type="file" name="file" id="file" class="form-control-file" accept=".ret" style="display: none;" required>
            <div id="fileName" class="mt-2"></div>
        </div>
        <button type="submit" class="btn btn-success">Enviar</button>
    </form>

    <!-- Modal -->
    <div class="modal fade" id="resultModal" tabindex="-1" role="dialog" aria-labelledby="resultModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resultModalLabel">Arquivos Gerados</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Processamento concluído. Arquivos gerados:</p>
                    <div class="d-flex justify-content-around">
                        <a href="#" id="downloadRet" download class="btn btn-success mx-1">Download RET Corrigido</a>
                        <a href="#" id="downloadTxt" download class="btn btn-success mx-1">Download TXT</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="bg-transparent text-center py-3">
    <div class="text-center my-4">
        <img src="https://bauhaussistemas.com.br/wp-content/uploads/2022/07/Ativo-1-8.png" alt="Logo" style="max-width: 200px;">
    </div>
    &copy; 2024 Bauhaus Sistemas. Todos os direitos reservados.
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        var uploadArea = $('#uploadArea');
        var fileInput = $('#file');
        var fileNameDisplay = $('#fileName');

        uploadArea.on('click', function() {
            fileInput.click();
        });

        uploadArea.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });

        uploadArea.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });

        uploadArea.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
            var files = e.originalEvent.dataTransfer.files;
            fileInput[0].files = files;
            showFileName(files[0]);
        });

        fileInput.on('change', function() {
            showFileName(this.files[0]);
        });

        function showFileName(file) {
            fileNameDisplay.text('Arquivo selecionado: ' + file.name);
        }

        $('#uploadForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    var data = JSON.parse(response);
                    $('#downloadRet').attr('href', data.outputFile);
                    $('#downloadTxt').attr('href', data.txtFile);
                    $('#resultModal').modal('show');
                }
            });
        });
    });
</script>
</body>
</html>
