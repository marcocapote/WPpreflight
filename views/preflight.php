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
        $resposta = Functions::verificar_cores_paginas($uploaded_file);
        echo $resposta . "<br>";

        $resposta2 =Functions::verificar_sangra($uploaded_file);
        echo $resposta2 . "<br>";

        $resolucao = Functions::verificar_resolucao($uploaded_file);
        echo $resolucao;

        $quantidade = Functions::verificar_qtd_paginas($uploaded_file);
        echo "<br> <h5> Quantidade de paginas no arquivo: " . $quantidade . "</h5><br>";

        $corfonte = Functions::verificar_fontes($uploaded_file);
        echo $corfonte;

        
    }
}
?>
<h3><?php echo isset($resposta) ? $resposta : ''; ?></h3>
<h2>Upload PDF</h2>

<?php
if (isset($resposta) && $resposta) {
    echo 'a';
} else {
    echo 'b';
}
?>



<form action="" method="post" enctype="multipart/form-data">
    <label for="file">Upload PDF</label>
    <input type="file" id="file" name="file">

    <button type="submit" name="submit_arquivo">Enviar</button>
</form>


<script>
document.addEventListener('DOMContentLoaded', function () {
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


