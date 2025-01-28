<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $isColaChecked = isset($_POST['cola']); // Verifica se a checkbox "cola" está marcada

    // Usa wp_handle_upload para processar o upload
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $upload_overrides = array('test_form' => false); // Impede que o WordPress faça uma verificação de formulário
    $uploaded_file = wp_handle_upload($_FILES['file'], $upload_overrides);

    if (isset($uploaded_file['error'])) {
        echo 'Erro no upload: ' . $uploaded_file['error'];
    } else {
        // Processa o arquivo apenas se o upload foi bem-sucedido
        // $coresimagem = Functions::verificar_cores_paginas($uploaded_file);
         $sangra = Functions::verificar_sangra($uploaded_file);
        // $resolucao = Functions::verificar_resolucao($uploaded_file);
        // $quantidade = Functions::verificar_qtd_paginas($uploaded_file);
        // $corfonte = Functions::verificar_fontes($uploaded_file);
        // $margemlombo = $isColaChecked ? Functions::verificar_margem_lombo($uploaded_file) : null; // Apenas se "cola" estiver marcada
        // $margemseguranca = Functions::verificar_margem_demais_casos($uploaded_file);
        // $fontepretopagina = Functions::verificar_fontes_preto($uploaded_file);
        $java = Functions::java($uploaded_file);
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preflight</title>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM"
        crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>

<body style="background-color: lightgray">
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <div class="container text-center">
                    <h3>Preflight</h3>
                </div>
            </div>
        </div>
        <div class="row rounded-top pt-3 bg-light pb-3">
            <div class="col">
                <div class="container mt-2">
                    <h2 class="">Upload PDF</h2>
                    <form action="" method="post" enctype="multipart/form-data">
                        <label for="file">Escolha o pdf do material</label>
                        <input type="file" id="file" name="file" accept=".pdf">
                        <br>        
                        <label for="cola">Lombo feito com cola</label>
                        <input type="checkbox" name="cola" id="">
                        <br>

                        <button type="submit" name="submit_arquivo">Enviar</button>
                    </form>
                </div>
            </div>
            <div class="col pt-5">
                <br>
                <div class="text">Quantidade de paginas: <?php echo $quantidade['pagina'] ?? '' ?></div>
                <div class="text">Tamanho do arquivo: <?php echo $quantidade['size'] ?? '' ?> </div>
            </div>
        </div>
        <div class="row bg-light ">
            <div class="col border-top">
                <div class="container pt-3 pb-2">
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
                        // Array com os dados a serem verificados
                        $checks = [
                            'lista-cores-imagem' => [
                                'data' => $coresimagem ?? null,
                                'mensagem' => 'imagens que não estão em cmyk',
                            ],
                            'lista-sangra' => [
                                'data' => $sangra ?? null,
                                'mensagem' => 'páginas sem a devida sangra',
                            ],
                            'lista-resolucao' => [
                                'data' => $resolucao ?? null,
                                'mensagem' => 'imagens sem a devida resolução',
                            ],
                            'lista-cor-fonte' => [
                                'data' => $corfonte ?? null,
                                'mensagem' => 'caixas de texto que não estão em cmyk ',
                            ],
                            'lista-margem-lombo' => [
                                'data' => $margemlombo ?? null,
                                'mensagem' => 'paginas estão sem a margem de segurança do lombo'
                            ],
                            'lista-margem-seguranca' => [
                                'data' => $margemseguranca ?? null,
                                'mensagem' => 'paginas estão sem a margem de segurança'
                            ],
                            'lista-fonte-preto' => [
                                'data' => $fontepretopagina ?? null,
                                'mensagem' => 'caixas de texto que não estão em preto',
                            ],
                            'lista-java' => [
                                'data' => $java ?? null,
                                'mensagem' => 'java',
                            ],
                        ];
                        // Iterar sobre os dados
                        foreach ($checks as $nomeLista => $info) {
                            if (isset($info['data']) && is_array($info['data'])) {
                                echo "<h5><a href='#' class='text-danger funcao-alternar' alternar-nome='{$nomeLista}'>Foram encontradas " . count($info['data']) . " {$info['mensagem']}</a></h5>";
                            } else {
                                echo "<h5 class='text-success'>" . ($info['data'] ?? '') . "</h5>";
                            }
                        }
                    }
                    ?>

                </div>
            </div>
        </div>
        <div class="row bg-light pb-3">
            <div class="col pb-3">
                <div class="container">
                </div>
                <div class="container pt-3 bg-light pb-3" id="lista-cores-imagem" style="display: none;">
                    <?php
                    if (!empty($coresimagem) && is_array($coresimagem)) {
                        echo "<table class='table table-striped table-bordered'>";
                        echo "<thead><tr><th>Imagens que não estão em CMYK</th></tr></thead><tbody>";
                        foreach ($coresimagem as $imagem) {
                            echo "<tr><td>{$imagem}</td></tr>";
                        }
                        echo "</tbody></table>";
                    }
                    ?>
                </div>
                <div class="container pt-3 bg-light pb-3" id="lista-sangra" style="display: none;">
                    <?php
                    if (!empty($sangra) && is_array($sangra)) {
                        echo "<table class='table table-striped table-bordered'>";
                        echo "<thead><tr><th>Páginas sem a devida sangra</th></tr></thead><tbody>";
                        foreach ($sangra as $pagina) {
                            echo "<tr><td>{$pagina}</td></tr>";
                        }
                        echo "</tbody></table>";
                    }
                    ?>
                </div>
                <div class="container pt-3 bg-light pb-3" id="lista-margem-lombo" style="display: none;">
                    <?php
                    if (!empty($margemlombo) && is_array($margemlombo)) {
                        echo "<table class='table table-striped table-bordered'>";
                        echo "<thead><tr><th>Páginas sem a devida margem no lombo</th></tr></thead><tbody>";
                        foreach ($margemlombo as $pagina) {
                            echo "<tr><td>{$pagina}</td></tr>";
                        }
                        echo "</tbody></table>";
                    }
                    ?>
                </div>
                <div class="container pt-3 bg-light pb-3" id="lista-margem-seguranca" style="display: none;">
                    <?php
                    if (!empty($margemseguranca) && is_array($margemseguranca)) {
                        echo "<table class='table table-striped table-bordered'>";
                        echo "<thead><tr><th>Páginas sem a devida margem</th></tr></thead><tbody>";
                        foreach ($margemseguranca as $pagina) {
                            echo "<tr><td>{$pagina}</td></tr>";
                        }
                        echo "</tbody></table>";
                    }
                    ?>
                </div>
                <div class="container pt-3 bg-light pb-3" id="lista-resolucao" style="display: none;">
                    <?php
                    if (!empty($resolucao) && is_array($resolucao)) {
                        echo "<table class='table table-striped table-bordered'>";
                        echo "<thead><tr><th>Imagens sem a devida resolução</th></tr></thead><tbody>";
                        foreach ($resolucao as $imgRes) {
                            echo "<tr><td>{$imgRes}</td></tr>";
                        }
                        echo "</tbody></table>";
                    }
                    ?>
                </div>
                <div class="container pt-3 bg-light pb-3" id="lista-cor-fonte" style="display: none;">
                    <?php
                    if (!empty($corfonte) && is_array($corfonte)) {
                        echo "<table class='table table-striped table-bordered'>";
                        echo "<thead><tr><th>Caixas de texto não em CMYK</th></tr></thead><tbody>";
                        foreach ($corfonte as $fonte) {
                            echo "<tr><td>{$fonte}</td></tr>";
                        }
                        echo "</tbody></table>";
                    }
                    ?>
                </div>
                <div class="container pt-3 bg-light pb-3" id="lista-fonte-preto" style="display: none;">
                    <?php
                    if (!empty($fontepretopagina) && is_array($fontepretopagina)) {
                        echo "<table class='table table-striped table-bordered'>";
                        echo "<thead><tr><th>Caixas de texto não em preto</th></tr></thead><tbody>";
                        foreach ($fontepretopagina as $fonte) {
                            echo "<tr><td>{$fonte}</td></tr>";
                        }
                        echo "</tbody></table>";
                    }
                    ?>

            </div>

            <div class="container pt-3 bg-light pb-3" id="lista-fonte-preto" style="display: block;">
                <?php
                if (!empty($java) && is_array($java)) {
                    echo "<table class='table table-striped table-bordered'>";
                    echo "<thead><tr><th>Caixas de texto não em preto</th></tr></thead><tbody>";
                    foreach ($java as $elements) {
                        echo "<tr><td>{$elements}</td></tr>";
                    }
                    echo "</tbody></table>";
                } else{
                    echo "<h5 class='text-success'>Não é uma array</h5>";
                }
                ?>

        </div>


    </div>
</body>

</html>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        document.body.addEventListener('click', function (event) {
            if (event.target.classList.contains('funcao-alternar')) {
                const tabelaId = event.target.getAttribute('alternar-nome');

                // Ocultar todas as tabelas
                document.querySelectorAll('div[id^="lista-"]').forEach(function (tabela) {
                    tabela.style.display = 'none';
                });

                // Mostrar a tabela correspondente
                if (tabelaId) {
                    const tabela = document.getElementById(tabelaId);
                    if (tabela) {
                        tabela.style.display = 'block';
                    }
                }
            }
        });


        const form = document.querySelector('#upload-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const fileInput = document.querySelector('#file');
            if (!fileInput.files.length) {
                alert('Por favor, selecione um arquivo.');
                return;
            }

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);

            try {
                const response = await fetch('/wp-json/wppreflight/v1/process-file', {
                    method: 'POST',
                    body: formData,
                });

                const result = await response.json();
                if (response.ok) {
                    document.querySelector('#response').innerText = result.message;
                } else {
                    document.querySelector('#response').innerText = `Erro: ${result.message}`;
                }
            } catch (error) {
                document.querySelector('#response').innerText = `Erro inesperado: ${error.message}`;
            }
        });
    });


</script>