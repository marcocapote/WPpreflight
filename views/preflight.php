<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    // Usa wp_handle_upload para processar o upload
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $upload_overrides = array('test_form' => false); // Impede que o WordPress faça uma verificação de formulário
    $uploaded_file = wp_handle_upload($_FILES['file'], $upload_overrides);

    // Verifica se houve erro no upload
    if (isset($uploaded_file['error'])) {
        echo 'Erro no upload: ' . $uploaded_file['error'];
    } else {
        // Se o upload foi bem-sucedido, verifica o arquivo
        $coresimagem = Functions::verificar_cores_paginas($uploaded_file);
        $sangra = Functions::verificar_sangra($uploaded_file);
        $resolucao = Functions::verificar_resolucao($uploaded_file);
        $quantidade = Functions::verificar_qtd_paginas($uploaded_file);
        $corfonte = Functions::verificar_fontes($uploaded_file);
        $margemlombo = Functions::verificar_margem_lombo($uploaded_file);
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
                    <h3>Preflight </h3>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="container rounded-top bg-light p-3 pl-4">
                    <h2>Upload PDF</h2>
                    <form action="" method="post" enctype="multipart/form-data">
                        <label for="file">Upload PDF</label>
                        <input type="file" id="file" name="file">
                        <button type="submit" name="submit_arquivo">Enviar</button>
                    </form>

                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="container pt-3 bg-light pb-2">
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])){

                   
                    if (isset($coresimagem) && is_array($coresimagem)) {
                        echo "<h5><a href='#' class='text-danger funcao-alternar' alternar-nome='lista-cores-imagem'>Foram encontradas " . count($coresimagem) . " imagens que não estão em cmyk</a></h5>";
                    } else {
                        echo "<h5 class='text-success'>" . $coresimagem . "</h5>";
                    }

                    if (isset($sangra) && is_array($sangra)) {
                        echo "<h5><a href='#' class='text-danger funcao-alternar' alternar-nome='lista-sangra'>Foram encontradas " . count($sangra) . " paginas sem a devida sangra</a></h5>";
                    } else {
                        echo "<h5 class='text-success'>" . $sangra . "</h5>";
                    }

                    if (isset($resolucao) && is_array($resolucao)) {
                        echo "<h5><a href='#' class='text-danger funcao-alternar' alternar-nome='lista-resolucao'>Foram encontradas " . count($resolucao) . " imagens sem a devida resolução</a></h5>";
                    } else {
                        echo "<h5 class='text-success'>" . $resolucao . "</h5>";
                    }

                    if (isset($corfonte) && is_array($corfonte)) {
                        echo "<h5><a href='#' class='text-danger funcao-alternar' alternar-nome='lista-cor-fonte'>Foram encontradas " . count($corfonte) . " caixas de texto que nao estão em cmyk</a></h5>";

                    } else {
                        echo "<h5 class='text-success'>" . $corfonte . "</h5>";
                    }

                }
                    ?>


                </div>
            </div>
        </div>
        <div class="row pb-3">

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

            </div>
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