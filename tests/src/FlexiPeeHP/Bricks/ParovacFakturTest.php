<?php

namespace Test\FlexiPeeHP\Bricks;

use FlexiPeeHP\Bricks\ParovacFaktur;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2018-04-17 at 19:11:15.
 */
class ParovacFakturTest extends \Test\Ease\SandTest
{
    /**
     * @var ParovacFaktur
     */
    protected $object;

    /**
     * Prepare Testing Invoice
     * 
     * @param array $initialData
     * 
     * @return \FlexiPeeHP\FakturaVydana
     */
    public function makeInvoice($initialData = [])
    {
        return \Test\FlexiPeeHP\FakturaVydanaTest::makeTestInvoice($initialData,
                1, 'vydana');
    }

    /**
     * Prepare testing payment
     * 
     * @param array $initialData
     * 
     * @return \FlexiPeeHP\Banka
     */
    public function makePayment($initialData = [])
    {
        return \Test\FlexiPeeHP\BankaTest::makeTestPayment($initialData,1);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new ParovacFaktur(["LABEL_PREPLATEK" => 'PREPLATEK', "LABEL_CHYBIFAKTURA" => 'CHYBIFAKTURA',
            "LABEL_NEIDENTIFIKOVANO" => 'NEIDENTIFIKOVANO']);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        
    }

    public function testGetDocumentTypes()
    {
        $this->assertArrayHasKey('FAKTURA', $this->object->getDocumentTypes());
    }

    /**
     * @covers FlexiPeeHP\Bricks\ParovacFaktur::setStartDay
     */
    public function testSetStartDay()
    {
        $this->object->setStartDay(1);
        $this->assertEquals(1, $this->object->daysBack);
    }

    /**
     * @covers FlexiPeeHP\Bricks\ParovacFaktur::getPaymentsToProcess
     */
    public function testGetPaymentsToProcess()
    {
        $this->object->getPaymentsToProcess(0); //Empty Restult
        $payment           = $this->makePayment(['popis' => 'Test GetPaymentsToProcess FlexiPeeHP-Bricks']);
        $paymentsToProcess = $this->object->getPaymentsToProcess(1);
        $this->assertArrayHasKey($payment->getRecordID(), $paymentsToProcess,
            'Can\'t find Payment');
    }

    /**
     * @covers FlexiPeeHP\Bricks\ParovacFaktur::getInvoicesToProcess
     */
    public function testGetInvoicesToProcess()
    {
        $invoice           = $this->makeInvoice(['popis' => 'Test InvoicesToProcess FlexiPeeHP-Bricks']);
        $invoicesToProcess = $this->object->getInvoicesToProcess(1);
        $this->assertArrayHasKey($invoice->getRecordID(), $invoicesToProcess,
            'Can\'t find Invoice');
    }

    /**
     * @covers FlexiPeeHP\Bricks\ParovacFaktur::invoicesMatchingByBank
     */
    public function testInvoicesMatchingByBank()
    {
        $faktura         = $this->makeInvoice(['typDokl' => \FlexiPeeHP\FlexiBeeRO::code('FAKTURA'),
            'popis' => 'InvoicesMatchingByBank FlexiPeeHP-Bricks Test']);
        $zaloha          = $this->makeInvoice(['typDokl' => \FlexiPeeHP\FlexiBeeRO::code('ZÁLOHA'),
            'popis' => 'InvoicesMatchingByBank FlexiPeeHP-Bricks Test']);
        $dobropis        = $this->makeInvoice(['typDokl' => \FlexiPeeHP\FlexiBeeRO::code('DOBROPIS'),
            'popis' => 'InvoicesMatchingByBank FlexiPeeHP-Bricks Test']);
        $this->object->setStartDay(-1);
        $this->object->outInvoicesMatchingByBank();
        $this->object->setStartDay(1);
        $paymentChecker  = new \FlexiPeeHP\Banka(null,
            ['detail' => 'custom:sparovano']);
        $paymentsToCheck = $this->object->getPaymentsToProcess(1);
        $this->object->outInvoicesMatchingByBank();
        foreach ($paymentsToCheck as $paymentID => $paymentData) {
            $paymentChecker->loadFromFlexiBee(\FlexiPeeHP\FlexiBeeRO::code($paymentData['kod']));
            $this->assertEquals('true',
                $paymentChecker->getDataValue('sparovano'), 'Matching error');
        }
    }

    /**
     * @covers FlexiPeeHP\Bricks\ParovacFaktur::invoicesMatchingByInvoices
     */
    public function testInvoicesMatchingByInvoices()
    {

        $faktura  = $this->makeInvoice(['typDokl' => \FlexiPeeHP\FlexiBeeRO::code('FAKTURA'),
            'popis' => 'InvoicesMatchingByInvoices FlexiPeeHP-Bricks Test']);
        $zaloha   = $this->makeInvoice(['typDokl' => \FlexiPeeHP\FlexiBeeRO::code('ZÁLOHA'),
            'popis' => 'InvoicesMatchingByInvoices FlexiPeeHP-Bricks Test']);
        $dobropis = $this->makeInvoice(['typDokl' => \FlexiPeeHP\FlexiBeeRO::code('DOBROPIS'),
            'popis' => 'InvoicesMatchingByInvoices FlexiPeeHP-Bricks Test']);

        $invoiceChecker  = new \FlexiPeeHP\FakturaVydana(null,
            ['detail' => 'custom:sparovano']);
        $invoicesToCheck = $this->object->getPaymentsToProcess(1);
        if (empty($invoicesToCheck)) {
            $this->markTestSkipped(_('No invoices to Process. Please run '));
        } else {
            $this->object->invoicesMatchingByInvoices();
            foreach ($invoicesToCheck as $paymentID => $paymentData) {
                $invoiceChecker->loadFromFlexiBee($paymentID);
                $this->assertEquals('true',
                    $invoiceChecker->getDataValue('sparovano'), 'Matching error');
            }
        }
    }

    /**
     * @covers FlexiPeeHP\Bricks\ParovacFaktur::settleCreditNote
     */
    public function testSettleCreditNote()
    {
        $dobropis = $this->makeInvoice(['typDokl' => \FlexiPeeHP\FlexiBeeRO::code('ODD'),
            'popis' => 'Test SettleCreditNote FlexiPeeHP-Bricks']);
        $payment  = $this->makePayment();
        $this->assertEquals(1,
            $this->object->settleCreditNote($dobropis, $payment));
    }

    /**
     * @covers FlexiPeeHP\Bricks\ParovacFaktur::settleProforma
     */
    public function testSettleProforma()
    {
        $zaloha  = $this->makeInvoice(['typDokl' => \FlexiPeeHP\FlexiBeeRO::code('ZÁLOHA'),
            'popis' => 'Test SettleProforma FlexiPeeHP-Bricks']);
        $payment = $this->makePayment();
        $this->object->settleProforma($zaloha, $payment->getData());
    }

    /**
     * @covers FlexiPeeHP\Bricks\ParovacFaktur::settleInvoice
     */
    public function testSettleInvoice()
    {
        $invoice = $this->makeInvoice(['typDokl' => \FlexiPeeHP\FlexiBeeRO::code('FAKTURA'),
            'popis' => 'Test SettleInvoice FlexiPeeHP-Bricks PHPUnit']);
        $payment = $this->makePayment();
        $this->assertEquals(1, $this->object->settleInvoice($invoice, $payment));
    }

    /**
     * @covers FlexiPeeHP\Bricks\ParovacFaktur::invoiceCopy
     */
    public function testInvoiceCopy()
    {
        $invoice = $this->makeInvoice(['popis' => 'Test InvoiceCopy FlexiPeeHP-Bricks']);
        $this->object->invoiceCopy($invoice, ['poznam' => 'Copied By unitTest']);
    }

    /**
     * @covers FlexiPeeHP\Bricks\ParovacFaktur::hotfixDeductionOfAdvances
     */
    public function testHotfixDeductionOfAdvances()
    {
        $varSym = \Ease\Sand::randomNumber(1111, 9999);
        $price  = \Ease\Sand::randomNumber(11, 99);


        $invoice = $this->makeInvoice(['typDokl' => 'code:ZDD', 'varSym' => $varSym,
            'sumZklZakl' => $price]);
        $payment = $this->makePayment(['varSym' => $varSym, 'sumZklZakl' => $price]);

        $this->object->hotfixDeductionOfAdvances($invoice, $payment);
    }

    /**
     * @covers FlexiPeeHP\Bricks\ParovacFaktur::findInvoices
     */
    public function testFindInvoices()
    {
        $this->makeInvoice(['varSym' => '123', 'poznam' => 'Test FindInvoices FlexiPeeHP-Bricks']);
        $this->makeInvoice(['specSym' => '356', 'poznam' => 'Test FindInvoices FlexiPeeHP-Bricks']);

        $this->object->findInvoices(['id' => '1', 'varSym' => '123']);
        $this->object->findInvoices(['id' => '2', 'specSym' => '356']);
    }

    /**
     * @covers FlexiPeeHP\Bricks\ParovacFaktur::findPayments
     */
    public function testFindPayments()
    {
        $this->object->findPayments(['varSym' => '123']);
        $this->object->findPayments(['specSym' => '356']);
    }


    /**
     * @covers FlexiPeeHP\Bricks\ParovacFaktur::findPayment
     */
    public function testFindPayment()
    {
        $this->object->findPayment(['varSym' => 123]);
        $this->object->findPayment(['specSym' => 456]);
    }

    /**
     * @covers FlexiPeeHP\Bricks\ParovacFaktur::findBestPayment
     */
    public function testFindBestPayment()
    {
        $varSym  = \Ease\Sand::randomNumber(111111, 999999);
        $specSym = \Ease\Sand::randomNumber(1111, 9999);
        $price   = \Ease\Sand::randomNumber(111, 999);

        $invoiceSs     = $this->makeInvoice(['varSym' => $varSym, 'specSym' => $specSym,
            'sumCelkem' => $price]);
        $paymentSs     = $this->makePayment(['specSym' => $specSym, 'sumCelkem' => $price]);
        $bestSSPayment = $this->object->findBestPayment([$paymentSs->getData()],
            $invoiceSs);

        $this->assertTrue(is_object($bestSSPayment));

        $invoiceVs     = $this->makeInvoice(['varSym' => $varSym]);
        $paymentVs     = $this->makePayment(['varSym' => $varSym]);
        $bestVSPayment = $this->object->findBestPayment([$paymentVs->getData()],
            $invoiceVs);
    }

    /**
     * @covers FlexiPeeHP\Bricks\ParovacFaktur::apiUrlToLink
     */
    public function testApiUrlToLink()
    {
        $this->assertEquals('<a href="'.constant('FLEXIBEE_URL').'/c/'.constant('FLEXIBEE_COMPANY').'/banka.json" target="_blank" rel="nofollow">https://demo.flexibee.eu:5434/c/demo/banka.json</a>',
            $this->object->apiUrlToLink($this->object->banker->apiURL));
    }
}
