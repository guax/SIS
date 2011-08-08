<?php
/**
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

require_once ('Participante.php');

class Sis_Persistencia
{
    var $host = 'host';
    var $user = 'user';
    var $db   = 'db';
    var $pwd  = 'pwd';

    function __construct()
    {
        $config = Zend_Registry::get('config');
        $this->host = $config->{APPLICATION_ENV}->resources->db->params->host;
        $this->user = $config->{APPLICATION_ENV}->resources->db->params->username;
        $this->pwd  = $config->{APPLICATION_ENV}->resources->db->params->password;
        $this->db   = $config->{APPLICATION_ENV}->resources->db->params->dbname;
    }

    private function getConnection ()
    {
        $conn = mysql_pconnect ($this->host, $this->user, $this->pwd);
        if (!$conn || ! mysql_select_db ($this->db, $conn)) {
            throw new Sis_Exception_Persistencia ('Erro ao conectar o banco de dados');
        }
        return $conn;
    }

    function countInscricoes()
    {
        $q = 'select count(*) from participante';
        $res = mysql_query($q, $this->getConnection());
        if (!$res) {
            throw new Sis_Exception_Persistencia ('Inconsist�ncia no banco de dados');
        }
        $row = mysql_fetch_row($res);
        return $row[0];
    }

    private function validaObrigatorio ($dados, $campo, $pcampo)
    {
        if (! $dados[$campo] || strlen(trim($dados[$campo])) == 0) {
            throw new Sis_Exception_Persistencia (sprintf('O campo %s � obrigat�rio.', $pcampo));
        }
    }

    private function buscarIdCodDesconto ($tipo, $codigo)
    {
        $conn = $this->getConnection();
        $sql = sprintf ("select id from %s where cod_desconto = '%s'",
            $tipo, mysql_real_escape_string($codigo, $conn));
        $res = mysql_query ($sql, $conn);
        if ($this->checkDatabaseResult ($res, $conn)) {
            $linha = mysql_fetch_row($res);
            return $linha[0];
        }
        else {
            throw new Sis_Exception_Persistencia ("Falha ao identificar c�digo de desconto");
        }
    }

    private function buscarCodDescontoId ($tab, $id)
    {
        $conn = $this->getConnection();
        $sql = sprintf ("select cod_desconto from %s where id = %d", $tab, $id);
        $res = mysql_query ($sql, $conn);
        if ($this->checkDatabaseResult ($res, $conn)) {
            $linha = mysql_fetch_row($res);
            return $linha[0];
        }
        else {
            throw new Sis_Exception_Persistencia ("Falha ao identificar código de desconto");
        }

    }

    function buscarIdCaravana ($codigo)
    {
        return $this->buscarIdCodDesconto ('caravana', $codigo);
    }

    function buscarCodDescontoCaravana ($id) {
        return $this->buscarCodDescontoId ("caravana", $id);
    }
    
    private function checkDatabaseResult ($res, $conn)
    {
        if (!$res) {
            $errno = mysql_errno($conn);
            $errmsg = mysql_error($conn);

            $msg = sprintf ("Erro %d: %s", $errno, $errmsg);
            throw new Sis_Exception_Persistencia ($msg);
        }
        else {
            return TRUE;
        }
        return FALSE;
    }

    function valoresParaData ($data)
    {
        $conn = $this->getConnection();
        $sql = "select vl_normal, vl_estudante, vl_ufsc, vl_caravana, vl_grupo ".
               "from precos where dt_limite > '%s' order by dt_limite";
        $res = mysql_query (sprintf ($sql, date('Y-m-d',$data)), $conn);
        if (!$this->checkDatabaseResult ($res, $conn)) {
            throw new Sis_Exception_Persistencia ("Erro ao acessar o banco de dados");
        }
        $row = mysql_fetch_array($res);
        return $row;
    }


    function updateInscricao ($participante)
    {
        $conn = $this->getConnection();
        $old = $this->buscarInscricao("email", $participante->email);
        
        $sql=sprintf("update participante ".
            "set nome = '%s', sexo = '%s', pais = '%s', \n".
            "uf = '%s', cidade = '%s', logradouro = '%s', bairro = '%s', cep = '%s', \n".
            "forma_tratamento = '%s', tipo_documento = '%s', \n".
            "num_documento = '%s', estudante = %d, inst_ensino = '%s', \n".
            "curso = '%s', nivel_ensino = '%s', empresa = '%s', cargo = '%s', id_caravana = %s, \n" .
            "id_grupo = %s, dt_inscricao = '%s', vl_pagamento = %1.2f, dt_vencimento = '%s', dt_pagamento = %s\n".
            "where id = %d",
            mysql_real_escape_string ($participante->nome),
            mysql_real_escape_string ($participante->sexo),
            mysql_real_escape_string ($participante->pais),
            mysql_real_escape_string ($participante->uf),
            mysql_real_escape_string ($participante->cidade),
            mysql_real_escape_string ($participante->logradouro),
            mysql_real_escape_string ($participante->bairro),
            mysql_real_escape_string ($participante->cep),
            mysql_real_escape_string ($participante->forma_tratamento),
            mysql_real_escape_string ($participante->tipo_doc),
            mysql_real_escape_string ($participante->num_doc),
            mysql_real_escape_string ($participante->estudante),
            mysql_real_escape_string ($participante->inst_ensino),
            mysql_real_escape_string ($participante->curso),
            mysql_real_escape_string ($participante->nivel_ensino),
            mysql_real_escape_string ($participante->empresa),
            mysql_real_escape_string ($participante->cargo),
            is_numeric($participante->id_caravana) ?$participante->id_caravana:'NULL',
            is_numeric($participante->id_grupo)?$participante->id_grupo:'NULL',
            date('Y-m-d', $participante->data_inscricao),
            $participante->valor_pagamento,
            date('Y-m-d', $participante->dt_vencimento),
            ($participante->data_pagamento)?"'" . date('Y-m-d', $participante->data_pagamento) . "'":'NULL',
            $participante->id
        );
        $res = mysql_query ($sql, $conn);
        if (!$res) {
            $code = mysql_errno($conn);
            if ($code == 1062) {
                $msg = 'email já cadastrado';
            }
            else {
                $msg=sprintf ("Erro %d no banco de dados: %s", $code, mysql_error($conn));
            }
            throw new Sis_Exception_Persistencia ($msg);
        }
        return $participante;
    }
   /*
    * Os dados para a nova inscrição devem estar em um array
    * associativo, com os nomes dos campos do banco de dados
    */
    function novaInscricao ($participante)
    {
        $conn = $this->getConnection();

        $sql=sprintf("insert into participante (".
            "email, nome, sexo, pais, \n".
            "uf, cidade, logradouro, bairro, cep, \n".
            "forma_tratamento, tipo_documento, \n".
            "num_documento, estudante, inst_ensino, \n".
            "curso, nivel_ensino, empresa, cargo, id_caravana, \n" .
            "id_grupo, dt_inscricao, vl_pagamento, senha, dt_vencimento) \n".
            "values ('%s', '%s', '%s', '%s', \n".
            "'%s', '%s', '%s', '%s', '%s', \n".
            "'%s', '%s', \n".
            "'%s', %d, '%s', \n".
            "'%s', '%s', '%s', '%s', %s, \n".
            "%s, '%s', %1.2f, '%s', '%s')",
            mysql_real_escape_string ($participante->email),
            mysql_real_escape_string ($participante->nome),
            mysql_real_escape_string ($participante->sexo),
            mysql_real_escape_string ($participante->pais),
            mysql_real_escape_string ($participante->uf),
            mysql_real_escape_string ($participante->cidade),
            mysql_real_escape_string ($participante->logradouro),
            mysql_real_escape_string ($participante->bairro),
            mysql_real_escape_string ($participante->cep),
            mysql_real_escape_string ($participante->forma_tratamento),
            mysql_real_escape_string ($participante->tipo_doc),
            mysql_real_escape_string ($participante->num_doc),
            mysql_real_escape_string ($participante->estudante),
            mysql_real_escape_string ($participante->inst_ensino),
            mysql_real_escape_string ($participante->curso),
            mysql_real_escape_string ($participante->nivel_ensino),
            mysql_real_escape_string ($participante->empresa),
            mysql_real_escape_string ($participante->cargo),
            is_numeric($participante->id_caravana) ?$participante->id_caravana:'NULL',
            is_numeric($participante->id_grupo)?$participante->id_grupo:'NULL',
            date('Y-m-d'),
            $participante->valor_pagamento,
            sha1($participante->senha),
            date('Y-m-d', $participante->dt_vencimento)
        );

        $res = mysql_query ($sql, $conn);
        if (!$res) {
            $code = mysql_errno($conn);
            if ($code == 1062) {
                $msg = 'email já cadastrado';
            }
            else {
                $msg=sprintf ("Erro %d no banco de dados: %s", $code, mysql_error($conn));
            }
            throw new Sis_Exception_Persistencia ($msg);
        }
        $participante->id = mysql_insert_id ($conn);

        if( $participante->getGroupId() != null ) {
            $sql = "insert into membroGrupo values('$participante->id','{$participante->getGroupId()}')";
            $res = mysql_query($sql, $conn);
        }

        return $participante;
    }

    private function gerarCodigoDesconto($prefix, $str) {
        return sprintf ('%s%08X', $prefix, crc32($prefix.$str) );
    }

    function buscarCaravana ($cod_desconto) {
       $conn = $this->getConnection();

       $id = $this->buscarIdCaravana($cod_desconto);
       
       $sql = sprintf("select cidade, id_organizador, cod_desconto
               from caravana where id = %d", $id);

       $res = mysql_query($sql, $conn);
       if (!$this->checkDatabaseResult ($res, $conn)) {
          throw new Sis_Exception_Persistencia ("Erro ao acessar o banco de dados");
       }
       if (mysql_num_rows($res) > 0) {
          $val = mysql_fetch_assoc($res);
          $caravana = new Sis_Caravana();
          $caravana->id = $id;
          $caravana->cidade = $val['cidade'];
          $caravana->cod_desconto = $val['cod_desconto'];
          $caravana->organizador = $this->buscarInscricao('id', $val['id_organizador']);
          $caravana->participantes = $this->selectParticipantes('id_caravana', $id);
          return $caravana;
       } else {
          return False;
       }
    }

    function buscarCaravanaId ($id) {
        $conn = $this->getConnection();

       $sql = sprintf("select cidade, id_organizador, cod_desconto
               from caravana where id = %d", $id);

       $res = mysql_query($sql, $conn);
       if (!$this->checkDatabaseResult ($res, $conn)) {
          throw new Sis_Exception_Persistencia ("Erro ao acessar o banco de dados");
       }
       if (mysql_num_rows($res) > 0) {
          $val = mysql_fetch_assoc($res);
          $caravana = new Sis_Caravana();
          $caravana->id = $id;
          $caravana->cidade = $val['cidade'];
          $caravana->cod_desconto = $val['cod_desconto'];
          $caravana->organizador = $this->buscarInscricao('id', $val['id_organizador']);
          $caravana->participantes = $this->selectParticipantes('id_caravana', $id);
          return $caravana;
       } else {
          return False;
       }
    }

    function novaCaravana ($cidade, $id_organizador) {
        $conn = $this->getConnection();

        $sql_base = "insert into caravana (cidade, id_organizador) ".
                    "values ('%s', %d)";
        $sql = sprintf (
            $sql_base, mysql_real_escape_string ($cidade, $conn),
            $id_organizador);

        $res = mysql_query($sql, $conn);
        if (!$this->checkDatabaseResult ($res, $conn)) {
            throw new Sis_Exception_Persistencia ("Erro ao acessar o banco de dados");
        }
        $result = new Sis_Caravana();
        $result->id = mysql_insert_id();
        $result->organizador = $id_organizador;
        $result->cidade = $cidade;

        $codigoStr = sprintf ("%05d - $04d - %s", $result->id,
            $id_organizador, $cidade);
        $codigo_desconto = $this->gerarCodigoDesconto("CAR", $codigoStr);

        $sql = sprintf("update caravana set cod_desconto = '%s' where id = %d",
            $codigo_desconto, $result->id);
        $res = mysql_query($sql, $conn);
        $result->cod_desconto = $codigo_desconto;

        return $result;
    }

    function validaLogin ($email, $senha)
    {
        $conn = $this->getConnection();
        $id = 0;
        $sql = sprintf (
         "select id from participante where email = '%s' and senha = sha1('%s')",
            mysql_real_escape_string ($email, $conn), mysql_real_escape_string($senha, $conn));

        if (strlen($senha) > 0) {
            $res = mysql_query($sql, $conn);
            if (!$this->checkDatabaseResult ($res, $conn)) {
                throw new Sis_Exception_Persistencia ("Erro ao acessar o banco de dados");
            }
            if (mysql_num_rows($res) > 0) {
                $items = mysql_fetch_array($res);
                $id = $items[0];
            }
        }
        if ($id > 0) {
            return $id;
        }
        else {
            return FALSE;
        }
    }

    function alteraSenha ($id, $senha)
    {
        $conn = $this->getConnection();
        $sql = sprintf ("update participante set senha=sha1('%s') where id=%d",
            $senha, $id);
        $res = mysql_query($sql, $conn);
        if ($res) {
            return True;
        }
        else {
            return False;
        }
    }

    function buscarInscricao ($chave, $valor)
    {
        $conn = $this->getConnection();
        $sql = "select id, email, forma_tratamento, nome, sexo, \n" .
            "pais, uf, cidade, logradouro, bairro, cep, \n".
            "tipo_documento as tipo_doc, num_documento as num_doc, estudante, inst_ensino = 'UFSC' as estudante_ufsc, inst_ensino, \n".
            "curso, nivel_ensino, empresa, cargo, \n".
            "dt_inscricao as data_inscricao, vl_pagamento as valor_pagamento, dt_vencimento, \n".
            "dt_pagamento as data_pagamento, id_caravana, id_grupo, senha \n".
            "from participante where %s = '%s'";

        $res = mysql_query (sprintf ($sql, $chave, mysql_real_escape_string ($valor, $conn)));

        if (!$this->checkDatabaseResult ($res, $conn)) {
            throw new Sis_Exception_Persistencia ("Erro ao acessar o banco de dados");
        }
        if (mysql_num_rows($res) > 0) {
           $val = mysql_fetch_assoc($res);
           
           $result = new Sis_Participante();
           foreach ( $val as $key => $value) {
              if (!is_numeric($key)) {
                 switch($key) {
                     case 'tipo_documento':
                        $key = 'tipo_doc';
                        break;
                     case 'num_documento':
                        $key = 'num_doc';
                        break;
                     case 'dt_inscricao':
                        $key = 'data_inscricao';
                        break;
                     case 'dt_pagamento':
                        $key = 'data_pagamento';
                        break;
                     case 'vl_pagamento':
                        $key = 'valor_pagamento';
                        break;
                    case 'id_caravana':
                        $value = $value === null ? null : (int)$value;
                        break;
                 }
                 $result->{$key} = $value;
              }
           }
           $result->estudante = ($result->estudante != '0');
           $result->estudante_ufsc = ($result->estudante_ufsc != '0');
           $result->data_inscricao = strtotime ($result->data_inscricao);
           $result->data_pagamento = $result->data_pagamento === null? "": strtotime ($result->data_pagamento);
           $result->dt_vencimento = strtotime ($result->dt_vencimento);
           return $result;
        }
        else {
            return False;
        }
    }

    function buscarInscricaoExpr ($chave, $expr)
    {
        $conn = $this->getConnection();
        $sql = "select id, email, forma_tratamento, nome, sexo, \n" .
            "pais, uf, cidade, logradouro, bairro, cep, \n".
            "tipo_documento as tipo_doc, num_documento as num_doc, estudante, inst_ensino = 'UFSC' as estudante_ufsc, inst_ensino, \n".
            "curso, nivel_ensino, empresa, cargo, \n".
            "dt_inscricao as data_inscricao, vl_pagamento as valor_pagamento, dt_vencimento, \n".
            "dt_pagamento as data_pagamento, id_caravana, id_grupo, senha \n".
            "from participante where %s %s";

        $res = mysql_query (sprintf ($sql, $chave, $expr));

        if (!$this->checkDatabaseResult ($res, $conn)) {
            throw new Sis_Exception_Persistencia ("Erro ao acessar o banco de dados");
        }
        if (mysql_num_rows($res) > 0) {
           $result = array();
           $i = 0;
           while($val = mysql_fetch_assoc($res)) {
               $result[$i] = new Sis_Participante();
               foreach ( $val as $key => $value) {
                  if (!is_numeric($key)) {
                     switch($key) {
                     case 'tipo_documento':
                        $key = 'tipo_doc';
                        break;
                     case 'num_documento':
                        $key = 'num_doc';
                        break;
                     case 'dt_inscricao':
                        $key = 'data_inscricao';
                        break;
                     case 'dt_pagamento':
                        $key = 'data_pagamento';
                        break;
                     case 'vl_pagamento':
                        $key = 'valor_pagamento';
                        break;
                        case 'id_caravana':
                            $value = $value === null ? null : (int)$value;
                            break;
                     }
                     $result[$i]->{$key} = $value;
                  }
               }
               $result[$i]->estudante = ($result[$i]->estudante != '0');
               $result[$i]->estudante_ufsc = ($result[$i]->estudante_ufsc != '0');
               $result[$i]->data_inscricao = strtotime ($result[$i]->data_inscricao);
               $result[$i]->data_pagamento = $result[$i]->data_pagamento === null? "": strtotime ($result[$i]->data_pagamento);
               $result[$i]->dt_vencimento = strtotime ($result[$i]->dt_vencimento);
               $i++;
           }
           return $result;
        }
        else {
            return False;
        }
    }

    function selectParticipantes ($chave, $valor, $like = FALSE)
    {
        $conn = $this->getConnection();
        $base_select = "select id, email, nome, sexo, pais, uf, cidade, ".
         "logradouro, bairro, forma_tratamento, tipo_documento as tipo_doc, num_documento as num_doc, ".
         "estudante, inst_ensino, curso, nivel_ensino, empresa, cargo, id_caravana, ".
         "id_grupo, dt_inscricao as data_inscricao, dt_vencimento, vl_pagamento valor_pagamento, dt_pagamento as data_pagamento ".
         "from participante ";
        $operador = '=';
        $val = $valor;
        if (!is_numeric($valor))
        $val = "'".$valor."'";

        if ($like) {
            $operador = "like";
            $val = "'%".mysql_real_esacape_string($valor, $conn)."%'";
        }

        $sql = sprintf("%s where %s %s %s", $base_select, $chave, $operador, $val);

        $result = array();

        $res = mysql_query ($sql, $conn);
        if (!$this->checkDatabaseResult ($res, $conn)) {
            throw new Sis_Exception_Persistencia ("Erro ao acessar o banco de dados");
        }
        while ($val = mysql_fetch_assoc($res)) {
           $p = new Sis_Participante();
           foreach ( $val as $key => $value) {
              if (!is_numeric($key)) {
                 $p->{$key} = $value;
              }
           }
           $p->estudante = ($p->estudante != '0');
           $p->estudante_ufsc = ($p->estudante_ufsc != '0');
           $p->data_inscricao = strtotime ($p->data_inscricao);
           $p->data_pagamento = strtotime ($p->data_pagamento);
           $p->dt_vencimento = strtotime ($p->dt_vencimento);
           $result[] = $p;
        }

        return $result;
    }


}
