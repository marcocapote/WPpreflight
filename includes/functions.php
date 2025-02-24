<?php
class Functions
{
    private static function getPdfInfo($uploaded_file)
    {
        if (!isset($uploaded_file['file']) || !file_exists($uploaded_file['file'])) {
            return ['error' => "Arquivo inválido ou não enviado."];
        }
        $pdfArquivo = realpath($uploaded_file['file']);
        if (!$pdfArquivo) {
            return ['error' => "Erro ao localizar o arquivo."];
        }
        return [
            'path' => str_replace('\\', '/', $pdfArquivo),
            'dir' => str_replace(['\\', '/'], '/', __DIR__ . '/preflight.jar')
        ];
    }

    private static function runJavaCommand($pdfPath, $jarPath, $commandType)
    {
        $command = 'java -jar ' . escapeshellarg($jarPath) . ' ' . escapeshellarg($pdfPath) . ' ' . $commandType . ' 2>&1';
        exec($command, $output, $retorno);
        return $retorno === 0 ? $output : null;
    }
    public static function verificar_sangra($uploaded_file)
    {
        $pdfInfo = self::getPdfInfo($uploaded_file);
        if (isset($pdfInfo['error']))
            return $pdfInfo['error'];

        $saida = self::runJavaCommand($pdfInfo['path'], $pdfInfo['dir'], 'margin');
        if (!$saida)
            return "Erro na execução do preflight.";
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
                'Esquerda' => $values[0],
                'Direita' => $values[1],
                'Superior' => $values[2],
                'Inferior' => $values[3]
            ];
        }


        $mensagens = [];
        foreach ($resultados as $resultado) {
            $issues = [];

            // Check all four bleed values
            foreach (['Esquerda', 'Direita', 'Superior', 'Inferior'] as $type) {
                $value = round($resultado[$type], 1);

                if ($value < 3) {
                    $issues[] = "$type: {$value}mm (abaixo do mínimo)";
                } elseif ($value < 5) {
                    $issues[] = "$type: {$value}mm (abaixo do recomendado)";
                }
            }   

            if (!empty($issues)) {
                $mensagens[] = "Pagina: {$resultado['pagina']} " . implode(', ', $issues);
            }
        }

        return empty($mensagens)
            ? "Todas as páginas com sangria estão dentro das normas."
            : $mensagens;

    }
    public static function verificar_margem($uploaded_file, $isColaChecked)
    {
        $pdfInfo = self::getPdfInfo($uploaded_file);
        if (isset($pdfInfo['error'])) {
            return $pdfInfo['error'];
        }
    
        $saida = self::runJavaCommand($pdfInfo['path'], $pdfInfo['dir'], 'marginSafety');
        if (!$saida) {
            return "Erro na execução do preflight.";
        }
    
        // Aqui iremos montar o array $resultados com os dados de cada página
        $resultados = [];
        foreach ($saida as $index => $linha) {
            $partes = preg_split('/\s+/', trim($linha));
            // Verifica se os dados necessários existem na linha
            if (isset($partes[13])) {
                // Se a variável $partes[1] não for o número correto da página, usamos o índice do loop + 1
                $pagina = $index + 1;
                $margemEsquerda = str_replace(',', '.', $partes[4]);
                $margemDireita = str_replace(',', '.', $partes[7]);
                $margemSuperior = str_replace(',', '.', $partes[10]);
                $margemInferior = str_replace(',', '.', $partes[13]);
    
                $resultados[] = [
                    'pagina' => $pagina,
                    'margemEsquerda' => round($margemEsquerda, 1),
                    'margemDireita' => round($margemDireita, 1),
                    'margemSuperior' => round($margemSuperior, 1),
                    'margemInferior' => round($margemInferior, 1)
                ];
            }
        }
    
        $mensagens = [];
        // Se não estiver marcada a opção "cola", as margens mínimas são 10mm para todos os lados
        if ($isColaChecked !== true) {
            foreach ($resultados as $resultado) {
                $erros = [];
                if ($resultado['margemEsquerda'] <= 5) {
                    $erros[] = "esquerda (" . $resultado['margemEsquerda'] . "mm)";
                }
                if ($resultado['margemDireita'] <= 5) {
                    $erros[] = "direita (" . $resultado['margemDireita'] . "mm)";
                }
                if ($resultado['margemSuperior'] <= 5) {
                    $erros[] = "superior (" . $resultado['margemSuperior'] . "mm)";
                }
                if ($resultado['margemInferior'] <= 5) {
                    $erros[] = "inferior (" . $resultado['margemInferior'] . "mm)";
                }
                if (!empty($erros)) {
                    $mensagens[] = "Pagina: " . $resultado['pagina'] . " Margens de segurança abaixo do mínimo (10mm): " . implode(", ", $erros) . ".<br>";
                }
            }
        } else if($isColaChecked === true) {
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
                    $mensagens[] = "Pagina: " . $resultado['pagina'] . " Margens de segurança abaixo do mínimo (5mm): " . implode(", ", $erros) . ".<br>";
                }
            }
        }
    
        if (!empty($mensagens)) {
            return $mensagens;
        } else {
            return "Todos os elementos contidos na página estão dentro dos limites dos espaços de segurança recomendado." ;
        }
    }
    


    public static function verificar_qtd_paginas($uploaded_file)
    {
        $pdfInfo = self::getPdfInfo($uploaded_file);
        if (isset($pdfInfo['error']))
            return $pdfInfo['error'];

        $saida = self::runJavaCommand($pdfInfo['path'], $pdfInfo['dir'], 'info');
        if (!$saida)
            return "Erro na execução do preflight.";

        $mensagens = [];
        foreach ($saida as $linha) {
            // Procura por número de páginas
            if (stripos($linha, "Paginas: ") === 0) {
                $partes = preg_split('/\s+/', $linha);
                if (isset($partes[1])) {
                    $mensagens['pagina'] = (int) $partes[1];
                }
            }
            // Procura pelo tamanho da página
            if (stripos($linha, "Resolucao:") === 0) {
                $partes = preg_split('/\s+/', $linha);
                if (isset($partes[2]) && isset($partes[4])) {
                    $mensagens['size'] = $partes[1] . ' x ' . $partes[4] . ' mm';
                }
            }
        }

        return $mensagens;

    }

    public static function java_verificar_resolucao($uploaded_file)
    {
        $pdfInfo = self::getPdfInfo($uploaded_file);
        if (isset($pdfInfo['error']))
            return $pdfInfo['error'];

        $saida = self::runJavaCommand($pdfInfo['path'], $pdfInfo['dir'], 'image');
        if (!$saida)
            return "Todas as imagens estão com a resolução correta.";

        $mensagens = [];
        foreach ($saida as $linha) {
            // Expressão regular corrigida para capturar número da página e DPI
            if (preg_match('/Pagina:\s*(\d+)\s*Resolucao:\s*(-?\d+)dpi/i', $linha, $matches)) {
                $pagina = isset($matches[1]) ? intval($matches[1]) : 0;
                $resolucao = isset($matches[2]) ? intval($matches[2]) : 0;

                // Depuração: Mostra os valores capturados
                error_log("Pagina: $pagina | Resolução: $resolucao dpi");

                if ($resolucao < 300) {
                    $mensagens[] = "Pagina: $pagina Imagem com resolução abaixo do recomendado: {$resolucao}dpi.<br>";
                }
            }

        }

        if (!empty($mensagens)) {
            return $mensagens;
        } else {
            return "Todas as imagens estão com a resolução correta.";
        }

    }

    public static function java($uploaded_file)
    {
        $pdfInfo = self::getPdfInfo($uploaded_file);
        if (isset($pdfInfo['error']))
            return $pdfInfo['error'];

        $saida = self::runJavaCommand($pdfInfo['path'], $pdfInfo['dir'], 'graphic');
        if (!$saida)
            return "Erro na execução do preflight.";

        $mensagens = [];
        $paginaAtual = null;

        foreach ($saida as $linha) {
            // Verifica imagens
            if (preg_match('/Image detected on page: (\d+) ColorSpace: (\w+)/', $linha, $matches)) {
                $pagina = $matches[1];
                $colorSpace = $matches[2];
                if (strtolower($colorSpace) !== 'devicecmyk' && strtolower($colorSpace) !== 'iccbased' && strtolower($colorSpace) !== 'separation' && strtolower($colorSpace) !== 'devicegray') {
                    $mensagens[] = "Pagina: $pagina Encontrada imagem com um formato de cores diferente de CMYK Formato encontrado: " . $colorSpace;
                }
            }
            // Verifica elementos gráficos (caminhos)
            elseif (preg_match('/(Fill|Stroke) Path detected on page: (\d+) ColorSpace: (\w+)/', $linha, $matches)) {
                $pagina = $matches[2];
                $colorSpace = $matches[3];
                if (strtolower($colorSpace) !== 'devicecmyk' && strtolower($colorSpace) !== 'iccbased' && strtolower($colorSpace) !== 'separation' && strtolower($colorSpace) !== 'devicegray') {
                    $mensagens[] = "Pagina: $pagina Encontrado elemento gráfico com um formato de cores diferente de CMYK Formato encontrado: " . $colorSpace;
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
            return "Todos os elementos gráficos e imagens estão num formato de cores permitido.";
        }

    }

    public static function javaFontes($uploaded_file)
    {
        $pdfInfo = self::getPdfInfo($uploaded_file);
        if (isset($pdfInfo['error']))
            return $pdfInfo['error'];

        $saida = self::runJavaCommand($pdfInfo['path'], $pdfInfo['dir'], 'fonts');
        if (!$saida)
            return "Erro na execução do preflight.";

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
                    $mensagens[] = "Pagina: $pagina Encontrada fonte com formato de cor diferente de CMYK: $cor";
                }
            }
        }

        if (!empty($mensagens)) {
            return $mensagens;
        } else {
            return "Todas as imagens e vetores do trabalho estão no espaço de cor correto. CMYK.";
        }

    }

    // public static function fontElement($uploaded_file)
    // {
    //     $pdfInfo = self::getPdfInfo($uploaded_file);
    //     if (isset($pdfInfo['error']))
    //         return $pdfInfo['error'];

    //     $saida = self::runJavaCommand($pdfInfo['path'], $pdfInfo['dir'], 'fontElement');
    //     if (!$saida)
    //         return "Erro na execução do preflight.";

    //     $mensagens = [];
    //     foreach ($saida as $linha) {
    //         $pattern = '/Pagina: (\d+)\s+Posicao: \(([\d.]+), ([\d.]+)\),\s+Tamanho: ([\d.]+)\s+CorTexto: \[([\d., ]*)\]\s+CorGrafico: \[([\d., ]*)\]/';

    //         // Verifica se a regex corresponde à linha
    //         if (preg_match($pattern, $linha, $matches)) {
    //             // Extrai as informações relevantes
    //             $pagina = $matches[1];       // Número da página
    //             $posX = $matches[2];         // Coordenada X
    //             $posY = $matches[3];         // Coordenada Y
    //             $tamFonte = $matches[4];     // Tamanho da fonte
    //             $corTexto = $matches[5];     // Cor do texto
    //             $corGrafico = $matches[6];   // Cor do gráfico
    //             $somaComponentes = 0;
    //             if (!empty($corGrafico)) {
    //                 // Remove espaços e divide os valores por vírgula
    //                 $componentes = explode(',', str_replace(' ', '', $corGrafico));

    //                 // Converte os valores para float e soma
    //                 $somaComponentes = array_sum(array_map('floatval', $componentes));
    //             }

    //             if ($somaComponentes > 2.9   &&  $tamFonte <= 5 && $corTexto == '0.0, 0.0, 0.0, 0.0') {
    //                 $mensagens[] = "Texto cor: $corTexto, gráfico cor: $corGrafico, tamanho da fonte: $tamFonte, posição: ($posX, $posY) na página $pagina soma dos componentes: $somaComponentes";
    //             }

    //         } else {
    //             // Caso a regex não corresponda à linha
    //             return "Linha não corresponde ao padrão: $linha\n";
    //         }
    //     }

    //     if (!empty($mensagens)) {
    //         return $mensagens;
    //     } else {
    //         return "Todos os elementos de texto aparecerão na impressao";
    //     }

    // }

    public static function javaFontePreta($uploaded_file)
    {
        $pdfInfo = self::getPdfInfo($uploaded_file);
        if (isset($pdfInfo['error']))
            return $pdfInfo['error'];

        $saida = self::runJavaCommand($pdfInfo['path'], $pdfInfo['dir'], 'fonts');
        if (!$saida)
            return "Não foi possivel encontrar nenhuma fonte preta.";

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
                if (strtolower($corType) == 'devicecmyk' ) {
                    if ($componentes[3] >= 0.7 && ($componentes[0] > 0 || $componentes[1] > 0 || $componentes[2] > 0)) {
                        $mensagens[] = "Pagina: $pagina Encontrado texto preto com outras cores: " . $corValues;
                    }
                }


            } else {
                // Linha não reconhecida - apenas registre, não interrompa
                error_log("Formato não reconhecido: $linha");
                continue; // Pula para próxima iteração
            }
        }

        return $mensagens ?: "Todas as fontes pretas estão somente no canal K";


    }
}
?>