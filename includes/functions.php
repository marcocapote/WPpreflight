<?php
class Functions
{

    public static function verificar_sangra($uploaded_file)
    {
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
            $pdfArquivo = realpath($uploaded_file['file']);
            $pdfArquivo = str_replace('\\', '/', $pdfArquivo);
            $numPages = Functions::verificar_qtd_paginas($uploaded_file);
            $sangras = [];
            $resultados = [];
            $diretorio = str_replace(['\\', '/'], '/', __DIR__ . '/preflight.jar');

            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo. Caminho: " . $pdfArquivo;
            }

            // Comando para executar o Java Preflight
            $comando = 'java -jar ' . escapeshellarg($diretorio) . ' ' . escapeshellarg($pdfArquivo) . ' margin 2>&1';
            exec($comando, $saida, $retorno);
            // Parse the output
            $currentPage = 0;
            foreach ($saida as $linha) {
                // Detect page numbers
                if (str_starts_with($linha, 'Pagina: ')) {
                    $currentPage = (int) str_replace('Pagina: ', '', $linha);
                }

                // Parse bleed values
                if (str_contains($linha, 'Sangria [')) {
                    preg_match_all('/[\d,]+(?= mm)/', $linha, $matches);
                    if (count($matches[0]) === 4) {
                        // Convert comma decimal separator to dot and cast to float
                        $sangras[$currentPage] = array_map(function ($value) {
                            return (float) str_replace(',', '.', $value);
                        }, $matches[0]);
                    }
                }
            }

            // Build resultados array
            foreach ($sangras as $page => $values) {
                $resultados[] = [
                    'pagina' => $page,
                    'SangraEsquerda' => $values[0],
                    'SangraDireita' => $values[1],
                    'SangraSuperior' => $values[2],
                    'SangraInferior' => $values[3]
                ];
            }


            $mensagens = [];
            foreach ($resultados as $resultado) {
                $issues = [];

                // Check all four bleed values
                foreach (['SangraEsquerda', 'SangraDireita', 'SangraSuperior', 'SangraInferior'] as $type) {
                    $value = round($resultado[$type], 1);

                    if ($value < 3) {
                        $issues[] = "$type: {$value}mm (abaixo do mínimo)";
                    } elseif ($value < 5) {
                        $issues[] = "$type: {$value}mm (abaixo do recomendado)";
                    }
                }

                if (!empty($issues)) {
                    $mensagens[] = "Página {$resultado['pagina']}: " . implode(', ', $issues);
                }
            }

            return empty($mensagens)
                ? "Todas as páginas estão com sangrias corretas"
                : $mensagens;
        }
    }
    public static function verificar_margem($uploaded_file, $isColaChecked)
    {
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
            $pdfArquivo = realpath($uploaded_file['file']);
            $pdfArquivo = str_replace('\\', '/', $pdfArquivo);
            $numPages = Functions::verificar_qtd_paginas($uploaded_file);
            $resultados = [];

            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo. " . $pdfArquivo;
            }
            $diretorio = str_replace(['\\', '/'], '/', __DIR__ . '/preflight.jar');
            $comando = 'java -jar ' . escapeshellarg($diretorio) . ' ' . escapeshellarg($pdfArquivo) . ' marginSafety 2>&1';
            exec($comando, $saida, $retorno);

            foreach ($saida as $linha) {
                $partes = preg_split('/\s+/', trim($linha));
                if (isset($partes[13])) {
                    $pagina = $partes[1];
                    $margemEsquerda = str_replace(',', '.', $partes[4]);
                    $margemDireita = str_replace(',', '.', $partes[7]);
                    $margemSuperior = str_replace(',', '.', $partes[10]);
                    $margemInferior = str_replace(',', '.', $partes[13]);

                }
            }

            for ($row = 0; $row < $numPages['pagina']; $row++) {
                $resultados[] = [
                    'pagina' => $pagina,
                    'margemEsquerda' => round($margemEsquerda, 1),
                    'margemDireita' => round($margemDireita, 1),
                    'margemSuperior' => round($margemSuperior, 1),
                    'margemInferior' => round($margemInferior, 1)
                ];
            }

            $mensagens = [];

            if ($isColaChecked !== true) {
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
            } else {
                foreach ($resultados as $resultado) {
                    $erros = [];
                    if ($resultado['margemEsquerda'] < 10) {
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
                        $mensagens[] = "A página " . $resultado['pagina'] . " está com margens de segurança abaixo do mínimo (5mm): " . implode(", ", $erros) . ".<br>";
                    }
                }
            }

            if (!empty($mensagens)) {
                return $mensagens;
            } else {
                return "Todas as paginas estão com a margem correta" . $isColaChecked;
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

    public static function java_verificar_resolucao($uploaded_file)
    {
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
            $pdfArquivo = realpath($uploaded_file['file']);
            $pdfArquivo = str_replace(['\\', '/'], '/', $pdfArquivo);

            $diretorio = str_replace(['\\', '/'], '/', __DIR__ . '/preflight.jar');

            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo. Caminho: " . $pdfArquivo;
            }

            // Comando para executar o Java Preflight
            $comando = 'java -jar ' . escapeshellarg($diretorio) . ' ' . escapeshellarg($pdfArquivo) . ' image 2>&1';
            exec($comando, $saida, $retorno);

            // Verifica se a execução falhou
            if ($retorno !== 0) {
                return "Erro ao executar o preflight.jar. Código de retorno: $retorno. Saída: " . implode("\n", $saida);
            }

            // Depuração: Mostra a saída capturada
            error_log("Saída do Preflight: " . print_r($saida, true));

            $mensagens = [];
            foreach ($saida as $linha) {
                // Expressão regular corrigida para capturar número da página e DPI
                if (preg_match('/Pagina:\s*(\d+)\s*Resolucao:\s*(-?\d+)dpi/i', $linha, $matches)) {
                    $pagina = isset($matches[1]) ? intval($matches[1]) : 0;
                    $resolucao = isset($matches[2]) ? intval($matches[2]) : 0;

                    // Depuração: Mostra os valores capturados
                    error_log("Página: $pagina | Resolução: $resolucao dpi");

                    if ($resolucao < 300) {
                        $mensagens[] = "Página $pagina contém imagem com resolução abaixo do recomendado: {$resolucao}dpi.<br>";
                    }
                }

            }

            if (!empty($mensagens)) {
                return $mensagens;
            } else {
                return "Todos os elementos gráficos estão com a devida resolução.";
            }
        } else {
            return "Arquivo não encontrado.";
        }
    }

    public static function java($uploaded_file)
    {
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
            $pdfArquivo = realpath($uploaded_file['file']);
            $pdfArquivo = str_replace(['\\', '/'], '/', $pdfArquivo);

            $diretorio = str_replace(['\\', '/'], '/', __DIR__ . '/preflight.jar');

            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo. Caminho: " . $pdfArquivo;
            }

            $comando = 'java -jar ' . $diretorio . ' ' . $pdfArquivo . ' graphic 2>&1';
            exec(str_replace(['\\', '/'], '/', $comando), $saida, $retorno);

            $mensagens = [];
            $paginaAtual = null;

            foreach ($saida as $linha) {
                // Verifica imagens
                if (preg_match('/Image detected on page: (\d+) ColorSpace: (\w+)/', $linha, $matches)) {
                    $pagina = $matches[1];
                    $colorSpace = $matches[2];
                    if (strtolower($colorSpace) !== 'devicecmyk' && strtolower($colorSpace) !== 'iccbased' && strtolower($colorSpace) !== 'separation' && strtolower($colorSpace) !== 'devicegray') {
                        $mensagens[] = "Encontrada imagem na página $pagina, com um formato de cores diferente de CMYK Formato encontrado: " . $colorSpace;
                    }
                }
                // Verifica elementos gráficos (caminhos)
                elseif (preg_match('/(Fill|Stroke) Path detected on page: (\d+) ColorSpace: (\w+)/', $linha, $matches)) {
                    $pagina = $matches[2];
                    $colorSpace = $matches[3];
                    if (strtolower($colorSpace) !== 'devicecmyk' && strtolower($colorSpace) !== 'iccbased' && strtolower($colorSpace) !== 'separation' && strtolower($colorSpace) !== 'devicegray') {
                        $mensagens[] = "Encontrado elemento gráfico na página $pagina, com um formato de cores diferente de CMYK Formato encontrado: " . $colorSpace;
                    }
                }

                // Mantém o tratamento do comando 'next' se necessário
                if (strpos($linha, 'Digite') !== false) {
                    exec('next');
                }
            }

            if (!empty($mensagens)) {
                return $mensagens; // Remove duplicados
            } else {
                return "Todos os elementos gráficos e imagens estão em CMYK.";
            }
        } else {
            return "Arquivo não encontrado.";
        }
    }

    public static function javaFontes($uploaded_file)
    {
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
            $pdfArquivo = realpath($uploaded_file['file']);
            $pdfArquivo = str_replace(['\\', '/'], '/', $pdfArquivo);

            $diretorio = str_replace(['\\', '/'], '/', __DIR__ . '/preflight.jar');

            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo. Caminho: " . $pdfArquivo;
            }

            $comando = 'java -jar ' . $diretorio . ' ' . $pdfArquivo . ' fonts 2>&1';
            exec(str_replace(['\\', '/'], '/', $comando), $saida, $retorno);

            $mensagens = [];

            foreach ($saida as $linha) {
                // Divide a linha por espaços em branco
                $partes = preg_split('/\s+/', $linha);

                // Extrai as informações relevantes
                $pagina = $partes[1]; // Número da página
                $cor = $partes[4]; // Tipo de cor (DeviceRGB, DeviceCMYK, etc.)
                //  $valoresCor = trim($partes[5], '[]'); // Valores numéricos das cores
                //  $fontes = isset($partes[7]) ? trim($partes[7], '[]') : ''; // Fontes utilizadas

                if ($partes[0] == 'Pagina:') {
                    // Se não for DeviceCMYK, adicionar à lista de mensagens
                    if (strtolower($cor) !== 'devicegray' && strtolower($cor) !== 'devicecmyk' && strtolower($cor) !== 'iccbased') {
                        $mensagens[] = "Encontrada fonte na página $pagina, com formato de cor diferente de CMYK: $cor";
                    }
                }
            }

            if (!empty($mensagens)) {
                return $mensagens;
            } else {
                return "Todas as fontes estão em CMYK.";
            }
        } else {
            return "Arquivo não encontrado.";
        }
    }

    public static function fontElement($uploaded_file)
    {
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
            $pdfArquivo = realpath($uploaded_file['file']);
            $pdfArquivo = str_replace(['\\', '/'], '/', $pdfArquivo);

            $diretorio = str_replace(['\\', '/'], '/', __DIR__ . '/preflight.jar');

            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo. Caminho: " . $pdfArquivo;
            }

            $comando = 'java -jar ' . $diretorio . ' ' . $pdfArquivo . ' fontElement 2>&1';
            exec(str_replace(['\\', '/'], '/', $comando), $saida, $retorno);

            $mensagens = [];

            foreach ($saida as $linha) {
                $pattern = '/Pagina: (\d+)\s+Posicao: \(([\d.]+), ([\d.]+)\),\s+Tamanho: ([\d.]+)\s+CorTexto: \[([\d., ]*)\]\s+CorGrafico: \[([\d., ]*)\]/';

                // Verifica se a regex corresponde à linha
                if (preg_match($pattern, $linha, $matches)) {
                    // Extrai as informações relevantes
                    $pagina = $matches[1];       // Número da página
                    $posX = $matches[2];         // Coordenada X
                    $posY = $matches[3];         // Coordenada Y
                    $tamFonte = $matches[4];     // Tamanho da fonte
                    $corTexto = $matches[5];     // Cor do texto
                    $corGrafico = $matches[6];   // Cor do gráfico
                    $somaComponentes = 0;
                    if (!empty($corGrafico)) {
                        // Remove espaços e divide os valores por vírgula
                        $componentes = explode(',', str_replace(' ', '', $corGrafico));

                        // Converte os valores para float e soma
                        $somaComponentes = array_sum(array_map('floatval', $componentes));
                    }

                    if ($somaComponentes > 2.9) {
                        $mensagens[] = "Texto cor: $corTexto, gráfico cor: $corGrafico, tamanho da fonte: $tamFonte, posição: ($posX, $posY) na página $pagina soma dos componentes: $somaComponentes";
                    }

                } else {
                    // Caso a regex não corresponda à linha
                    return "Linha não corresponde ao padrão: $linha\n";
                }



            }

            if (!empty($mensagens)) {
                return $mensagens;
            } else {
                return "Todas as fontes estão em CMYK.";
            }
        } else {
            return "Arquivo não encontrado.";
        }
    }

    public static function javaFontePreta($uploaded_file)
    {
        if (isset($uploaded_file['file']) && file_exists($uploaded_file['file'])) {
            $pdfArquivo = realpath($uploaded_file['file']);
            $pdfArquivo = str_replace(['\\', '/'], '/', $pdfArquivo);

            $diretorio = str_replace(['\\', '/'], '/', __DIR__ . '/preflight.jar');

            if (!$pdfArquivo) {
                return "Erro ao localizar o arquivo. Caminho: " . $pdfArquivo;
            }

            $comando = 'java -jar ' . $diretorio . ' ' . $pdfArquivo . ' fonts 2>&1';
            exec(str_replace(['\\', '/'], '/', $comando), $saida, $retorno);
            
            $mensagens = [];
            
            foreach ($saida as $linha) {
                // Padrão para linhas do tipo: Pagina: 1 - Cor: DeviceRGB [0.102, 0.122, 0.141] Fonte(s): ...
                $pattern1 = '/Pagina:\s*(\d+)\s+-\s+Cor:\s*(\w+)\s*\[([\d., ]+)\]\s+Fonte\(s\):(.*?)\|/i';
                
                // Padrão para linhas com posição/tamanho: Pagina: 4 Posicao: (379.25, 252.43), Tamanho: 39.984...
                $pattern2 = '/Pagina:\s*(\d+)\s+Posicao:\s*\(([\d.]+),\s*([\d.]+)\)\s*,\s*Tamanho:\s*([\d.]+)\s+CorTexto:\s*\[([\d.]+)\]\s+CorGrafico:\s*\[([\d., ]*)\]/i';
            
                if (preg_match($pattern2, $linha, $matches)) {
                    // Processar dados de posição/tamanho
                    $pagina = $matches[1];
                    $posX = $matches[2];
                    $posY = $matches[3];
                    $tamanho = $matches[4];
                    $corTexto = $matches[5];
                    $corGrafico = $matches[6];
                    
                    // Sua lógica de processamento aqui
                    
                } elseif (preg_match($pattern1, $linha, $matches)) {
                    // Processar dados de cor/fonte
                    $pagina = $matches[1];
                    $corType = $matches[2];
                    $corValues = $matches[3];
                    $fontInfo = $matches[4];

                    $componentes = explode(',', str_replace(' ', '', $corValues));
                    
                    // Exemplo de tratamento:
                    if (strtolower($corType) == 'devicecmyk' || strtolower($corType) == 'devicegray' ) {
                        ($componentes[count($componentes) - 1] >= 0.7) ? $mensagens[] =  "Texto preto detectado na pagina $pagina cor " . $componentes[count($componentes) - 1] : null;
                    }


                } else {
                    // Linha não reconhecida - apenas registre, não interrompa
                    error_log("Formato não reconhecido: $linha");
                    continue; // Pula para próxima iteração
                }
            }
            
            return $mensagens ?: "Todas as verificações passaram";

            if (!empty($mensagens)) {
                return $mensagens;
            } else {
                return "Todas as fontes estão em CMYK.";
            }
        } else {
            return "Arquivo não encontrado.";
        }
    }



}
?>