<?php
/**
 *  $Id$
 *  This file is part of SIS.
 *
 *  SIS is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  SIS is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with SIS.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once ('pgs.php');
require_once ('Email.php');

class Sis_Sis {
    private $persist;

    function  __construct() {
        $this->persist = new Sis_Persistencia();
    }

    private function validaCampoObrigatorio ($campo, $msg)
    {
        if (strlen(trim($campo)) == 0) {
            throw new Sis_Exception_InscricaoInvalida ($msg);
        }
    }

    private function precoeVencimento ($participante)
    {
        $hoje = strtotime('now');
        $venc = strtotime("+5 days");
        $participante->dt_inscricao = $hoje;
        $valores = $this->persist->valoresParaData ($hoje);
        $participante->dt_vencimento = $venc;
        if ($participante->id_caravana ) {
            $participante->valor_pagamento = $valores['vl_caravana'];
        }
        else if ($participante->getGroupId() != null) {
            $grupos = new Sis_Persistencia_Grupos();
            $grupo = $grupos->getGrupo($participante->getGroupId());
            $participante->valor_pagamento = $grupo->calculaValorInscricao($valores['vl_normal']);
        }
        else if ($participante->estudante && $participante->estudante_ufsc) {
            $participante->valor_pagamento = $valores['vl_ufsc'];
        }
        else if ($participante->estudante) {
            $participante->valor_pagamento = $valores['vl_estudante'];
        }
        else {
            $participante->valor_pagamento = $valores['vl_normal'];
        }
    }

    private function gerarSenha($participante)
    {
        $aux = $participante->email . $participante->nome . date('r');
        $hash = sha1($aux);
        return substr($hash, 0, 6);
    }

    private function validaInscricao (Sis_Participante $participante)
    {
        $this->validaCampoObrigatorio ($participante->email, 'E-mail é obrigatório');
        $this->validaCampoObrigatorio ($participante->nome, 'Nome é obrigatório');

        if ($participante->estudante) {
            $this->validaCampoObrigatorio($participante->inst_ensino, "Instituição de ensino é obrigatório para estudantes");
            $this->validaCampoObrigatorio($participante->nivel_ensino, "Nível de escolaridade é obrigatório para estudantes");
            $this->validaCampoObrigatorio($participante->curso, "Curso é obrigatório para estudantes");
        }

        if ($participante->cod_desconto) {
            $tipo = substr($participante->cod_desconto, 0, 3);
            try {
                if ($tipo == 'CAR') {
                    $participante->id_caravana = $this->persist->buscarIdCaravana ($participante->cod_desconto);
                }
                else if ($tipo == 'GRU') {
                    $grupos = new Sis_Persistencia_Grupos();
                    $grupo = $grupos->getByCodigoDesconto($participante->cod_desconto);
                    if($grupo->isFull()) {
                        throw new Sis_Exception_InscricaoInvalida("Código de desconto já ultrapassou o limite de inscritos");
                    }
                    if(!$grupo->isValid()) {
                        throw new Sis_Exception_InscricaoInvalida("Código de desconto já ultrapassou a data limite");
                    }
                    $participante->setGroupId($grupo->getId());
                }
                else {
                    throw new Sis_Exception_InscricaoInvalida ("Codigo de desconto inválido");
                }
                if (!$participante->id_caravana && !$participante->id_grupo) {
                   /* código de desconto não encontrado. */
                   throw new Sis_Exception_InscricaoInvalida ("Codigo de desconto não encontrado");
                } 
            }
            catch (Sis_Exception_Persistencia $ex) {
                throw new Sis_Exception_InscricaoInvalida ("Codigo de desconto inválido");
            }
        }

        $this->precoeVencimento ($participante);

        $participante->senha = $this->gerarSenha($participante);
        return true;

    }

    function novaInscricao ($participante)
    {
        $test = null;
        try {
            $test = $this->buscarInscricaoEmail($participante->email);
        }
        catch(Sis_Exception_InscricaoInvalida $ex) {
            //
        }
        if($test !== null)
            throw new Sis_Exception_InscricaoInvalida("E-mail já cadastrado");
            
        if ($this->validaInscricao ($participante)) {
            $novo = $this->persist->novaInscricao ($participante);
            //chamar envio de email que está em email.php neste mesmo
            //diretório
            enviaEmail ("cadastro", $participante, "");
            if($novo->valor_pagamento == 0) {
                $this->quitarPagamento($novo->email, strtotime("now"), 0.0);
            }
            return $novo;
        }
        else {
            throw new Sis_Exception_InscricaoInvalida ("Dados inválidos para inscrição");
        }
    }

    function atualizarInscricao ($participante)
    {
        if ($participante->id_caravana) {
            $participante->cod_desconto = $this->persist->buscarCodDescontoCaravana($participante->id_caravana);
        }
        if ($this->validaInscricao($participante)) {
            $novo = $this->persist->updateInscricao ($participante);
        }
    }

    function alterarSenha ($email, $senhaAtual, $senhaNova)
    {
        $participante = $this->buscarInscricaoEmail($email);
        if ($this->persist->validaLogin($email, $senhaAtual))
        {
            return $this->persist->alteraSenha ($participante->id, $senhaNova);
        }
        else
        {
            throw new Sis_Exception_Persistencia("Senha atual inválida\n");
        }
        return False;
    }

    public function definirSenha($id, $senha) {
        $participante = $this->buscarInscricao($id);
        return $this->persist->alteraSenha($participante->id, $senha);
    }

    /**
     *
     * @param string $email
     * @return Sis_Participante
     */
    function buscarInscricaoEmail ($email)
    {
        try {
            $insc = $this->persist->buscarInscricao ('email', $email);
            if (!$insc) {
                throw new Sis_Exception_InscricaoInvalida ("Não existe inscrição para este e-mail");
            }
            return $insc;
        }
        catch (Sis_Exception_Persistencia $ex) {
            throw new Sis_Exception_InscricaoInvalida ($ex->getMessage());
        }
    }

    function buscarInscricao ($id)
    {
        try {
            $insc = $this->persist->buscarInscricao ('id', $id);
            if (!$insc) {
                throw new Sis_Exception_InscricaoInvalida ("Não existe inscrição para este e-mail");
            }
            return $insc;
        }
        catch (Sis_Exception_Persistencia $ex) {
            throw new Sis_Exception_InscricaoInvalida ($ex->getMessage());
        }
    }

    function getInscricoesAbertas() {
        return $this->persist->buscarInscricaoExpr ('dt_pagamento', 'is NULL');
    }

    function pagamento($email) {
        $participante = $this->buscarInscricaoEmail($email);
        $this->precoeVencimento($participante);

        // Criando um novo carrinho
        $pgs = new pgs(array('email_cobranca'=>'cioban@solisc.org.br'));

        // Dados do cliente
        $cliente = array ('nome'   => $participante->nome,
            'cep'    => $participante->cep,
            'end'    => $participante->logradouro,
            'cidade' => $participante->cidade,
            'uf'     => $participante->uf,
            'pais'   => $participante->pais,
            'email'  => $participante->email);

        // Adicionando um produto
        $item = array(
            "valor"=>$participante->valor_pagamento,
            "descricao"=>"Inscricao SOLISC 2010",
            "quantidade"=>1,
            "id"=>"SOL2010-".$participante->id
        );

        if($participante->valor_pagamento == "") {
            return null;
        }

        $pgs->cliente($cliente);
        $pgs->adicionar(array($item));

        // Mostrando o botão de pagamento
        return $pgs;
    }

    private function gerarCodigoAutenticacao ($participante)
    {
        $cd_auth_data = "AutenticacaoPagamento: " .
            $participante->email . '-' . $participante->nome . '-' .
            $participante->data_pagamento . '-' .
            $participante->dt_vencimento . '-' .
            $participante->valor_pagamento;

        return sha1($cd_auth_data);
    }

    /**
     * Quitação de pagamento
     * IMPORTANTE: a data deve ser um "timestamp", e não uma
     * string. Ela será formatada com a função date para inserção no
     * banco de dados e para envio no e-mail.
     * Da mesma forma, o campo valor deve conter um número real!
     */
    function quitarPagamento ($email, $data, $valor)
    {
        $participante = $this->buscarInscricaoEmail($email);
        if($participante->data_pagamento === "") {
            $participante->valor_pagamento = $valor;
            $participante->data_pagamento = $data;
            $this->persist->updateInscricao ($participante);
            enviaEmail ("inscricao", $participante, $this->gerarCodigoAutenticacao($participante));

            if($participante->id_caravana !== null) {
                $caravana = $this->buscarCaravanaId($participante->id_caravana);
                if(count($caravana->participantes) > 5) {
                    $caravana_ok = 0;
                    foreach ($caravana->participantes as $p_caravana) {
                        if($p_caravana->data_pagamento != "") {
                            $caravana_ok++;
                        }
                    }
                    if($caravana_ok >= 4 && $caravana->organizador->data_pagamento == "") {
                        // Pagando a conta do organizador da caravana com R$0,00.
                        $this->quitarPagamento($caravana->organizador->email, time(), 0.0);
                    }
                }
            }
        }
    }

    function validarCodigoAutenticacao ($email, $codigo)
    {
       $participante = $this->buscarInscricaoEmail($email);
       return $codigo == $this->gerarCodigoAutenticacao($participante);
    }

    function novaCaravana ($organizador, $cidade)
    {
       try {
          $org_oficial = $this->buscarInscricaoEmail ($organizador->email);
       } catch (Sis_Exception_InscricaoInvalida $ex) {
          $org_oficial = $this->novaInscricao($organizador);
       }
       if ($org_oficial->id_caravana !== null) {
          throw new Sis_Exception_CaravanaInvalida("Organizador já faz parte de outra caravana");
       }

       $dados_caravana = $this->persist->novaCaravana ($cidade, $org_oficial->id);

       $dados_caravana->organizador = $org_oficial;
       $dados_caravana->participantes[] = $org_oficial;
       
       $org_oficial->id_caravana = (int)$dados_caravana->id;
       $this->persist->updateInscricao($org_oficial);

       enviaEmail("caravana", $org_oficial, $dados_caravana->cod_desconto);

       return $dados_caravana;
    }

    function buscarCaravana($cod_desconto)
    {
       return $this->persist->buscarCaravana($cod_desconto);
    }

    /**
     * Busca uma caravana pelo seu ID.
     *
     * @param int $id
     * @return Sis_Caravana caravana
     */
    function buscarCaravanaId($id)
    {
       return $this->persist->buscarCaravanaId($id);
    }
}