<?php
// services/evaluator.php
// FR7: otomatik değerlendirme
// FR8: başarı yüzdesi
// FR9: eksik konular
// FR10: geri bildirim üretme

function evaluate_quiz(array $postData) {
    // q_1, q_2 gibi alanlardan puan hesapla
    $total = 0;
    $correct = 0;
    $weakTopics = [];

    foreach ($postData as $key => $val) {
        if (strpos($key, 'q_') === 0) {
            $qid = str_replace('q_', '', $key);
            $userChoice = (int)$val;
            $correctChoice = (int)($postData['correct_' . $qid] ?? -1);
            $topic = $postData['topic_' . $qid] ?? 'Unknown topic';

            $total++;
            if ($userChoice === $correctChoice) {
                $correct++;
            } else {
                $weakTopics[] = $topic;
            }
        }
    }

    $scorePercent = $total > 0 ? round(($correct / $total) * 100) : 0;

    $feedback = $scorePercent >= 80
        ? "Great job! Keep practicing to maintain your level."
        : "Review the topics you missed and retry similar questions.";

    return [
        'score_percent' => $scorePercent,
        'weak_topics' => array_values(array_unique($weakTopics)),
        'feedback' => $feedback,
        'details' => ['correct' => $correct, 'total' => $total]
    ];
}

function evaluate_writing(string $text) {
    $wordCount = str_word_count($text);
    $score = 50;

    // Basit heuristics
    if ($wordCount >= 80) $score += 20;
    if ($wordCount >= 100) $score += 10;

    // Çok basit grammar ipuçları (demo)
    $commonMistakes = [];
    if (stripos($text, "He go ") !== false) $commonMistakes[] = "3rd person singular (he/she/it) needs -s (e.g., 'He goes').";
    if (stripos($text, "I am agree") !== false) $commonMistakes[] = "Use 'I agree' (not 'I am agree').";

    $score = min(100, $score);

    $feedback = "Word count: {$wordCount}. ";
    $feedback .= $commonMistakes
        ? "Suggestions: " . implode(" ", $commonMistakes)
        : "Good structure. Try adding more vocabulary variety.";

    $weakTopics = $commonMistakes ? ["Grammar - common patterns"] : [];

    return [
        'score_percent' => $score,
        'weak_topics' => $weakTopics,
        'feedback' => $feedback
    ];
}

function evaluate_speaking(string $audioPath) {
    // Demo: Gerçek STT yoksa bile raporda "STT later" diye constraints'e bağlanır.
    // Şimdilik dosya var mı yok mu kontrol edip puan veriyoruz.
    $score = file_exists($audioPath) ? 70 : 0;

    return [
        'score_percent' => $score,
        'weak_topics' => $score ? ["Pronunciation (basic)"] : ["Audio submission failed"],
        'feedback' => $score
            ? "Audio received. In MVP, speaking feedback is generated using basic checks. Future work: STT + pronunciation scoring."
            : "Audio could not be processed."
    ];
}
?>
