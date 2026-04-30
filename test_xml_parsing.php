<?php

/**
 * Standalone XML parsing test
 * Tests the TEIF XML parsing logic without database
 */

// Load Composer autoloader
require __DIR__ . '/vendor/autoload.php';

// Test data
$xmlContent = file_get_contents(__DIR__ . '/tests/fixtures/test_invoice.xml');

// Parse XML
$xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOCDATA);

if (!$xml) {
    echo "❌ FAILED: Could not parse XML file\n";
    exit(1);
}

echo "✓ XML parsed successfully\n";

// Test root element detection
$rootName = $xml->getName();
echo "✓ Root element: " . $rootName . "\n";

if ($rootName !== 'TEIF') {
    echo "❌ FAILED: Expected TEIF root element, got " . $rootName . "\n";
    exit(1);
}

// Test header extraction
$header = $xml->InvoiceHeader;
$senderTaxId = (string) ($header->MessageSenderIdentifier ?? '');
$receiverTaxId = (string) ($header->MessageRecieverIdentifier ?? '');

echo "✓ Sender Tax ID: " . $senderTaxId . "\n";
echo "✓ Receiver Tax ID: " . $receiverTaxId . "\n";

if (empty($senderTaxId) || empty($receiverTaxId)) {
    echo "❌ FAILED: Missing sender or receiver tax ID\n";
    exit(1);
}

// Test body extraction
$body = $xml->InvoiceBody;
if (!$body) {
    echo "❌ FAILED: InvoiceBody not found\n";
    exit(1);
}

echo "✓ InvoiceBody extracted\n";

// Test BGM extraction
$bgm = $body->Bgm;
$invoiceId = (string) ($bgm->DocumentIdentifier ?? '');
$docType = $bgm->DocumentType;
$invoiceType = (string) ($docType['code'] ?? '');

echo "✓ Invoice ID: " . $invoiceId . "\n";
echo "✓ Invoice Type: " . $invoiceType . "\n";

if (empty($invoiceId) || empty($invoiceType)) {
    echo "❌ FAILED: Missing invoice ID or type\n";
    exit(1);
}

// Test DTM (Date) extraction
$dtm = $body->Dtm;
$issueDate = '';
foreach ($dtm->DateText as $dateText) {
    $functionCode = (string) ($dateText['functionCode'] ?? '');
    if ($functionCode === 'I-31') {
        $value = (string) $dateText;
        $format = (string) ($dateText['format'] ?? '');
        // Parse date
        if ($format === 'ddMMyy' && strlen($value) >= 6) {
            $day = substr($value, 0, 2);
            $month = substr($value, 2, 2);
            $year = '20' . substr($value, 4, 2);
            $issueDate = "$year-$month-$day";
        }
        break;
    }
}

echo "✓ Issue Date: " . $issueDate . "\n";

if (empty($issueDate)) {
    echo "❌ FAILED: Could not extract issue date\n";
    exit(1);
}

// Test lines extraction
$linSection = $body->LinSection;
$lineCount = 0;
$lines = [];

foreach ($linSection->Lin as $lin) {
    $lineId = (string) ($lin->ItemIdentifier ?? '');
    $linImd = $lin->LinImd;
    $itemCode = (string) ($linImd->ItemCode ?? '');
    $description = (string) ($linImd->ItemDescription ?? '');

    $linQty = $lin->LinQty;
    $quantity = (string) ($linQty->Quantity ?? '0');

    // Get amounts
    $unitPrice = '0';
    $lineAmount = '0';
    $linMoa = $lin->LinMoa;

    foreach ($linMoa->AmountDetails as $moaContainer) {
        $moa = $moaContainer->Moa;
        $typeCode = (string) ($moa['amountTypeCode'] ?? '');
        $amount = (string) ($moa->Amount ?? '0');

        if ($typeCode === 'I-183') {
            $unitPrice = $amount;
        } elseif ($typeCode === 'I-171') {
            $lineAmount = $amount;
        }
    }

    $lines[] = [
        'line_id' => $lineId,
        'item_code' => $itemCode,
        'description' => $description,
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'line_amount' => $lineAmount,
    ];

    $lineCount++;
}

echo "✓ Lines extracted: " . $lineCount . "\n";
if ($lineCount !== 2) {
    echo "❌ FAILED: Expected 2 lines, got " . $lineCount . "\n";
    exit(1);
}

foreach ($lines as $idx => $line) {
    echo "  Line " . ($idx + 1) . ": " . $line['description'] . " (Qty: " . $line['quantity'] . ", Price: " . $line['unit_price'] . ", Amount: " . $line['line_amount'] . ")\n";
}

// Test amounts extraction
$invoiceMoa = $body->InvoiceMoa;
$totalHt = '0';
$totalTva = '0';
$totalTtc = '0';

foreach ($invoiceMoa->AmountDetails as $amtDetails) {
    $moa = $amtDetails->Moa;
    $typeCode = (string) ($moa['amountTypeCode'] ?? '');
    $amount = (string) ($moa->Amount ?? '0');

    switch ($typeCode) {
        case 'I-176':
            $totalHt = $amount;
            break;
        case 'I-180':
            $totalTtc = $amount;
            break;
        case 'I-181':
            $totalTva = $amount;
            break;
    }
}

echo "✓ Totals HT: " . $totalHt . ", TVA: " . $totalTva . ", TTC: " . $totalTtc . "\n";

if (empty($totalHt) || empty($totalTtc)) {
    echo "❌ FAILED: Missing totals\n";
    exit(1);
}

// Test partners extraction
$partnerSection = $body->PartnerSection;
$senderName = '';
$receiverName = '';

foreach ($partnerSection->PartnerDetails as $partner) {
    $functionCode = (string) ($partner['functionCode'] ?? '');
    $nad = $partner->Nad;

    if ($nad) {
        $name = (string) ($nad->PartnerName ?? '');

        if ($functionCode === 'I-62') {
            $senderName = $name;
        } elseif ($functionCode === 'I-64') {
            $receiverName = $name;
        }
    }
}

echo "✓ Sender: " . $senderName . "\n";
echo "✓ Receiver: " . $receiverName . "\n";

if (empty($senderName) || empty($receiverName)) {
    echo "❌ FAILED: Missing sender or receiver name\n";
    exit(1);
}

// All tests passed
echo "\n✅ ALL TESTS PASSED!\n";
echo "\nParsed Invoice Summary:\n";
echo "  ID: " . $invoiceId . "\n";
echo "  Type: " . $invoiceType . "\n";
echo "  Date: " . $issueDate . "\n";
echo "  Sender: " . $senderName . " (" . $senderTaxId . ")\n";
echo "  Receiver: " . $receiverName . " (" . $receiverTaxId . ")\n";
echo "  Lines: " . $lineCount . "\n";
echo "  Total HT: " . $totalHt . " TND\n";
echo "  Total TVA: " . $totalTva . " TND\n";
echo "  Total TTC: " . $totalTtc . " TND\n";

exit(0);
