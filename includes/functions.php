<?php
class Functions
{
    public static function verificar_cores_paginas($uploaded_file)
    {
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
            $mensagens = [];
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
                        $mensagens[] = "Encontrado imagem na pagina " . ($partes[0]) . " que não está em cmyk. Formato encontrado: " . $partes[5] . "<br>";
                    }
                }
            }
            if (!empty($mensagens)) {
                return $mensagens;
            } else {
                // Se todos os itens forem "cmyk", retorna sucesso
                return "Todas as imagens estão em cmyk.";
            }

        } else {
            return "Nenhum arquivo válido foi enviado.";
        }
    }
    public static function verificar_sangra($uploaded_file)
    {
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
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
            $comando = '"C:\poppler-24.08.0\Library\bin\pdfinfo.exe" -box -f 1 -l ' . $numPages['pagina'] . ' ' . $pdfArquivo . ' 2>&1';
            exec($comando, $saida, $retorno);


            foreach ($saida as $index => $linha) {
                $partes = preg_split('/\s+/', trim($linha)); // Divide a linha por espaços
                // Verifica se temos pelo menos 6 itens na linha
                if (isset($partes[2]) && $partes[2] === "BleedBox:") {
                    $sangraPagina = [$partes[3], $partes[4], $partes[5], $partes[6]];

                    array_push($sangras, $sangraPagina);
                    // return " numero de paginas: ". $numPages . ' ' . $partes[1] . ' ' . $partes[2] . ' ' . $partes[3] . ' ' . $partes[4] . ' '. $partes[5] . ' ' . $partes[6];
                } else if (isset($partes[2]) && $partes[2] === "TrimBox:") {
                    $trimPagina = [$partes[3], $partes[4], $partes[5], $partes[6]];

                    array_push($trims, $trimPagina);
                } else {
                    continue;
                }
            }
            for ($row = 0; $row < $numPages['pagina']; $row++) {
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
                if ($linha[0] < 3 || $linha[1] < 3) {
                    $mensagens[] = " A pagina " . ($index + 1) . " está com a sangria abaixo do mínimo (3mm): " . $linha[0] . "mm <br>";

                } else if ($linha[0] < 5 || $linha[1] < 5) {
                    $mensagens[] = " A pagina " . ($index + 1) . " está com a sangria acima do mínimo mas abaixo do recomendado (5mm): " . $linha[0] . "mm <br>";
                }
            }

            if (!$mensagens) {
                return "Todas as paginas estão com a sangra correta";
            } else {
                return $mensagens;
            }
        }
    }

    public static function verificar_margem_lombo($uploaded_file)
    {
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
            $pdfArquivo = realpath($uploaded_file['file']);
            $pdfArquivo = str_replace('\\', '/', $pdfArquivo);
            $numPages = Functions::verificar_qtd_paginas($uploaded_file);
            $artboxes = [];
            $trims = [];
            $resultados = [];

            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo. " . $pdfArquivo;
            }

            $comando = '"C:\poppler-24.08.0\Library\bin\pdfinfo.exe" -box -f 1 -l ' . $numPages['pagina'] . ' ' . $pdfArquivo . ' 2>&1';
            exec($comando, $saida, $retorno);

            foreach ($saida as $index => $linha) {
                $partes = preg_split('/\s+/', trim($linha));
                if (isset($partes[2]) && $partes[2] === "ArtBox:") {
                    $artboxes[] = [$partes[3], $partes[4], $partes[5], $partes[6]];
                } else if (isset($partes[2]) && $partes[2] === "TrimBox:") {
                    $trims[] = [$partes[3], $partes[4], $partes[5], $partes[6]];
                }
            }

            for ($row = 0; $row < $numPages['pagina']; $row++) {
                $pagina = $row + 1; // Páginas começam em 1
                if ($pagina % 2 !== 0) { // Página ímpar: margem esquerda
                    $margem = (floatval($artboxes[$row][0]) - floatval($trims[$row][0])) * (25.4 / 72);
                    $resultados[] = [
                        'pagina' => $pagina,
                        'lado' => 'esquerda',
                        'margem' => round($margem, 1)
                    ];
                } else { // Página par: margem direita
                    $margem = (floatval($trims[$row][2]) - floatval($artboxes[$row][2])) * (25.4 / 72);
                    $resultados[] = [
                        'pagina' => $pagina,
                        'lado' => 'direita',
                        'margem' => round($margem, 1)
                    ];
                }
            }

            $mensagens = [];
            foreach ($resultados as $resultado) {
                if ($resultado['margem'] < 10) {
                    $mensagens[] = "A página " . $resultado['pagina'] . " está com a margem de segurança " . $resultado['lado'] . " abaixo do mínimo (10mm): " . $resultado['margem'] . "mm <br>";
                }
            }

            if (!empty($mensagens)) {
                return $mensagens;
            } else {
                return "Todas as paginas estão com a margem de segurança do lombo corretas";
            }

        }
        return "Arquivo não encontrado ou inválido.";
    }

    public static function verificar_margem_demais_casos($uploaded_file)
    {
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
            $pdfArquivo = realpath($uploaded_file['file']);
            $pdfArquivo = str_replace('\\', '/', $pdfArquivo);
            $numPages = Functions::verificar_qtd_paginas($uploaded_file);
            $artboxes = [];
            $trims = [];
            $resultados = [];

            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo. " . $pdfArquivo;
            }

            $comando = '"C:\poppler-24.08.0\Library\bin\pdfinfo.exe" -box -f 1 -l ' . $numPages['pagina'] . ' ' . $pdfArquivo . ' 2>&1';
            exec($comando, $saida, $retorno);

            foreach ($saida as $linha) {
                $partes = preg_split('/\s+/', trim($linha));
                if (isset($partes[2]) && $partes[2] === "ArtBox:") {
                    $artboxes[] = [$partes[3], $partes[4], $partes[5], $partes[6]];
                } else if (isset($partes[2]) && $partes[2] === "TrimBox:") {
                    $trims[] = [$partes[3], $partes[4], $partes[5], $partes[6]];
                }
            }

            for ($row = 0; $row < $numPages['pagina']; $row++) {
                $pagina = $row + 1; // Páginas começam em 1

                // Cálculo de todas as margens
                $margemEsquerda = (floatval($artboxes[$row][0]) - floatval($trims[$row][0])) * (25.4 / 72);
                $margemDireita = (floatval($trims[$row][2]) - floatval($artboxes[$row][2])) * (25.4 / 72);
                $margemSuperior = (floatval($trims[$row][3]) - floatval($artboxes[$row][3])) * (25.4 / 72);
                $margemInferior = (floatval($artboxes[$row][1]) - floatval($trims[$row][1])) * (25.4 / 72);

                $resultados[] = [
                    'pagina' => $pagina,
                    'margemEsquerda' => round($margemEsquerda, 1),
                    'margemDireita' => round($margemDireita, 1),
                    'margemSuperior' => round($margemSuperior, 1),
                    'margemInferior' => round($margemInferior, 1)
                ];
            }

            $mensagens = [];
            foreach ($resultados as $resultado) {
                $erros = [];
                if ($resultado['margemEsquerda'] < 5) {
                    $erros[] = "esquerda (" . $resultado['margemEsquerda'] . "mm)";
                }
                if ($resultado['margemDireita'] < 5) {
                    $erros[] = "direita (" . $resultado['margemDireita'] . "mm)";
                }
                if ($resultado['margemSuperior'] < 5) {
                    $erros[] = "superior (" . $resultado['margemSuperior'] . "mm)";
                }
                if ($resultado['margemInferior'] < 5) {
                    $erros[] = "inferior (" . $resultado['margemInferior'] . "mm)";
                }

                if (!empty($erros)) {
                    $mensagens[] = "A página " . $resultado['pagina'] . " está com margens de segurança abaixo do mínimo (10mm): " . implode(", ", $erros) . ".<br>";
                }
            }
            if (!empty($mensagens)) {
                return $mensagens;
            } else {
                return "Todas as paginas estão com a margem correta";
            }

        }
        return "Arquivo não encontrado ou inválido.";
    }




    public static function verificar_qtd_paginas($uploaded_file)
    {
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
            $pdfArquivo = realpath($uploaded_file['file']);
            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo.";
            }

            $pdfArquivo = str_replace('\\', '/', $pdfArquivo);
            $comando = '"C:\poppler-24.08.0\Library\bin\pdfinfo.exe" ' . escapeshellarg($pdfArquivo) . ' 2>&1';
            exec($comando, $saida, $retorno);

            if ($retorno !== 0) {
                return "Erro ao executar comando: " . implode("\n", $saida);
            }

            $mensagens = [];
            foreach ($saida as $linha) {
                // Procura por número de páginas
                if (stripos($linha, "Pages:") === 0) {
                    $partes = preg_split('/\s+/', $linha);
                    if (isset($partes[1])) {
                        $mensagens['pagina'] = (int) $partes[1];
                    }
                }
                // Procura pelo tamanho da página
                if (stripos($linha, "Page size:") === 0) {
                    $partes = preg_split('/\s+/', $linha);
                    if (isset($partes[2]) && isset($partes[4])) {
                        $mensagens['size'] = round((float) $partes[2] * 25.4 / 72, 1) . ' x ' . round((float) $partes[4] * 25.4 / 72, 1) . ' mm';
                    }
                }
            }

            return $mensagens;
        } else {
            return "Arquivo inválido ou não enviado.";
        }
    }



    public static function verificar_resolucao($uploaded_file)
    {
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
            $mensagens = [];
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
                        $mensagens[] = "Encontrado imagem na pagina " . ($partes[0]) . " que esta com a resolução abaixo do recomendado (300dpi). Resolução encontrada: " . $partes[12] . "dpi <br>";
                    }
                }
            }
            if (!empty($mensagens)) {
                return $mensagens;
            } else {
                // Se todos os itens forem "cmyk", retorna sucesso
                return "Todas as imagens estão com a resolução correta.";
            }

        } else {
            return "Nenhum arquivo válido foi enviado.";
        }
    }
    public static function verificar_fontes($uploaded_file)
    {
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

            $resultados = [];
            foreach ($saida as $index => $linha) {
                // Ignora a primeira linha (cabeçalho)
                if ($index === 0) {
                    continue;
                }

                // Quebra a linha em partes
                $partes = preg_split('/\s+/', trim($linha));

                // Verifica se o item é `devicecmyk`
                if (isset($partes[5]) && strtolower($partes[3]) !== 'colourspace="devicecmyk"') {
                    $pagina = null;
                    $textbox = null;

                    // Busca para cima no arquivo
                    for ($i = $index - 1; $i >= 0; $i--) {
                        $linhaAnterior = $saida[$i];
                        $partesAnterior = preg_split('/\s+/', trim($linhaAnterior));

                        // Verifica se a linha começa com `<page>`
                        if (!$pagina && isset($partesAnterior[0]) && strtolower($partesAnterior[0]) === '<page') {
                            $pagina = $partesAnterior[1]; // Salva o valor [1] da linha `<page>`
                        }

                        // Verifica se a linha começa com `<textbox>`
                        if (!$textbox && isset($partesAnterior[0]) && strtolower($partesAnterior[0]) === '<textbox') {
                            $textbox = $partesAnterior[1]; // Salva o valor [1] da linha `<textbox>`
                        }

                        // Se ambos foram encontrados, não é necessário continuar
                        if ($pagina && $textbox) {
                            break;
                        }
                    }

                    // Agrupa resultados por `textbox`
                    if ($textbox) {
                        $chave = "Textbox ID: $textbox, Página: $pagina";
                        if (!isset($resultados[$chave])) {
                            $resultados[$chave] = [];
                        }
                        if (!in_array($partes[3], $resultados[$chave])) {
                            $resultados[$chave][] = $partes[3]; // Adiciona o formato encontrado
                        }
                    }
                }
            }

            if (!empty($resultados)) {
                $mensagens = [];
                foreach ($resultados as $chave => $formatos) {
                    $mensagens[] = "$chave contém formatos não CMYK: " . implode(', ', $formatos) . "<br>";
                }
                return $mensagens;
            } else {
                // Se todos os itens forem "cmyk", retorna sucesso
                return "Todos os textos estão em cmyk.";
            }
        }
    }


    public static function verificar_fontes_preto($uploaded_file)
    {
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
            $pdfArquivo = realpath($uploaded_file['file']);
            $pdfArquivo = str_replace(['\\', '/'], '/', $pdfArquivo);

            $diretorio = str_replace(['\\', '/'], '/', __DIR__ . '/function.py');

            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo. Caminho: " . $pdfArquivo;
            }

            $comando = 'C:\\Users\\User\\AppData\\Local\\Programs\\Python\\Python313\\python.exe ' . $diretorio . ' ' . $pdfArquivo . ' 2>&1';
            exec(str_replace(['\\', '/'], '/', $comando), $saida, $retorno);

            if ($retorno !== 0) {
                return "Erro ao executar o comando. Saída: " . implode("\n", $saida) . "<br>" . $comando;
            }

            $resultadosAgrupados = [];

            foreach ($saida as $index => $linha) {
                if (strpos($linha, 'colourspace="DeviceCMYK"') !== false) {
                    if (preg_match('/ncolour="\((.*?)\)"/', $linha, $matches)) {
                        $pagina = null;
                        $textbox = null;

                        for ($i = $index - 1; $i >= 0; $i--) {
                            $linhaAnterior = $saida[$i];
                            $partesAnterior = preg_split('/\s+/', trim($linhaAnterior));

                            if (!$pagina && isset($partesAnterior[0]) && strtolower($partesAnterior[0]) === '<page') {
                                $pagina = $partesAnterior[1];
                            }

                            if (!$textbox && isset($partesAnterior[0]) && strtolower($partesAnterior[0]) === '<textbox') {
                                $textbox = $partesAnterior[1];
                            }

                            if ($pagina && $textbox) {
                                break;
                            }
                        }

                        $valoresCMYK = array_map('floatval', explode(', ', $matches[1]));
                        list($c, $m, $y, $k) = $valoresCMYK + [0, 0, 0, 0];

                        $isPretoPuro = ($c === 0.0 && $m === 0.0 && $y === 0.0 && $k > 0.0);
                        $isQuasePreto = ($c <= 0.4 && $m <= 0.4 && $y <= 0.4 && $k > 0.9);

                        if ($isQuasePreto && !$isPretoPuro) {
                            $chave = "Página: $pagina | Textbox: $textbox";
                            if (!isset($resultadosAgrupados[$chave])) {
                                $resultadosAgrupados[$chave] = 0;
                            }
                            $resultadosAgrupados[$chave]++;
                        }
                    }
                }
            }

            if (!empty($resultadosAgrupados)) {
                $mensagens = [];
                foreach ($resultadosAgrupados as $chave => $quantidade) {
                    $mensagens[] = "Encontrado $quantidade fonte(s) que não estão em preto puro em $chave.";
                }
                return $mensagens;
            } else {
                return "Não foram encontrados valores CMYK.";
            }
        } else {
            return "Nenhum arquivo válido foi enviado.";
        }
    }

    public static function java($uploaded_file) {
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
            $pdfArquivo = realpath($uploaded_file['file']);
            $pdfArquivo = str_replace(['\\', '/'], '/', $pdfArquivo);
    
            $diretorio = str_replace(['\\', '/'], '/', __DIR__ . '/preflight.jar');
    
            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo. Caminho: " . $pdfArquivo;
            }
    
            $comando = 'java -jar ' . $diretorio . ' ' . $pdfArquivo . ' 2>&1';
            exec(str_replace(['\\', '/'], '/', $comando), $saida, $retorno);
    
            $mensagens = [];
            $paginaAtual = null;
    
            foreach ($saida as $linha) {
                // Verifica se a linha indica uma nova página
                if (strpos($linha, 'Fill Path detected on page:') !== false || strpos($linha, 'Stroke Path detected on page:') !== false) {
                    preg_match('/page: (\d+)/', $linha, $matches);
                    if (isset($matches[1])) {
                        $paginaAtual = $matches[1];
                    }
                }
    
                // Verifica se a linha contém informações sobre o espaço de cores
                if (strpos($linha, 'Fill Color Space:') !== false || strpos($linha, 'Stroke Color Space:') !== false) {
                    preg_match('/Color Space: (\w+)/', $linha, $matches);
                    if (isset($matches[1]) && strtolower($matches[1]) !== 'devicegray') {
                        $mensagens[] = "Encontrado elemento gráfico na página $paginaAtual, com um formato de cores diferente de Gray: " . $matches[1];
                    }
                }
    
                // Verifica se a linha pede para digitar 'next' ou 'exit'
                if (strpos($linha, 'Digite \'next\' para processar as próximas') !== false) {
                    // Envia o comando 'next' para continuar o processamento
                    exec('next');
                }
            }
    
            if (!empty($mensagens)) {
                return $mensagens;
            } else {
                return "Todos os elementos gráficos estão em DeviceGray.";
            }
        } else {
            return "Arquivo não encontrado.";
        }
    }
    




}
?>