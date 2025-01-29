import contextlib
import io
import sys

from pdfminer.converter import XMLConverter
from pdfminer.pdfinterp import PDFResourceManager, PDFPageInterpreter
from pdfminer.pdfpage import PDFPage
from pdfminer.layout import LAParams
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

with open(sys.argv[1], 'rb') as in_fp:
    out_fp = io.BytesIO()
    rsc_mgr = PDFResourceManager(caching=True)
    laparams = LAParams()
    converter = XMLConverter(rsc_mgr, out_fp, codec='UTF-8', laparams=laparams)

    with contextlib.closing(converter) as device:
        interpreter = PDFPageInterpreter(rsc_mgr, device)
        for page in PDFPage.get_pages(in_fp, maxpages=0):  # Process only the first page
            interpreter.process_page(page)

    print(out_fp.getvalue().decode('UTF-8'))