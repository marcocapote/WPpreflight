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

        $quantidade = Functions::verificar_qtd_paginas($uploaded_file);
        echo $quantidade;
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


