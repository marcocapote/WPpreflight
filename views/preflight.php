<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $isColaChecked = true; // Verifica se a checkbox "cola" está marcada

    // Usa wp_handle_upload para processar o upload
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $upload_overrides = array('test_form' => false); // Impede que o WordPress faça uma verificação de formulário
    $uploaded_file = wp_handle_upload($_FILES['file'], $upload_overrides);

    if (isset($uploaded_file['error'])) {
        echo 'Erro no upload: ' . $uploaded_file['error'];
    } else {
        // Processa o arquivo apenas se o upload foi bem-sucedido
        // $coresimagem = Functions::verificar_cores_paginas($uploaded_file);

        $functions = new Functions();

        //Verifica se as sangras estão settadas
        $sangra = $functions->verificar_sangra($uploaded_file);

        //cama a função marginElement para processar os elementos que estao em area de risco e dividi-los para MSI (margem de segurança interna) e MSE (margem de segurança externa)
        $functions->marginElement($uploaded_file);

        //Pega os elementos detectados pela função marginElement
        $getSangra = !is_array($sangra) ? $functions->getListaMSE() : null;

        //Verifica se as margens de segurança estão settadas
        $margemseguranca = Functions::verificar_margem($uploaded_file, $isColaChecked);

        //Verifica se as imagens estão com a resolução correta
        $resolucao = Functions::java_verificar_resolucao($uploaded_file);

        //Pega os elementos detectados pela função marginElement
        $getMSI = !empty($functions->getListaMSI()) ? $functions->getListaMSI() : null;

        //Puxa a quantidade de paginas e o tamanho do arquivo
        $quantidade = Functions::verificar_qtd_paginas($uploaded_file);

        //Verifica se as fontes estão no padrão CMYK
        $corfonte = Functions::corFontes($uploaded_file);

        //verifica se os elementos graficos estão no padrão CMYK
        $corElemento = Functions::corElemento($uploaded_file);

        // $margemlombo = $isColaChecked ? Functions::verificar_margem_lombo($uploaded_file) : null; // Apenas se "cola" estiver marcada

        //verifica se as fontes dentro dos elementos graficos vão aparecer na impressao
        $fontElement = Functions::fontElement($uploaded_file);

        //verifica se as fontes pretas estão manchadas
        $javaFontePreta = Functions::javaFontePreta($uploaded_file);

    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preflight Beta 1.3</title>

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







        <div class="row mb-3">
            <div class="col-9">
                <div class="text-right mt-4 mr-0 ">
                    <h3>Preflight Beta 1.3.2</h3>
                </div>
            </div>
            <div class="col-3">
                <div class="text-right ">
                    <img src="<?php echo plugin_dir_url(__FILE__) . '../src/logo_maxi_250.png'; ?>" width="220"
                        height="220">

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
                        <br>

                        <!-- <input type="checkbox" name="cola" id="" value="true">
                        <label for="cola">Acabamento com cola | Lombada quadrada</label>
                            <br>
                        <br> -->

                        <button type="submit" name="submit_arquivo">Enviar</button>
                    </form>
                </div>
            </div>
            <div class="col pt-5">
                <br>
                <div class="text">Quantidade de paginas: <?php echo $quantidade['pagina'] ?? 'nao encontrado' ?></div>
                <div class="text">Tamanho do arquivo: <?php echo $quantidade['size'] ?? ' nao encontrado' ?> </div>
            </div>
        </div>
        <div class="row bg-light ">
            <div class="col border-top">
                <div class="container pt-3 pb-2">
                    <table>
                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
                            // Array com os dados a serem verificados
                            $checks = [
                                'lista-sangra' => [
                                    'data' => $sangra ?? null,
                                    'mensagem' => 'páginas com problemas na configuração da sangria, e requerem sua atenção.',
                                    'titulo' => 'Sangria',
                                    'modal' => 'Sangria',
                                    'descricao' => file_get_contents(plugin_dir_path(__FILE__) . '../src/txt/sangra.txt'),
                                ],
                                'lista-get-sangra' => [
                                    'data' => $getSangra ?? null,
                                    'mensagem' => 'elementos com pouca ou nenhuma sangria, e requerem sua atenção.',
                                    'titulo' => 'Sangria dos elementos',
                                    'modal' => 'SangriaElementos',
                                    'descricao' => file_get_contents(plugin_dir_path(__FILE__) . '../src/txt/sangraElementos.txt'),
                                ],
                                'lista-margem-seguranca' => [
                                    'data' => $margemseguranca ?? null,
                                    'mensagem' => 'páginas em que a margem de segurança não está configurada (opcional).',
                                    'titulo' => 'Margem de segurança',
                                    'modal' => 'MargemSeguranca',
                                    'descricao' => file_get_contents(plugin_dir_path(__FILE__) . '../src/txt/margemSeguranca.txt'),
                                ],
                                'lista-get-MSI' => [
                                    'data' => $getMSI ?? null,
                                    'mensagem' => 'elementos que possivelmente devem ser sangrados, e requerem sua atenção.',
                                    'titulo' => 'Margem de segurança dos elementos',
                                    'modal' => 'MargemSegurancaElementos',
                                    'descricao' => file_get_contents(plugin_dir_path(__FILE__) . '../src/txt/margemSegurancaElementos.txt'),
                                ],
                                'lista-resolucao' => [
                                    'data' => $resolucao ?? null,
                                    'mensagem' => 'imagens abaixo da resolução recomendada. Verificar a qualidade as imagens listadas',
                                    'titulo' => 'Imagem/Resolução',
                                    'modal' => 'Resolucao',
                                    'descricao' => file_get_contents(plugin_dir_path(__FILE__) . '../src/txt/resolucao.txt'),
                                ],
                                'lista-cor-fonte' => [
                                    'data' => $corfonte ?? null,
                                    'mensagem' => 'caixas de texto que não estão no padrão CMYK',
                                    'titulo' => 'Espaço de cor/Fonte',
                                    'modal' => 'CorFonte',
                                    'descricao' => file_get_contents(plugin_dir_path(__FILE__) . '../src/txt/corFonte.txt'),
                                ],
                                'lista-java' => [
                                    'data' => $corElemento ?? null,
                                    'mensagem' => 'elementos que não estão no padrão CMYK.',
                                    'titulo' => 'Espaço de cor/Imagens e vetores',
                                    'modal' => 'CorElemento',
                                    'descricao' => file_get_contents(plugin_dir_path(__FILE__) . '../src/txt/corElemento.txt'),
                                ],
                                'lista-java-fonte-preta' => [
                                    'data' => $javaFontePreta ?? null,
                                    'mensagem' => 'fontes visualmente pretas manchadas com outras cores',
                                    'titulo' => 'Fonte preta manchada',
                                    'modal' => 'FontePreta',
                                    'descricao' => file_get_contents(plugin_dir_path(__FILE__) . '../src/txt/fontePreta.txt'),
                                ],
                                'lista-fonte-elemento' => [
                                    'data' => $fontElement ?? null,
                                    'mensagem' => 'fontes que podem apresentar problemas na impressao.',
                                    'titulo' => 'Visibilidade da fonte',
                                    'modal' => 'VisibilidadeFonte',
                                    'descricao' => file_get_contents(plugin_dir_path(__FILE__) . '../src/txt/visibilidadeFonte.txt'),
                                ],

                            ];
                            // Iterar sobre os dados
                            foreach ($checks as $nomeLista => $info) {
                                echo "
                                                                            <!-- Modal -->
                                            <div class='modal fade' id='exampleModal-{$info['modal']}' tabindex='-1' role='dialog' aria-labelledby='exampleModalLabel'
                                                aria-hidden='true'>
                                                <div class='modal-dialog' role='document'>
                                                    <div class='modal-content'>
                                                        <div class='modal-header'>
                                                            <h5 class='modal-title' id='exampleModalLabel'>{$info['titulo']}</h5>
                                                            <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                                <span aria-hidden='true'>&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class='modal-body'>
                                                            {$info['descricao']}
                                                        </div>
                                                        <div class='modal-footer'>
                                                            <button type='button' class='btn btn-secondary' data-dismiss='modal'>Fechar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                ";
                                if (isset($info['data']) && is_array($info['data']) && !empty($info['data'])) {
                                    echo "





                                             <tr>
                                                <td>
                                                <div class='d-flex flex-row'>
                                                    <h6>{$info['titulo']}</h6>
                                                    
                                                    <a class='ml-auto text-dark' data-toggle='modal' data-target='#exampleModal-{$info['modal']}' href='#'>
                                                    <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-info-circle ml-auto' viewBox='0 0 16 16'>
                                                        <path d='M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16'/>
                                                        <path d='m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0'/>
                                                    </svg>
                                                    </a>
                                                </div>
                                                </td>
                                                <td width='70%' class='p-0 m-0'>   
                                                    <a href='#' class='d-flex container-fluid flex-row funcao-alternar text-danger p-3' data-target='{$nomeLista}'> 
                                                        <div class='row d-flex w-100'>
                                                            <div class='col-10 text-left'>
                                                                <b> " . count($info['data']) . " {$info['mensagem']} </b>
                                                            </div>
                                                            <div class='col-2 d-flex justify-content-end align-items-center' style='width: 70px;'>
                                                                <div id='olho'>
                                                                    <svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='currentColor' class='bi bi-eye text-dark mr-2' viewBox='0 0 16 16'>
                                                                        <path d='M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z'/>
                                                                        <path d='M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0'/>
                                                                    </svg>
                                                                </div>
                                                                <div class='erro'>
                                                                    <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='text-danger bi bi-x-square mr-2' viewBox='0 0 16 16'>
                                                                        <path d='M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z'/>
                                                                        <path d='M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708'/>
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </td>
                                            </tr>
                                            <tbody id='{$nomeLista}' class='extra-info' style='display: none;'>
                                        ";

                                    foreach ($info['data'] as $item) {
                                        // Separar a mensagem em duas partes: "Pagina: <num>" e o restante
                                        if (preg_match('/^(Pagina:\s*\d+)(.*)$/i', $item, $matches)) {
                                            $col1 = trim($matches[1]); // "Pagina: <num>"
                                            $col2 = trim($matches[2]); // o restante da mensagem
                                        } else {
                                            $col1 = $item;
                                            $col2 = "";
                                        }
                                        echo " 
                                                <tr>
                                                    <td style='text-align: center;'>{$col1}</td>
                                                    <td width='70%'>{$col2}</td>
                                                </tr>
                                            ";
                                    }
                                    echo "</tbody>";
                                } else if (!empty($info['data'])) {
                                    // Caso o dado não seja um array ou já seja um valor único
                                    echo "
                                            <tr>   
                                                <td>
                                                <div class='d-flex flex-row'>
                                                    <h6>{$info['titulo']}</h6>
                                                    <a class='ml-auto text-dark' data-toggle='modal' data-target='#exampleModal-{$info['modal']}' href='#'>
                                                    <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-info-circle ' viewBox='0 0 16 16'>
                                                        <path d='M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16'/>
                                                        <path d='m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0'/>
                                                    </svg>
                                                    </a>
                                                </div>
                                                </td>
                                                <td width='70%' class='p-0 m-0'>
                                                <a class='d-flex container-fluid flex-row funcao-alternar text-success p-3'>
                                                    <div class='row d-flex w-100'>
                                                        <div class='col-10 text-left'>
                                                            <b class='text-success'>{$info['data']}</b>
                                                        </div>
                                                        <div class='col-2 d-flex justify-content-end align-items-center' style='width: 70px;'>
                                                            <div class='icone'>
                                                                <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='text-success bi bi-check-square mr-2' viewBox='0 0 16 16'>
                                                                    <path d='M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z'/>
                                                                    <path d='M10.97 4.97a.75.75 0 0 1 1.071 1.05l-3.992 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z'/>
                                                                </svg>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    </a>
                                                </td>
                                            </tr>
                                        ";
                                }
                            }
                        }
                        ?>
                    </table>


                </div>
            </div>
        </div>


    </div>
</body>

</html>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        document.body.addEventListener('click', function (event) {
            // Garante que o clique seja tratado mesmo que seja em um elemento interno
            const link = event.target.closest('.funcao-alternar');
            if (!link) return;
            event.preventDefault();

            const targetId = link.getAttribute('data-target');
            const targetContainer = document.getElementById(targetId);
            // Seleciona o container onde o SVG está (dentro do link)
            const svgContainer = link.querySelector('#olho');

            // SVG padrão (olho) e SVG alternado (olho riscado)
            const eyeSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-eye mr-2 text-dark" viewBox="0 0 16 16">
        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
        </svg>`;

            const eyeSlashSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-eye-slash text-dark mr-2" viewBox="0 0 16 16">
        <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755q-.247.248-.517.486z"/>
        <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829"/>
        <path d="M3.35 5.47q-.27.24-.518.487A13 13 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12z"/>
        </svg>`;

            if (targetContainer) {
                if (targetContainer.style.display === 'table-row-group') {
                    // Se estiver visível, oculta o container e define o ícone padrão (olho)
                    targetContainer.style.display = 'none';
                    if (svgContainer) {
                        svgContainer.innerHTML = eyeSvg;
                    }
                } else {
                    // Oculta todos os containers de extra-info e reseta todos os ícones para o olho padrão
                    document.querySelectorAll('.extra-info').forEach(function (tbody) {
                        tbody.style.display = 'none';
                    });
                    document.querySelectorAll('.funcao-alternar #olho').forEach(function (el) {
                        el.innerHTML = eyeSvg;
                    });
                    // Exibe o container desejado e troca o ícone para olho riscado
                    targetContainer.style.display = 'table-row-group';
                    if (svgContainer) {
                        svgContainer.innerHTML = eyeSlashSvg;
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