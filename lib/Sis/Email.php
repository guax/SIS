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

function textoConfirmacao ($participante, $autenticacao)
{
   $datapag = date('d/m/Y', $participante->data_pagamento);
   $valpag = sprintf('R$ %6.2f', $participante->valor_pagamento);
   $htmlInscricao = "
       <html>
         <body>
	   <div>
	     <table style='border: 2px solid black; padding: 5px;' width='100%'>
	       <tbody><tr>
		   <td><big>IMPRIMA E LEVE AO EVENTO!</big></td>
		 </tr>
		 <tr>
		   <td colspan='2'>
		     Sua inscrição no SOLISC 2010 está confirmada.
		   </td>
		 </tr>
                 
		 <tr>
		 </tr><tr>
		   <td colspan='3' >
		     <hr>
		     <h2>INSCRIÇÃO $participante->id <br></h2>
		     <hr>
		   </td>
		 </tr>
		 <tr>
		   <td><font size='+1'><b>Nome:</b>$participante->nome</font>
		   </td>
		 </tr>
		 <tr>
		   <td>
		     <font size='+1'><b>Data de pagamento:</b></font>$datapag
		   </td>
		 </tr>
		 <tr>
		   <td>
		     <font size='+1'><b>Valor Pago:</b></font>$valpag
		   </td>
		 </tr>
		 <tr>
		   <td>
		     <font size='+1'><b>Código Autenticação:</b></font>$autenticacao
		   </td>
		 </tr>
	     </tbody></table>
             
	   </div>
         </body>
       </html>
       ";

   return $htmlInscricao;
}


function textoCadastro($participante)
{
   $htmlCadastro = "
      <html>
	<body>
	  <div>
	    <table style='border: 2px solid black; padding: 5px;' width='100%'>
	      <tbody>
                <tr>
		  <td><big>Confirmação de Cadastro</big></td>
		</tr>
		<tr>
		  <td colspan='2'>
		    Seu cadastro (pré-inscrição) do SOLISC 2010 está confirmado.
		  </td>
		</tr>
		
		<tr>
		  <td colspan='3'>
		    <hr>
		    <b>Nome:</b> $participante->nome
		  </td>
		</tr>
		<tr>
		  <td colspan='3'>
		    <hr>
		    <b>e-mail:</b> $participante->email
		  </td>
		</tr>
		<tr>
		  <td colspan='3'>
		    <b>senha:</b> $participante->senha
		  </td>
		</tr>
		<tr>
		  <td colspan='3'>
		    <hr>
		    <b>Favor acessar o site: <a href='http://www.solisc.org.br/sis/auth/login'>www.solisc.org.br/sis</a> e alterar sua senha.</b>
		  </td>
		</tr>
		<tr>
		  <td colspan='3'>
		    Após a confirmação do pagamento, será enviado ao seu e-mail o código da inscrição.
		  </td>
		</tr>
		
	      </tbody>
	    </table>
	  </div>
	</body>
      </html>
      ";
   return $htmlCadastro;
}

function textoCaravana($participante, $cod_desconto)
{
   $htmlCadastro = "
      <html>
	<body>
	  <div>
	    <table style='border: 2px solid black; padding: 5px;' width='100%'>
	      <tbody>
                <tr>
		  <td><big>Confirmação de Cadastro</big></td>
		</tr>
		<tr>
		  <td colspan='2'>
		    <p>O cadastro da sua caravana no SOLISC 2010 está confirmado.</p>
                    <p>O código de desconto informado neste e-mail deve ser repassado a todos
                       os participantes da sua caravana para que eles efetuem a inscrição. A
                       sua inscrição como organizador da caravana não precisa ser paga, e
                       será considerada quitada quando houverem 5 (cinco) inscrições, incluindo a
                       sua própria, nesta caravana.<p>
                    <p>Com o uso do código de desconto, os participantes da sua caravana pagarão
                       o valor da inscrição de estudante</p>.
		  </td>
		</tr>
		
		<tr>
		  <td colspan='3'>
		    <hr>
		    <b>Nome do organizador:</b> $participante->nome
		  </td>
		</tr>
		<tr>
		  <td colspan='3'>
		    <hr>
		    <b>e-mail do organizador:</b> $participante->email
		  </td>
		</tr>
		<tr>
		  <td colspan='3'>
		    <b>código de desconto:</b> $cod_desconto
		  </td>
		</tr>
	      </tbody>
	    </table>
	  </div>
	</body>
      </html>
      ";
   return $htmlCadastro;
}

function enviaEmail ($tipoEmail, $participante, $autenticacao)
{
   $to = $participante->email;
   $subject = "";
   $html = "";
   if ($tipoEmail == "cadastro") {
      $subject = "Confirmação de Cadastro - SOLISC 2010";
      $html = textoCadastro($participante);
   } else if ($tipoEmail == "inscricao") {
      $subject = "Confirmação de Inscrição - SOLISC 2010";
      $html = textoConfirmacao($participante, $autenticacao);
   } else if ($tipoEmail == "caravana") {
      $subject = "Cadastro de Caravana - SOLISC 2010";
      $html = textoCaravana($participante, $autenticacao);
   }
	
   //Código que envia o e-mail
   $headers = "MIME-Version: 1.0\r\n";
   $headers .= "From: no-reply@solisc.org.br\r\n";
   $headers .= "Content-type: text/html; charset=utf-8\r\n";
   return mail($to, $subject, $html, $headers);
}
