import sys
import fitz  # PyMuPDF

def encontrar_caixas_coloridas(pdf_path):
    caixas_coloridas = []

    doc = fitz.open(pdf_path)
    for page_num, page in enumerate(doc):
        drawings = page.get_drawings()
        print(f"Page {page_num + 1} drawings: {drawings}")  # Print the drawings for each page
        for item in drawings:
            if 'fill' in item:
                caixas_coloridas.append({
                    'pagina': page_num + 1,
                    'coordenadas': item['rect'],
                    'cor_preenchimento': item['fill'],
                })

    return caixas_coloridas

if __name__ == "__main__":
    # Verifica se o caminho do PDF foi fornecido
    if len(sys.argv) < 2:
        print("Uso: python verificar_caixas.py <caminho_para_pdf>")
        sys.exit(1)

    # Obtém o caminho do PDF do argumento da linha de comando
    pdf_path = sys.argv[1]

    try:
        # Encontra caixas coloridas
        caixas = encontrar_caixas_coloridas(pdf_path)
        if caixas:
            print("Caixas coloridas encontradas:")
            for caixa in caixas:
                print(f"Página: {caixa['pagina']}, Coordenadas: {caixa['coordenadas']}, Cor: {caixa['cor_preenchimento']}")
        else:
            print("Nenhuma caixa colorida encontrada no PDF.")
    except Exception as e:
        print(f"Erro ao processar o PDF: {e}")