#!/usr/bin/env python2.4
# -*- coding: utf-8 -*-

from xml.dom import minidom
import MySQLdb
from ConfigParser import ConfigParser
import sys

from cmdlinecfg import ConfigOption,ConfigParam,AppConfig

def getText (tag, subtag):
    els = tag.getElementsByTagName(subtag) 
    if len(els) > 0:
        el = els[0]
        cnode = el.firstChild
        return cnode.wholeText
    else:
        return ""


def initConfig():
    helpHeader = u"Baixas de Pagamentos via Pag Seguro para o SIS"
    helpTrailler = u"""
    Copyright(c) 2009 - Assoliação Software Livre SC - SoLiSC
    Este programa é Software Livre, licenciado sob GPLv3.
    Informações sobre a licença em
    http://www.fsf.org/licensing/licenses/gpl.html"""
   
    cfg = AppConfig(__file__, helpHeader, helpTrailler)

    helpText = u"""Servidor de banco de dados (mysql)
    \t\tSe não informado, é assumido o valor "mysql.guax.net"."""
    cfg.addOption(ConfigOption ('dbHost',
                                longOpt='dbhost',
                                hasParam=True,
                                defValue='mysql.guax.net',
                                helpMsg=helpText))

    helpText= u"""Nome do database do SIS
    \t\tSe não informado, é assumido o valor "solisc_sis"."""
    cfg.addOption(ConfigOption ('dbDatabase',
                                longOpt='database',
                                hasParam=True,
                                defValue='solisc_sis',
                                helpMsg=helpText))

    helpText= u"""Nome de usuário do banco de dados do SIS
    \t\tSe não informado, é assumido "solisc"."""
    
    cfg.addOption(ConfigOption('dbUser',
                               longOpt='dbuser',
                               hasParam=True,
                               defValue='solisc',
                               helpMsg=helpText))

    helpText=u"""Senha do banco de dados do SIS
    \t\tSe não informado, é assumido conexão sem senha"""

    cfg.addOption(ConfigOption('dbPass',
                               longOpt='dbpass',
                               hasParam=True,
                               defValue=None,
                               helpMsg = helpText))

    helpText=u"""Arquivo de configuração do SIS.
    \t\tSe informado, as opções de configuração do banco
    \t\tde dados são lidas deste arquivo, e as opções de
    \t\tconfiguração do acesso ao banco de dados serão
    \t\tignoradas."""

    cfg.addOption(ConfigOption('siscfg',
                               shortOpt = 'c',
                               longOpt = 'config',
                               defValue=None,
                               hasParam=True,
                               helpMsg = helpText))

    helpText=u"""Comando a ser executado. Pode ser:
    \t\tlista\t- listar os dados no arquivo XML
    \t\tbaixa\t- Realizar a baixa dos pagamentos com status
    \t\t\t  "Aprovada" na base do SIS.
    \t\tcsv\t- listar os pagamentos em um arquivo CSV para
    \t\t\t  baixa por upload no sis, com envio de e-mail
    \t\t\t  indicando a quitação do pagamento. Nesta opção, é
    \t\t\t  necessário informar também o parâmetro nomeCSV"""

    cfg.addParam (ConfigParam('comando',
                              helpMsg = helpText))

    cfg.addParam (ConfigParam('xml',
                              helpMsg = "Arquivo XML do PagSeguro"))

    cfg.addParam (ConfigParam('nomeCSV',
                              helpMsg = "Nome do arquivo CSV a ser gerado.",
                              defValue = "baixas.csv"))
    
    cfg.prepareConfig()
    return cfg


def executaBaixa(status, valor, tipoRelat, nome, email, dtComp):
    if status == 'Aprovada':
        print "Realizando baixa da transação"
        msg = "%-25s %8.2f %-20s %s (%s)" % \
              (status, valor, tipoRelat, nome, email)
        print msg.encode('utf-8')

        c = conn.cursor()
        sql = """
        update participante
        set dt_pagamento = '%s',
        vl_pagamento=%f
        where email='%s'""" % (dtComp, valor, email)
        try:
            print "Executando SQL:\n", sql
            res = c.execute(sql)
            print "Resultado: ", res
            conn.commit()
            if res != 1:
                print "ERRO: registro não encontrado para atualizar"
        except Exception,e:
            print "Erro ao atualizar participante"
            print e
            c.close()
            
    else:
        print "Ignorando transação não 'aprovada'"
    

def procXML(appConfig):
    doc = minidom.parse(appConfig.xml)
    datasets = doc.getElementsByTagName('NewDataSet')

    if appConfig.comando == 'csv':
        arqCSV = open (appConfig.nomeCSV, "w")
        print >> arqCSV, "email,data pagamento,valor pago"
    elif appConfig.comando == 'baixa':
        conn = connectDatabase(appConfig)

    for ds in datasets:
        itens = ds.getElementsByTagName('Table')
        for item in itens:
            tId = getText(item,'Transacao_ID')
            tipoTrans = getText(item, 'Tipo_Transacao')
            nome = getText(item, 'Cliente_Nome')
            email = getText(item,'Cliente_Email')
            tipoPag = getText(item,'Tipo_Pagamento')
            valor = getText (item, 'Valor_Bruto')
            status = getText (item, 'Status')
            dtComp = getText (item, 'Data_Compensacao')[:10]
            

            valor = float(valor.replace('.','').replace(',','.'))
            dtComp = dtComp[-4:] + '-' + dtComp[3:5] + '-' + dtComp[:2]
            
            if tipoTrans == "Pagamento":
                tipoRelat = tipoPag
            else:
                tipoRelat = tipoTrans

            if appConfig.comando == 'lista':
                msg = "%-25s %8.2f %-20s %s %s (%s)" % \
                      (status, valor, tipoRelat, dtComp, nome, email)
                print msg.encode('utf-8')
            elif appConfig.comando == 'baixa':
                executaBaixa (status, valor, tipoRelat, nome, email, dtComp)
            elif appConfig.comando == 'csv':
                if status == 'Aprovada':
                    print >> arqCSV, ','.join((email, str(valor), dtComp))
            else:
                print "Comando inválido"
                
    if appConfig.comando == 'baixa':
        conn.close()

def unquote(s):
    if s[0] in [ '"', "'" ]:
        if s[0] == s[-1]:
            return s[1:-1]
    return s

def configDatabaseFromCfg(appConfig):
    cfg = ConfigParser()
    cfg.read((appConfig.siscfg,))

    appConfig.dbHost = unquote(cfg.get('production','resources.db.params.host'))
    appConfig.dbUser = unquote(cfg.get('production','resources.db.params.username'))
    appConfig.dbDatabase = unquote(cfg.get('production', 'resources.db.params.dbname'))
    appConfig.dbPass = unquote(cfg.get('production', 'resources.db.params.password'))


def connectDatabase(appConfig):
    return MySQLdb.connect(host=appConfig.dbHost,
                           user=appConfig.dbUser,
                           db=appConfig.dbDatabase,
                           passwd=appConfig.dbPass)

def checkDbConnection(appConfig):
    conn = None
    try:
        try:
            conn = connectDatabase(appConfig)
        except Exception,e:
            print u"Falha ao conectar o banco de dados"
            try:
                cod,msg = e
                print "MySQL error (%d): %s" % (cod,msg)
            except:
                print "Erros:", str(e)
            return False
    finally:
        if conn:
            conn.close()
    return True

    

def checkCmdLine(appConfig):

    if not appConfig.comando:
        print u"O comando é obrigatório".encode('utf-8')
        return False

    if not appConfig.comando in [ 'lista', 'baixa', 'csv' ]:
        print u"O comando deve ser 'lista' ou 'baixa' ou 'csv'".encode('utf-8')
        return False                                  

    if not appConfig.xml:
        print u"O nome do arquivo XML é obrigatório".encode('utf-8')
        return False

    if appConfig.siscfg:
        configDatabaseFromCfg(appConfig)

    if appConfig.comando == 'baixa':
        return checkDbConnection(appConfig)
    else:
        return True

def main():
    appConfig = initConfig()
    
    appConfig.procCmdLine(sys.argv)

    if checkCmdLine(appConfig):
        procXML (appConfig)


if __name__ == '__main__':
    main()
