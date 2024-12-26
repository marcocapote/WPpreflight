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
            $mensagens=[];
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
                        $mensagens[] = "Encontrado imagem na pagina ". ($partes[0]) ." que não está em cmyk. Formato encontrado: " . $partes[5] . "<br>";
                    }
                }
            }
            if(!empty($mensagens)){
                return print_r($mensagens);
            }else{
                // Se todos os itens forem "cmyk", retorna sucesso
                return "Todos os itens estão em cmyk.";
            }
            
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
            $trims = [];
            $resultados = [];
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
                }else if(isset($partes[2]) && $partes[2] === "TrimBox:"){
                    $trimPagina = [$partes[3], $partes[4], $partes[5], $partes[6]];

                    array_push($trims, $trimPagina);
                } else{
                    continue;
                }
            }
            for ($row = 0; $row < $numPages; $row++) {
                $resultado = [
                    (floatval($sangras[$row][2]) - floatval($trims[$row][2])) * (25.4 / 72),
                    (floatval($sangras[$row][3]) - floatval($trims[$row][3])) * (25.4 / 72)
                ];
                array_push($resultados, $resultado);
            }
            
            $mensagens = []; // Array para armazenar mensagens
            
            foreach ($resultados as $index => $linha) {
                $linha[0] = round($linha[0], 1);
                $linha[1] = round($linha[1], 1);
                // Verificar se algum valor é menor que 3
                if ( $linha[0]  < 3 || $linha[1] < 3) {
                    $mensagens[] = " A pagina " . ($index + 1) . " está com a sangria abaixo do mínimo (3mm): " . $linha[0] . "mm <br>";
                    
                }else if ($linha[0] < 5 || $linha[1] < 5){
                    $mensagens[] = " A pagina " . ($index + 1) . " está com a sangria acima do mínimo mas abaixo do recomendado (5mm): ". $linha[0] . "mm <br>";
                }else{
                    $mensagens[] = "A pagina está com a sangra correta";
                }
            }
            
            return print_r($mensagens);
            
            
            
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
        } else {
            return "Nenhum arquivo válido foi enviado.";
        }
    
    }
    public static function verificar_resolucao($uploaded_file){
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
            $mensagens=[];
            // Variável para rastrear se algum item não está em "cmyk"
            foreach ($saida as $index => $linha) {
                // Ignora a primeira linha (cabeçalho)
                if ($index === 0) {
                    continue; // Pula a primeira linha
                }

                // Quebra a linha em partes
                $partes = preg_split('/\s+/', trim($linha)); // Divide a linha por espaços

                // Verifica se temos pelo menos 6 itens na linha
                if (isset($partes[12])) {
                    // Verifica se o 6º item é diferente de "cmyk"
                    if (intval($partes[12]) < 300) {
                        $mensagens[] = "Encontrado imagem na pagina ". ($partes[0]) ." que esta com a resolução abaixo do recomendado (300dpi). Resolução encontrada: " . $partes[12] . "dpi <br>";
                    }
                }
            }
            if(!empty($mensagens)){
                return print_r($mensagens);
            }else{
                // Se todos os itens forem "cmyk", retorna sucesso
                return "Todos os itens estão com a resolução correta.";
            }
            
        } else {
            return "Nenhum arquivo válido foi enviado.";
        }
    }
    public static function verificar_fontes($uploaded_file) {
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
            // Caminho absoluto do arquivo enviado
            $pdfArquivo = realpath($uploaded_file['file']);
            $pdfArquivo = str_replace(['\\', '/'], '/', $pdfArquivo);
    
            // Obtém o caminho absoluto do script Python
            $diretorio = __DIR__ . '/function.py';
    
            // Substituir todas as barras invertidas e normais para um formato padronizado
            $diretorio = str_replace(['\\', '/'], '/', $diretorio);
    
            // Verifica se o caminho do arquivo foi resolvido corretamente
            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo. Caminho: " . $pdfArquivo;
            }
    
            // Monta o comando para executar o script Python
            $comando = 'C:\\Users\\User\\AppData\\Local\\Programs\\Python\\Python313\\python.exe ' . $diretorio . ' ' . $pdfArquivo . ' 2>&1';
    
            // Executa o comando
            exec(str_replace(['\\', '/'], '/', $comando), $saida, $retorno);
    
            // Verifica se o comando foi executado com sucesso
            if ($retorno !== 0) {
                return "Erro ao executar o comando. Saída: " . implode("\n", $saida) . "<br>" . $comando;
            }
    
            $mensagens = [];
            // Variável para rastrear se algum item não está em "cmyk"
            foreach ($saida as $index => $linha) {
                // Ignora a primeira linha (cabeçalho)
                if ($index === 0) {
                    continue;
                }
    
                // Quebra a linha em partes
                $partes = preg_split('/\s+/', trim($linha));
    
                // Verifica se o item é `devicecmyk`
                if (isset($partes[5]) && strtolower($partes[3]) !== 'colourspace="devicecmyk"') {
                    // Busca para cima a linha mais próxima que começa com `<page`
                    for ($i = $index - 1; $i >= 0; $i--) {
                        $linhaPagina = $saida[$i];
                        $partesPagina = preg_split('/\s+/', trim($linhaPagina));
    
                        if (isset($partesPagina[0]) && strtolower($partesPagina[0]) === '<page') {
                            for ($l = $index - 1; $l >= 0; $l--){
                                $linhaBox = $saida[$l];
                                $partesBox = preg_split('/\s+/', trim($linhaBox));
                                // Adiciona a informação da página à mensagem
                                 $mensagens[] = "Encontrado caracter na página " . htmlspecialchars($partesPagina[1]) . " que não está em cmyk. Formato encontrado: " . $partes[3] . "<br>";
                                break;
                            }
                            
                        }
                    }
                }
            }
    
            if (!empty($mensagens)) {
                return implode("\n", $mensagens);
            } else {
                // Se todos os itens forem "cmyk", retorna sucesso
                return "Todos os itens estão em cmyk.";
            }
        } else {
            return "Nenhum arquivo válido foi enviado.";
        }
    }
    

    
    
}
?>
