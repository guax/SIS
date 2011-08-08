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

/**
 * Geração de certificados em PDF para usuários cadastrados e confirmados no
 * sistema.
 */
class CertificateController extends Zend_Controller_Action {

    public function indexAction() {
        $sis = new Sis_Sis();
        $user = $sis->buscarInscricaoEmail(Zend_Auth::getInstance()->getIdentity()->email);
        $db = Zend_Registry::get('db');
        $result = $db->query("SELECT confirmed FROM participante WHERE id='{$user->id}'")->fetchObject();
        $this->view->valid = $result->confirmed == false ? false : true;
    }

    public function verifyAction() {
        $sis = new Sis_Sis();
        $this->view->id = $this->getRequest()->getParam("userId");
        try {
            $this->view->valid = true;
            $user = $sis->buscarInscricao($this->getRequest()->getParam("userId"));

            $db = Zend_Registry::get('db');
            $result = $db->query("SELECT confirmed FROM participante WHERE id='{$user->id}'")->fetchObject();
            if ($result->confirmed == false) {
                $this->view->valid = false;
            }
            $this->view->name = $user->nome;
        } catch (Sis_Exception_InscricaoInvalida $ex) {
            $this->view->valid = false;
        }
    }

    public function downloadAction() {
        $this->_helper->layout()->disableLayout();
        $this->getHelper('viewRenderer')->setNoRender();

        $this->getFrontController()->getResponse()->setHeader("Content-Type", "application/pdf");

        $pdf = new Sis_Pdf();
        $page = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_A4_LANDSCAPE);
        $leftMargin = 40;
        $rightMargin = 40;

        $image = new Zend_Pdf_Resource_Image_Png("../logo.png");

        $imageWidth = 250;
        $imageHeight = 81;

        $x1 = 50;
        $y2 = $page->getHeight() - 30;

        $y1 = $y2 - $imageHeight;
        $x2 = $imageWidth + $x1;

        $page->drawImage($image, $x1, $y1, $x2, $y2);

        $offset = $page->getHeight() - 250;

        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 50);
        Sis_Pdf::drawText($page, "CERTIFICADO", $leftMargin, $offset + 160, $page->getWidth() - 50, Sis_Pdf::TEXT_ALIGN_RIGHT);

        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 16);
        Sis_Pdf::drawText($page, "A SOLISC Associação Software Livre de Santa Catarina, confere esse certificado à", $leftMargin, $offset, $page->getWidth() - $rightMargin, Sis_Pdf::TEXT_ALIGN_CENTER);

        $sis = new Sis_Sis();
        $user = $sis->buscarInscricaoEmail(Zend_Auth::getInstance()->getIdentity()->email);

        $offset -= 40;
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 18);
        Sis_Pdf::drawText($page, $user->nome, $leftMargin, $offset, $page->getWidth() - $rightMargin, Sis_Pdf::TEXT_ALIGN_CENTER);

        $offset -= 40;
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 16);
        $text = "Por sua participação no 5º SoLiSC – Congresso Catarinense de Software Livre";
        Sis_Pdf::drawText($page, $text, $leftMargin, $offset, $page->getWidth() - $rightMargin, Sis_Pdf::TEXT_ALIGN_CENTER);

        $offset -= 20;
        $text = "realizado em Florianópolis, nos dias 22 e 23 de Outubro de 2010";
        Sis_Pdf::drawText($page, $text, $leftMargin, $offset, $page->getWidth() - $rightMargin, Sis_Pdf::TEXT_ALIGN_CENTER);

        $offset -= 20;
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 16);
        $text = "totalizando 16 horas de atividades.";
        Sis_Pdf::drawText($page, $text, $leftMargin, $offset, $page->getWidth() - $rightMargin, Sis_Pdf::TEXT_ALIGN_CENTER);

        $address = "http://www.solisc.org.br/2010/sis/certificate/verify/" . $user->id;

        $text = "A autenticidade desse documento pode ser verificada no endereço: $address";
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 12);
        $page->drawText($text, $leftMargin, 30, "utf-8");

        $image = new Zend_Pdf_Resource_Image_Png("../baleia.png");
        $imageWidth = 154;
        $imageHeight = 110;

        $offset -= 30;

        $x1 = ($page->getWidth() - $imageWidth) / 2;
        $y1 = $offset - $imageHeight;

        $x2 = $x1 + $imageWidth;
        $y2 = $offset;

        $page->drawImage($image, $x1, $y1, $x2, $y2);

        $pdf->pages[] = $page;
        echo $pdf->render();
    }

    protected function sendMail($email, $nome) {
        $mailbody = <<<MAIL
   <p>Prezado %s, foi confirmada sua presença no 5º SoLiSC. Caso queira
       emitir um certificado de participação. Vá até o endereço: http://www.solisc.org.br/2010/sis/ faça login usando este email e sua senha e clique na opção Certificado.</p>
       <p>Lembre-se de conferir o seu nome no cadastro antes de baixar o certificado.</p>
MAIL;
        $mail = new Zend_Mail("utf8");
        $mail->setBodyHtml(sprintf($mailbody, $nome));
        $mail->addTo($email);
        $mail->setFrom("no-reply@solisc.org.br");
        $mail->setSubject("Emissão de Certificado");
        $mail->send();
    }

    public function confirmAction() {
        $db = Zend_Registry::get('db');

        if ($this->getRequest()->isPost()) {
            $id = $_POST['userId'];
            $db->update("participante", array("confirmed" => true), "id='$id'");
            $sis = new Sis_Sis();
            $user = $sis->buscarInscricao($id);
            $this->sendMail($user->email, $user->nome);
        }
        $this->view->users = $db->query("select * from participante where confirmed=false")->fetchAll();
    }

}
