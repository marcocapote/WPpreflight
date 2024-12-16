<?php
class Functions {
    public static function verificar_cores_paginas($uploaded_file) {
        // Verifica se o arquivo foi enviado corretamente
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
            // Caminho absoluto do arquivo enviado
            $pdfArquivo = realpath($uploaded_file['file']);
            $pdfArquivo = str_replace('\\', '/', $pdfArquivo);

            // Verifica se o caminho do arquivo foi resolvido corretamente
            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo. " . $pdfArquivo;
            }

            // Comando Ghostscript para contar as páginas do PDF
            $comando = '"C:\poppler-24.08.0\Library\bin\pdfimages.exe" -list ' . $pdfArquivo . ' 2>&1';
            exec($comando, $saida, $retorno);

            // Verifica se o comando foi executado com sucesso
            if ($retorno !== 0) {
                return "Erro ao executar o comando Ghostscript. Comando: " . implode("\n", $saida);
            }

            // Variável para rastrear se algum item não está em "cmyk"
            foreach ($saida as $index => $linha) {
                // Ignora a primeira linha (cabeçalho)
                if ($index === 0) {
                    continue; // Pula a primeira linha
                }

                // Quebra a linha em partes
                $partes = preg_split('/\s+/', trim($linha)); // Divide a linha por espaços

                // Verifica se temos pelo menos 6 itens na linha
                if (isset($partes[5])) {
                    // Verifica se o 6º item é diferente de "cmyk"
                    if (strtolower($partes[5]) !== "cmyk") {
                        return "Pelo menos um item não está em cmyk. Item encontrado: " . $partes[5];
                    }
                }
            }

            // Se todos os itens forem "cmyk", retorna sucesso
            return "Todos os itens estão em cmyk.";
        } else {
            return "Nenhum arquivo válido foi enviado.";
        }
    }
    public static function verificar_sangra($uploaded_file){
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])){
            $pdfArquivo = realpath($uploaded_file['file']);
            $pdfArquivo = str_replace('\\', '/', $pdfArquivo);
            $numPages = Functions::verificar_qtd_paginas($uploaded_file);
            $sangras = [];
            // Verifica se o caminho do arquivo foi resolvido corretamente
            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo. " . $pdfArquivo;
            }

            // Comando Ghostscript para contar as páginas do PDF
            $comando = '"C:\poppler-24.08.0\Library\bin\pdfinfo.exe" -box -f 1 -l '. $numPages .' ' . $pdfArquivo . ' 2>&1';
            exec($comando, $saida, $retorno);


            foreach ($saida as $index => $linha) {
                $partes = preg_split('/\s+/', trim($linha)); // Divide a linha por espaços
                // Verifica se temos pelo menos 6 itens na linha
                if (isset($partes[2]) && $partes[2] === "BleedBox:") {
                    $sangraPagina = [$partes[3], $partes[4], $partes[5], $partes[6]];
                    
                    array_push($sangras, $sangraPagina);
                   // return " numero de paginas: ". $numPages . ' ' . $partes[1] . ' ' . $partes[2] . ' ' . $partes[3] . ' ' . $partes[4] . ' '. $partes[5] . ' ' . $partes[6];
                } else{
                    continue;
                }
            }
            return $sangras[1][3];
            
        }
    }
    public static function verificar_qtd_paginas($uploaded_file){
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
            // Caminho absoluto do arquivo enviado
            $pdfArquivo = realpath($uploaded_file['file']);
            $pdfArquivo = str_replace('\\', '/', $pdfArquivo);

            // Verifica se o caminho do arquivo foi resolvido corretamente
            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo. " . $pdfArquivo;
            }

            // Comando Ghostscript para contar as páginas do PDF
            $comando = '"C:\poppler-24.08.0\Library\bin\pdfinfo.exe" ' . $pdfArquivo . ' 2>&1';
            exec($comando, $saida, $retorno);

            // Verifica se o comando foi executado com sucesso
            if ($retorno !== 0) {
                return "Erro ao executar o comando Ghostscript. Comando: " . implode("\n", $saida);
            }

            // Variável para rastrear se algum item não está em "cmyk"
            foreach ($saida as $index => $linha) {
                // Quebra a linha em partes
                $partes = preg_split('/\s+/', trim($linha)); // Divide a linha por espaços
                // Ignora a primeira linha (cabeçalho)
                if ($partes[0] === "Pages:") {
                    return intval($partes[1]);
                } else{
                    continue;
                }

                

                // Verifica se temos pelo menos 6 itens na linha
                
            }

            // Se todos os itens forem "cmyk", retorna sucesso
            return "Todos os itens estão em cmyk.";
        } else {
            return "Nenhum arquivo válido foi enviado.";
        }
    
    }
}
?>
