#!/usr/bin/env python
# -*- coding: utf-8 -*-
# $Id: cmdlinecfg.py,v 1.2 2009/07/21 17:39:48 alexandre.machado Exp $

"""
# Alexandre Machado
#
# Parsing de linha de comando, gerando um objeto de
# configuracao
#
# Uso:
#
# Crie um objeto AppConfig para armazenar sua configuracao
#
# cfg = AppConfig ('Nome da Aplicacao', ...)
#
# Acicione suas opcoes de linha de comando:
#
# cfg.addOption (ConfigOption('opt1', ...))
#
# Adicione seus parametros posicionais, na ordem em
# que devem ser interpretados
#
# cfg.addParam (ConfigParam ('param1', ...))
#
# Inicialize a configuracao com os valores default:
#
# cfg.prepareConfig()
#
# Interprete a linha de comando:
#
# cfg.procCmdLine(sys.argv)
#
# Use os parametros
#
# if cfg.opt1:
#    alguma coisa
#
# arq = cfg.param1
#
# O parametro -h (--help) e provido automaticamente.
#
"""

import getopt
import sys
from encprinter import EncodedPrinter

class ConfigEntry (object):
    """ Entrada de configuração da aplicação """
    
    def __init__(self, name, defValue=None, helpMsg=None):
        """
        name: Nome do item de configuração. Deve ser um identificador
              válido em python
        defValue: Valor default
        helpMsg: Mensagem de help associada a esta config.
        """
        self.name = name
        self.defValue = defValue
        self.helpMsg = helpMsg

class ConfigOption (ConfigEntry):
    """ Opção de linha de comando """
    
    def __init__(self, name, shortOpt=None, longOpt=None,
                 hasParam=False, defValue=None, helpMsg=None):
        """
        shortOpt: opção curta (uma letra)
        longOpt: opção longa (uma palavra)
        hasParam: opção tem parâmetro?
        defValue: valor padrão para a opção
        helpMsg: mensagem de help descrevendo a opção
        """
        super(ConfigOption,self).__init__(name, defValue, helpMsg)
        self.shortOpt=shortOpt
        self.longOpt=longOpt
        self.hasParam=hasParam

    def getOptForHelp(self):
        " Formata o nome da opção para help "
        res = u""
        complemento = u""
        if self.hasParam:
            complemento = u' <%s>' % self.name
        if self.shortOpt:
            res = u'-%s%s' % (self.shortOpt, complemento)
        if self.longOpt:
            if len(res) > 0:
                res = u'%s ou --%s%s' % (res, self.longOpt, complemento)
            else:
                res = u'--%s%s' % (self.longOpt, complemento)
        return res

    def checkOption (self, optval):
        """
        Verifica se o texto da linha de comando corresponde
        a esta opção.

        Ex: se a opção for:
        shortOpt = x
        longOpt  = extreme_option

        vamos comparar optval com:
        -x
        --extreme_option
        """          
        rv = False

        if self.shortOpt:
            rv = optval == ('-' + self.shortOpt)
        if self.longOpt:
            rv = rv or (optval == ('--'+ self.longOpt))

        return rv


class ConfigParam (ConfigEntry):
    """ Parâmetro de linha de comando (posicional) """    
    def __init__(self, name, defValue=None, helpMsg = None):
        super(ConfigParam,self).__init__(name, defValue, helpMsg)


class AppConfig(object):
    """
    Configuração da aplicação e interpretação da linha de
    comando
    """
    prt = EncodedPrinter()

    
    def __init__(self, appName, helpHeader=None, helpTrailler=None):
        """
        appName: Nome da aplicação (executável/script principal)
        helpHeader: Cabeçalho da mensagem de ajuda
        helpTrailler: Rodapé da mensagem de ajuda
        """
        self.options = []
        self.params  = []
        self.helpHeader = helpHeader
        self.helpTrailler = helpTrailler
        self.appName = appName

        self.addOption ( ConfigOption ('help',
                                       shortOpt = 'h',
                                       longOpt = 'help',
                                       hasParam = False,
                                       helpMsg = u'mostra esta mensagem de ajuda') )

    def printOut(self, output, msg):
        self.prt.printOut (msg, output)

    def usage (self, output=sys.stdout):
        """
        Mostra a mensagem de ajuda (como usar a aplicação)

        output: objeto "File Like" onde será mostrado o help.
                o padrão é sys.stdout, mas pode ser redirecionado,
                por exmeplo, para sys.stderr
        """

        if self.helpHeader:
            self.printOut(output,(self.helpHeader))

        paramStr = u' '.join ([x.name for x in self.params])

        self.printOut(output,
                     u"Uso:\n   %s [opções] %s" % (self.appName, paramStr))
        self.printOut(output, "\n")

        if len(self.params) > 0:
            self.printOut(output, u"Onde:")

            for par in self.params:
                self.printOut(output,
                              u"\t%s:\n\t\t%s" % (par.name, par.helpMsg))

        self.printOut(output, u"\nOpções:")
        for opt in self.options:
            self.printOut(output,
                          u"\t%s:\n\t\t%s" % (opt.getOptForHelp(),
                                              opt.helpMsg))

        if self.helpTrailler:
            self.printOut(output, "\n")
            self.printOut(output, self.helpTrailler)


    def addParam (self, par):
        """
        Adiciona um objeto ConfigParam no final da lista de
        parâmetros posicionais
        """
        self.params.append(par)

    def delParam (self, name):
        """
        Elimina um parâmetro pelo nome
        """
        toDel = [ x for x in self.params if x.name == name ]
        for i in toDel:
            self.params.remove(i)

    def addOption (self, opt):
        """
        Adiciona uma opção de linha de comando
        """
        self.options.append(opt)
        
    def delOption (self, name):
        """
        Elimina uma opção de linha de comando
        """
        toDel = [ x for x in self.options if x.name == name ]
        for i in toDel:
            self.options.remove(i)

    def prepareConfig(self):
        """
        Prepara o objeto configuração para uso,
        inicializando os atributos dinâmicos com os
        valores padrão dos itens de configuração
        """
        for i in self.options:
            setattr(self, i.name, i.defValue)
        for i in self.params:
            setattr(self, i.name, i.defValue)

    def prepareOpts(self):
        """
        Prepara as opções para tratamento da
        linha de comando
        """
        short = ""
        longList = []

        for i in self.options:
            if i.shortOpt:
                short += i.shortOpt
                if i.hasParam:
                    short +=':'
            if i.longOpt:
                v = i.longOpt
                if i.hasParam:
                    v+='='
                longList.append(v)

        return (short, longList)

    def procCmdLine (self, args):
        """
        Processa a linha de comando, interpretando
        os parâmetros e opções
        """
        getOptShortOpts, getOptLongOpts = self.prepareOpts()

        try:
            options, params = getopt.getopt(args[1:],
                                            getOptShortOpts,
                                            getOptLongOpts)
        except getopt.GetoptError, e:
            self.usage(sys.stderr)
            self.printOut(sys.stderr, "")
            self.printOut(sys.stderr, u"Erro nas opções da linha de comando!")
            self.printOut(sys.stderr, u"\t" + str(e))
            self.printOut(sys.stderr, "")
            sys.exit(-1)
        
        
        for opt,par in options:
            if opt in [ '-h', '--help' ]:
                self.usage()
                sys.exit(0)
                
            for item in self.options:
                if item.checkOption (opt):
                    if item.hasParam:
                        setattr(self, item.name, par)
                    else:
                        setattr(self, item.name, True)

        try:
            positional=0
            for i in params:
                setattr(self, self.params[positional].name, i)
                positional += 1
        except IndexError:
            # parâmetros adicionais ignorados
            pass
        
def printCfg(cfg):
    """
    Imprime, de forma legível, os valores das opções
    de linha de comando, para debug.
    """
    encp = EncodedPrinter()
    for i in dir(cfg):
        if i[:2] != '__' and \
            i not in ['options','params','helpHeader',
                      'helpTrailler', 'appName',
                      'prt'] :
            val = getattr(cfg,i)
            if not callable(val):
                encp.printOut ("%s\t%s" % (i, str(val)))

#
# Rotina de Teste
#
if __name__ == '__main__':

    import sys
    
    helpHeader = "Teste de tratamento de linha de comando"
    helpTrailler = "Espero que funcione ;-)"
    
    cfg = AppConfig('cmdlinecfg.py', helpHeader, helpTrailler)
                    
    cfg.addOption (ConfigOption ('logLevel',
                                 shortOpt='l',
                                 longOpt='nivel_log',
                                 hasParam=True,
                                 defValue=5,
                                 helpMsg='nível de detalhe do log'))
    cfg.addOption (ConfigOption ('debug',
                                 longOpt='debug',
                                 hasParam=False,
                                 helpMsg='habilitar debug'))

    cfg.addParam (ConfigParam ('ze',
                               helpMsg = "zé é o Primeiro parâmetro\n" + \
                               "\t\tque é usado para teste\n" + \
                               "\t\tTome cuidado para não colocar acentos nos nomes de parâmetros\n" + \
                               "\t\tpois isto pode inviabilizar o uso deles no código como atributos"))

    cfg.addParam (ConfigParam ('param1',
                               helpMsg = 'Segundo parâmetro'))

    print "Antes de preparar..."
    printCfg(cfg)

    cfg.prepareConfig()

    print "Depois de preparar..."
    printCfg(cfg)

    cfg.procCmdLine (sys.argv)
    print "Depois de tratar as opções:"
    printCfg(cfg)

    if cfg.help:
        cfg.usage()
        sys.exit(0)

    print "--------------------"
    if cfg.debug:
        print "Debug habilitado"


    print "O nível de log é:", cfg.logLevel
    print "O valor de zé é:", cfg.ze

    
    
