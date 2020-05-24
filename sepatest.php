<?php
// the autoloader will find all needed files...
require_once 'autoloader.php';

use SKien\Sepa\Sepa;
use SKien\Sepa\SepaDoc;
use SKien\Sepa\SepaPmtInf;
use SKien\Sepa\SepaTxInf;

	// some valid transactions
	$aValidTransactions = array(
		array(
				'dblValue' => 104.45, 
				'strDescription' => 'Test Betreff 1', 
				'strName' => 'Mustermann, Max', 
				'strIBAN' => 'DE11682900000009215808', 
				'strBIC' => 'GENODE61LAH', 
				'strMandateId' => 'ID-0815', 
				'strDateOfSignature' => '2018-04-03'
		),
		array(
				'dblValue' => 205.67, 
				'strDescription' => 'Test Betreff 2', 
				'strName' => 'Musterfrau, Karin', 
				'strIBAN' => 'DE71664500500070143559', 
				'strBIC' => 'SOLADES1OFG', 
				'strMandateId' => 'ID-0816', 
				'strDateOfSignature' => '2019-06-09'
		)
	);
	
	// some invalid transactions to test validation
	$aInvalidTransactions = array(
			array(
					'dblValue' => 104.45,
					'strDescription' => 'Ein Eidgenosse...',
					'strName' => '',						// missing Name
					'strIBAN' => 'CH18 0483 5029 8829 8100 0',
					'strBIC' => 'CRESCHZZ80A',
					'strMandateId' => 'ID-0815',
					'strDateOfSignature' => '18-04-03'		// Wrong Date
			),
			array(
					// 'dblValue' => 104.45,				// no value
					'strDescription' => '',					// missing descr
					'strName' => 'Mustermann, Max',
					'strIBAN' => 'FR14 2004 1010 0505 0001 3M02 606',
					'strBIC' => 'GENODE61LAH',				
					// 'strMandateId' => 'ID-0815',			// missing
					// 'strDateOfSignature' => '18-04-03'	// missing
			),
			array(
					'dblValue' => 205.67,
					'strDescription' => 'Test Betreff 2 - enthält Umlaute, Sonderzeichen {} _@ und ungültige Zeichen [&%]',
					'strName' => 'Musterfrau, Karin',
					'strIBAN' => 'DE71664600500070143559',	// Wrong checksum
					'strBIC' => 'SOLADES1OG',				// missing sign
					'strMandateId' => 'ID-0816',
					'strDateOfSignature' => '2019-06-09'
			)
	);
	
	// initialize package
	// - init() have to be called first before any use of the package!
	// - full validation is by default activated
	// - switch to german error messages
	Sepa::init();
	Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);
	Sepa::loadErrorMsg('sepa_errormsg_de.json');
	
	// test for dirct debit transdaction
	$type = Sepa::CDD;
	
	// create new SEPA document with header
	$oSepaDoc = new SepaDoc($type);
	$oSepaDoc->createGroupHeader('Test company 4711');

	// create payment info instruction (PII) and set all needet creditor information
	$oPPI = new SepaPmtInf($oSepaDoc);
	$oPPI->setName('Testfirma');
	$oPPI->setCI('DE79 ZZZ 01234567890');
	$oPPI->setIBAN('DE71664500500070143559');
	$oPPI->setBIC('GENODE61LAH');
	$oPPI->setSeqType(Sepa::SEQ_RECURRENT);
	
	// add the PII to the document.
	// NOTE: dont' add any transaction to the PII bofore added to the doc!   
	if (($iErr = $oSepaDoc->addPaymentInstructionInfo($oPPI)) == Sepa::OK) {
		// test for transactions through SepaTxInf::fromArray() 
		foreach ($aInvalidTransactions as $aTrans) {
			$oTxInf = new SepaTxInf($type);
			$oTxInf->fromArray($aTrans);
			$iErr = $oPPI->addTransaction($oTxInf);
			if ($iErr != Sepa::OK) {
				echo '<h2>' . $oTxInf->getName() . ' (' . $oTxInf->getDescription() . ')</h2>';
				echo $oTxInf->errorMsg($iErr, '<br />');
			} else {
				$strPaymentId = $oTxInf->getPaymentId();
				// ... may write back generated id to database 
			}
		}
		
		// test for direct call of SepaTxInf::setXXX Methods
		$oTxInf = new SepaTxInf($type);
	
		$oTxInf->setValue( 168.24 );
		$oTxInf->setDescription('Test Betreff 3');
		$oTxInf->setName('Doe, John');
		$oTxInf->setIBAN('DE71664500500070143559');
		$oTxInf->setBIC('SOLADES1OFG');
		$oTxInf->setMandateId('ID-4711');
		$oTxInf->setDateOfSignature(new DateTime('22.12.2017'));
		
		$iErr = $oTxInf->validate();
		if ($iErr == Sepa::OK) {
			$oPPI->addTransaction($oTxInf);
		} else {
			echo $oTxInf->getName() . ' (' . $oTxInf->getDescription() . ')<br />';
			echo $oTxInf->errorMsg($iErr, '<br />');
		}
		
		if ($oSepaDoc->getInvalidCount() == 0) {
			// ... may cretae some loging etc.
			$strLog = date('Y-m-d H:i') . ': SEPA Direct Debit Transactions erzeugt (';
			$strLog .= $oSepaDoc->getTxCount() . 'Transaktionen / ';
			$strLog .= sprintf("%01.2f", $oSepaDoc->getCtrlSum()) . ' &euro;)';
			
			$oSepaDoc->output('test.xml');
		}
	} else {
		echo '<h2>' . $oPPI->getName() . '</h2>';
		echo $oPPI->errorMsg($iErr, '<br />');
	}
	