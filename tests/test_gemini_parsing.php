<?php
// tests/test_gemini_parsing.php

echo "Testing JSON Cleaning Logic...\n";
echo "------------------------------\n";

function test_cleaning($rawInput, $testName) {
    echo "Test: $testName\n";
    
    // Logic copied from evaluator.php
    $cleanResponse = preg_replace('/^```json\s*|\s*```$/m', '', $rawInput);
    
    if (preg_match('/\{.*\}/s', $cleanResponse, $matches)) {
        $cleanResponse = $matches[0];
    }
    
    // The specific robust regex
    $cleanResponse = preg_replace('/[^\x20-\x7E\t\r\n\x{00A0}-\x{00FF}\x{0100}-\x{017F}\x{0080}-\x{00FF}]/u', '', $cleanResponse);
    
    $decoded = json_decode($cleanResponse, true);
    
    if (json_last_error() === JSON_ERROR_NONE && $decoded) {
        echo "PASS: parsed successfully.\n";
        // echo "Output: " . json_encode($decoded) . "\n";
    } else {
        echo "FAIL: " . json_last_error_msg() . "\n";
        echo "Cleaned Input: [" . $cleanResponse . "]\n";
    }
    echo "------------------------------\n";
}

// Case 1: Clean JSON
$json1 = '{"score": 90, "feedback": "Good job"}';
test_cleaning($json1, "Clean JSON");

// Case 2: Markdown
$json2 = "```json\n" . $json1 . "\n```";
test_cleaning($json2, "Markdown Fences");

// Case 3: Surrounding Text
$json3 = "Here is your JSON:\n" . $json1 . "\nHope it helps.";
test_cleaning($json3, "Surrounding Text");

// Case 4: Control Characters (Null byte)
$json4 = '{"score": 90, "feedback": "Bad char here -> ' . "\x00" . ' <-"}';
test_cleaning($json4, "Control Chars (Null Byte)");

// Case 5: Valid Control Chars (Newlines)
$json5 = '{"score": 90, "feedback": "Line 1\nLine 2"}';
test_cleaning($json5, "Valid Newlines");

// Case 6: Vertical Tab (Should be stripped)
$json6 = '{"score": 90, "feedback": "Vertical tab -> ' . "\x0b" . ' <-"}';
test_cleaning($json6, "Vertical Tab (Strip)");

?>
