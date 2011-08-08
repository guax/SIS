<?php
abstract class Sis_Payment {

    /**
     * Pagamento não efetuado
     */
    const UNPAID = "unpaid";

    /**
     * Pagamento efetuado e confirmado
     */
    const PAID = "paid";

    /**
     * Pagamento aguardando confirmação
     */
    const OPEN = "open";

    /**
     * Pagamentos encerrados
     */
    const CLOSED = "closed";
}
