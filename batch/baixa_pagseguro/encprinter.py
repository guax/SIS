#!/usr/bin/env python
# -*- coding: utf-8 -*-
#
"""
Nexxera Tecnologia e Serviços S/A
nxgen.py - invocação do gerador

Parte integrante do sistema gerador de código Nexxera
 
Alexandre Machado <alexandre.machado@nexxera.com> - 16/07/2009 - 17:00
"""

import sys
from locale import getpreferredencoding
from codecs import encode, decode

def findGoodPreferredEncoding():
    """
    Descobre qual a codificação de caracteres preferida
    do sistema, e, se não conseguir codificar caracteres
    acentuados nesta codificação, considera como preferida
    a codificação ISO-8859-1
    """
    enc = getpreferredencoding()            
    try:
        y = encode(u'é',enc, 'strict')                
    except:
        enc = 'iso-8859-1'
    try:
        x = encode(u'é',enc, 'strict')
        y = decode(x,enc, 'strict')
        if u'é' != y:
            enc='iso-8859-1'
    except:
        enc = 'iso-8859-1'
    return enc


class EncodedPrinter:
    """
    Escrita em arquivo ou tela utilizando o codec
    padrão do sistema (codificação de caractere padrão)
    """
    myEncoding = None

    def checkEncodeDecode(self):
        "Verifica qual a codificação padrão"
        if not self.myEncoding:
            self.myEncoding = findGoodPreferredEncoding()

    def printOut(self, msg, output=sys.stdout):
        "Imprime codificado"
        self.checkEncodeDecode()
        try:
            msgOut = unicode(msg)
        except:
            msgOut = decode(msg, self.myEncoding, 'replace')
            
        print >> output, encode(msgOut,self.myEncoding, 'replace')
   
